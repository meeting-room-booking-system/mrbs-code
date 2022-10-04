<?php

namespace MRBS;

use PDOException;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;

// Suppress deprecation notices until we get to requiring at least PHP 8
// because union types, needed for the return types of read() and gc(), are
// not supported in PHP 7.  Using the #[\ReturnTypeWillChange] attribute
// does not help because that was only introduced in PHP 8.1.
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
    self::$table = _tbl('sessions');

    if (!db()->table_exists(self::$table))
    {
      // We throw an exception if the table doesn't exist rather than returning FALSE, because in some
      // versions of PHP, eg 7.0.25, session_start() will throw a fatal error if it can't open
      // a session, rather than just returning FALSE as the documentation seems to suggest.   So
      // when a new SessionHandlerDb object is created we do it in a try/catch block.  [Note that
      // the exception can't be thrown on open() because a try/catch round session_start() won't
      // catch the exception - maybe because open() is a callback function??]
      throw new \Exception("MRBS: session table does not exist");
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
  // processing.
  public function read($id)
  {
    global $dbsys;

    try
    {
      $sql = "SELECT data
                FROM " . self::$table . "
               WHERE id=:id
               LIMIT 1";

      $result = db()->query1($sql, array(':id' => $id));
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

    if (!isset($result) || ($result === -1))
    {
      return '';
    }

    // TODO: fix this properly
    // In PostgreSQL we store the session base64 encoded.  That's because the session data string (encoded by PHP)
    // can contain NULL bytes when the User object has protected properties.  The solution is probably to convert
    // the data column in PostgreSQL to be bytea rather than text.  However this doesn't seem to work for some reason -
    // no doubt soluble - and also upgrading the database is complicated while the roles branch is still under
    // development and there are two sets of upgrades to be merged.  So for the moment we have this rather inelegant
    // workaround.
    if ($dbsys == 'pgsql')
    {
      $decoded = base64_decode($result, true);
      // Test to see if the data is base64 encoded so that we can handle session data written before this change.
      if (($decoded !== false) && (base64_encode($decoded) === $result))
      {
        return $decoded;
      }
    }

    return $result;
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function write($id, $data): bool
  {
    global $dbsys;

    // See comment in read()
    if ($dbsys == 'pgsql')
    {
      $data = base64_encode($data);
    }

    $sql = "SELECT COUNT(*) FROM " . self::$table . " WHERE id=:id LIMIT 1";
    $rows = db()->query1($sql, array(':id' => $id));

    if ($rows > 0)
    {
      $sql = "UPDATE " . self::$table . "
                 SET data=:data, access=:access
               WHERE id=:id";
    }
    else
    {
      // The id didn't exist so we have to INSERT it (we couldn't use
      // REPLACE INTO because we have to cater for both MySQL and PostgreSQL)
      $sql = "INSERT INTO " . self::$table . "
                          (id, data, access)
                   VALUES (:id, :data, :access)";
    }

    $sql_params = array(':id' => $id,
                        ':data' => $data,
                        ':access' => time());

    db()->command($sql, $sql_params);

    return true;
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
    try
    {
      $sql = "UPDATE " . self::$table . "
                 SET access=:access
               WHERE id=:id";

      $sql_params = array(
          ':id' => $id,
          ':access' => time()
        );

      db()->command($sql, $sql_params);
    }
    catch(PDOException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return false;
    }

    return true;
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
