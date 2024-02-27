<?php

namespace MRBS;

use PDOException;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

// Suppress deprecation notices until we get to requiring at least PHP 8
// because union types, needed for the return types of read() and gc(), are
// not supported in PHP 7.
if (version_compare(MRBS_MIN_PHP_VERSION, '8.0.0') < 0)
{
  $old_level = error_reporting();
  error_reporting($old_level & ~E_DEPRECATED);
}
else
{
  trigger_error("This code can now be removed", E_USER_NOTICE);
}

// Use our own PHP session handling by storing sessions in the database.   This has three advantages:
//    (a) it's more secure, especially on shared servers
//    (b) it avoids problems with ordinary sessions not working because the PHP session save
//        directory is not writable
//    (c) it's more resilient in clustered environments

class SessionHandlerDb implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
  private static $table;

  public function __construct()
  {
    self::$table = _tbl('session');

    // We need to lock the session data while it is in use in order to prevent problems
    // with Ajax calls.  This happens with the default file session handler, but
    // in order to provide it with the DB session handler we need the ability to set multiple locks.
    if (!db()->supportsMultipleLocks())
    {
      throw new SessionHandlerDbException(
        "MRBS: database does not support multiple locks.",
        SessionHandlerDbException::NO_MULTIPLE_LOCKS
      );

    }

    if (!db()->table_exists(self::$table))
    {
      // We throw an exception if the table doesn't exist rather than returning FALSE, because in some
      // versions of PHP, eg 7.0.25, session_start() will throw a fatal error if it can't open
      // a session, rather than just returning FALSE as the documentation seems to suggest.   So
      // when a new SessionHandlerDb object is created we do it in a try/catch block.  [Note that
      // the exception can't be thrown on open() because a try/catch round session_start() won't
      // catch the exception - maybe because open() is a callback function??]
      //
      // This exception will also be thrown on the upgrade to database schema version 83, when the
      // table was renamed.
      throw new SessionHandlerDbException(
        "MRBS: session table does not exist",
        SessionHandlerDbException::TABLE_NOT_EXISTS
      );
    }
  }

  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function open($path, $name): bool
  {
    return true;
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function close(): bool
  {
    return true;
  }


  // Returns an encoded string of the read data. If nothing was read, it must
  // return an empty string. Note this value is returned internally to PHP for
  // processing.  Note that the data is base64_encoded in the database (otherwise
  // there were problems with PostgreSQL in storing some objects - needs further
  // investigation).
  // TODO  Objects with private or protected properties contain NULL bytes when encoded
  // TODO  by PHP and can't be written to a PostgreSQL text data type.  The column needs
  // TODO  to be converted to bytea.
  public function read($id)
  {
    // Acquire mutex to lock the session id.  When using the default file session handler
    // locks are obtained using flock().  We need to do something similar in order to prevent
    // problems with multiple Ajax requests writing to the S_SESSION variable while
    // another process is still using it.
    // Acquire a lock
    if (!db()->mutex_lock($id))
    {
      trigger_error("Failed to acquire a lock", E_USER_WARNING);
      return '';
    }

    try
    {
      $sql = "SELECT data
                FROM " . self::$table . "
               WHERE id=:id
               LIMIT 1";

      $result = db()->query_single_non_bool($sql, array(':id' => $id));
    }
    catch (DBException $e)
    {
      // If the exception is because the sessions table doesn't exist, then that's
      // probably because we're in the middle of the upgrade that creates the
      // sessions table, so just ignore it and return ''.   Otherwise re-throw
      // the exception.
      if (!db()->table_exists(self::$table))
      {
        return '';
      }
      throw $e;
    }

    return (!isset($result) || ($result === false)) ? '' : base64_decode($result);
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.  Note that the data is base64_encoded
  // in the database (see read() above).
  public function write($id, $data): bool
  {
    $query_data = array(
      'id' => $id,
      'data' => base64_encode($data),
      'access' => time()
    );

    $sql_params = array();
    $sql = db()->syntax_upsert($query_data, self::$table, $sql_params, 'id');

    // From the MySQL manual:
    // "With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 1 if the row is inserted as a
    // new row, 2 if an existing row is updated, and 0 if an existing row is set to its current values.
    // If you specify the CLIENT_FOUND_ROWS flag to the mysql_real_connect() C API function when connecting
    // to mysqld, the affected-rows value is 1 (not 0) if an existing row is set to its current values."
    return (0 < db()->command($sql, $sql_params));
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function destroy($id): bool
  {
    try
    {
      $sql = "DELETE FROM " . self::$table . " WHERE id=:id";
      db()->command($sql, array(':id' => $id));
      return true;
    }
    catch (\Exception $e)
    {
      return false;
    }
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function gc($max_lifetime)
  {
    $sql = "DELETE FROM " . self::$table . " WHERE access<:old";
    db()->command($sql, array(':old' => time() - $max_lifetime));
    return true;  // An exception will be thrown on error
  }


  // Need to provide this method to circumvent a bug in some versions of PHP.
  // See https://github.com/php/php-src/issues/9668
  public function validateId($id) : bool
  {
    // Acquire a lock
    if (!db()->mutex_lock($id))
    {
      trigger_error("Failed to acquire a lock", E_USER_WARNING);
      return false;
    }

    $sql = "SELECT COUNT(*)
              FROM " . self::$table . "
             WHERE id=:id
             LIMIT 1";

    return (db()->query1($sql, array(':id' => $id)) == 1);
  }


  // We only need to provide this method because it's part of SessionUpdateTimestampHandlerInterface
  // which we are implementing in order to provide validateId().
  public function updateTimestamp($id, $data) : bool
  {
    // Acquire a lock
    if (!db()->mutex_lock($id))
    {
      trigger_error("Failed to acquire a lock", E_USER_WARNING);
      return false;
    }

    try
    {
      $sql = "UPDATE " . self::$table . "
                 SET access=:access
               WHERE id=:id";

      $sql_params = array(
        ':id' => $id,
        ':access' => time()
      );

      $result = (1 === db()->command($sql, $sql_params));
    }
    catch(PDOException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      $result = false;
    }

    // Release the mutex lock
    db()->mutex_unlock($id);

    return $result;
  }

}


// Restore the original error reporting level
if (version_compare(MRBS_MIN_PHP_VERSION, '8.0.0') < 0)
{
  error_reporting($old_level);
}
else
{
  trigger_error("This code can now be removed", E_USER_NOTICE);
}
