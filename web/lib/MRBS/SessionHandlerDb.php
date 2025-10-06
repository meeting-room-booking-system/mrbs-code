<?php
declare(strict_types=1);
namespace MRBS;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use MRBS\DB\DBException;
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
//
// The class also encrypts the session data, using a random key which is stored in a cookie (based on
// https://github.com/ezimuel/PHP-Secure-Session).

class SessionHandlerDb implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
  private const KEY_COOKIE_PREFIX = 'KEY_';

  private $key;
  private static $table;

  public function __construct()
  {
    self::$table = _tbl('sessions');

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
    try {
      $this->key = $this->getKey(self::KEY_COOKIE_PREFIX . $name);
    }
    catch (\Exception $e) {
      trigger_error("Failed to get key: " . $e->getMessage(), E_USER_WARNING);
      // The message below is sometimes seen. Log the key cookie value.  It's usually because the cookie value is
      // 'deleted'.  This is because, as the PHP manual says in https://www.php.net/manual/en/function.setcookie.php,
      // "Cookies must be deleted with the same parameters as they were set with. If the value argument is an empty
      // string, and all other arguments match a previous call to setcookie(), then the cookie with the specified name
      // will be deleted from the remote client. This is internally achieved by setting value to 'deleted' and
      // expiration time in the past."
      // However it's not clear why the cookie is being read, given that it has been deleted.   And as it is being read
      // can we do some kind of retry and set the key cookie with a proper key so that session_start() does not fail?
      if (str_contains($e->getMessage(), 'Encoding::hexToBin() input is not a hex string'))
      {
        $key_cookie = $_COOKIE[self::KEY_COOKIE_PREFIX . $name];
        trigger_error("Key cookie: $key_cookie");
      }
      return false;
    }

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

      $result = db()->query_scalar_non_bool($sql, array(':id' => $id));
    }
    catch (DBException $e)
    {
      // If the exception is because the sessions table doesn't exist, then that's
      // probably because we're in the middle of the upgrade that creates the
      // sessions table, so just ignore it and return ''.   Otherwise, re-throw
      // the exception.
      if (!db()->table_exists(self::$table))
      {
        return '';
      }
      throw $e;
    }

    if (!isset($result) || ($result === false))
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
    // NOTE: this step is probably not necessary anymore, now that the session data is encrypted.
    if ($dbsys == 'pgsql')
    {
      $decoded = base64_decode($result, true);
      // Test to see if the data is base64 encoded so that we can handle session data written before this change.
      if (($decoded !== false) && (base64_encode($decoded) === $result))
      {
        $result = $decoded;
      }
    }

    try {
      $result = Crypto::decrypt($result, $this->key);
    }
    catch (WrongKeyOrModifiedCiphertextException $e) {
      $message = $e->getMessage();
      if (!str_contains($message, 'Ciphertext has invalid hex encoding'))
      {
        // This exception can be caused by (1) the wrong key being used, or (2) the cipher text having
        // been modified or truncated.  The message will generally be "Integrity check failed".  None
        // of these should normally happen.  If the cipher text has been truncated, because it was too
        // long for the database column, then we should have seen an SQL error when the session data
        // was written.
        trigger_error(get_class($e) . ': ' . $message, E_USER_WARNING);
        $result = '';
      }
      // Otherwise do nothing.  This is to handle the case where we are reading old session data before
      // encryption was introduced, when the session data will almost certainly contain non-hex characters.
      // So just return the undecrypted data from the database (because it was never encrypted in the first
      // place).
    }
    catch (\Exception $e) {
      trigger_error("Failed to decrypt session data: " . $e->getMessage(), E_USER_WARNING);
      $result = '';
    }

    return $result;
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function write($id, $data): bool
  {
    global $dbsys;

    try {
      $data = Crypto::encrypt($data, $this->key);
    }
    catch (\Exception $e) {
      trigger_error("Failed to encrypt session data: " . $e->getMessage(), E_USER_WARNING);
      return false;
    }

    // See comment in read()
    if ($dbsys == 'pgsql')
    {
      $data = base64_encode($data);
    }

    $query_data = array(
      'id' => $id,
      'data' => $data,
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


  // Delete the key cookie, but only if the expiry is not zero.
  public static function deleteKeyCookie() : void
  {
    if (false === ($name = session_name()))
    {
      return;
    }

    if (session_get_cookie_params()['lifetime'] !== 0)
    {
      $name = self::KEY_COOKIE_PREFIX . $name;
      unset($_COOKIE[$name]);
      self::setKeyCookie($name, '', time() - 42000);
    }
  }


  // Regenerate the key cookie, setting its expiry to be the same as the session cookie's.
  public static function regenerateKeyCookie() : void
  {
    // No need to do anything if we can't get a session name, or if the expiry is zero (browser close).
    if ((false === ($name = session_name())) || (0 === ($session_lifetime = session_get_cookie_params()['lifetime'])))
    {
      return;
    }

    // And no need to do anything if we can't get a cookie value.
    $name = self::KEY_COOKIE_PREFIX . $name;
    if (!isset($_COOKIE[$name]))
    {
      return;
    }

    // But otherwise, set the key cookie lifetime to be the same as the session cookie's.
    self::setKeyCookie($name, $_COOKIE[$name], time() + $session_lifetime);
  }


  private static function setKeyCookie(string $name, string $value, int $expires) : void
  {
    // Use the same cookie params as for the session cookie.
    $cookie_params = session_get_cookie_params();
    setcookie(
      $name,
      $value,
      $expires,
      $cookie_params['path'],
      $cookie_params['domain'],
      $cookie_params['secure'],
      $cookie_params['httponly']
    );
  }


  private function getKey(string $name) : Key
  {
    // Get the key from the cookie, or if there isn't one create a random key and
    // store it in the cookie.
    if (empty($_COOKIE[$name]))
    {
      $key = Key::createNewRandomKey();
      $ascii_key = $key->saveToAsciiSafeString();
      $session_lifetime = session_get_cookie_params()['lifetime'];
      // Set the expiry to be the same as the session cookie expiry, or else 0 for browser close
      self::setKeyCookie($name, $ascii_key, ($session_lifetime > 0) ? time() + $session_lifetime : 0);
      $_COOKIE[$name] = $ascii_key;
    }
    else
    {
      $key = Key::loadFromAsciiSafeString($_COOKIE[$name]);
    }

    return $key;
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
