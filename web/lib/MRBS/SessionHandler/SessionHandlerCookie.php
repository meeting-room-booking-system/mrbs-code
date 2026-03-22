<?php
declare(strict_types=1);
namespace MRBS\SessionHandler;

use MRBS\Errors\Errors;
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


/**
 * A custom session handler that stores session data in a cookie.
 */
class SessionHandlerCookie implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{

  private const DEFAULT_HASH_ALGO = 'sha512';

  private $algo;
  private $include_ip;
  private $lifetime;
  private $secret;


  public function __construct(
    #[\SensitiveParameter]
    string $secret,
    string $algo = self::DEFAULT_HASH_ALGO,
    int $lifetime = 0,
    bool $include_ip = false
  )
  {
    $this->include_ip = $include_ip;
    $this->lifetime = $lifetime;
    $this->secret = $secret;
    if (in_array($algo, hash_hmac_algos()))
    {
      $this->algo = $algo;
    }
    else
    {
      $this->algo = self::DEFAULT_HASH_ALGO;
      $message = "Invalid hash algorithm '$algo' specified, using '" . self::DEFAULT_HASH_ALGO . "'sha512' instead";
      trigger_error($message, E_USER_WARNING);
    }
  }


  /**
   * @return bool The return value (usually TRUE on success, FALSE on failure). Note this value is
   * returned internally to PHP for processing.
   */
  public function open($path, $name): bool
  {
    // Nothing to do here
    return true;
  }


  /**
   * @return bool The return value (usually TRUE on success, FALSE on failure). Note this value is
   * returned internally to PHP for processing.
   */
  public function close(): bool
  {
    // Nothing to do here
    return true;
  }


  /**
   * @return string Returns an encoded string of the read data. If nothing was read, it must
   * return an empty string. Note this value is returned internally to PHP for processing.
   */
  public function read($id)
  {
    if (!$this->validateId($id))
    {
      return '';
    }

    $exploded = explode('_', $_COOKIE[$id]);
    if (count($exploded) !== 2)
    {
      return '';
    }
    list($hash, $base64_data) = $exploded;
    $data = base64_decode($base64_data);

    // Check the hash
    if (!hash_equals($hash, self::getHash($this->algo, $data, $this->secret)))
    {
      trigger_error('Cookie has been tampered with or secret may have changed', E_USER_WARNING);
      return '';
    }

    try
    {
      // Decode the data so that we can check the expiry time and IP address
      session_decode($data);
      $this->validateSession();
      // Everything looks OK.  Clear the internal data keys and return the data.
      unset($_SESSION['_ip']);
      unset($_SESSION['_expiry']);
      if (false === ($data = session_encode()))
      {
        throw new SessionHandlerCookieException('Failed to encode session data');
      }
      return $data;
    }
    catch (SessionHandlerCookieException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      // We have to unset the $_SESSION variable as well as return an empty string because
      // we have already called session_decode() above.
      unset($_SESSION);
      return '';
    }
  }


  /**
   * @return bool The return value (usually TRUE on success, FALSE on failure). Note this value is
   * returned internally to PHP for processing.
   */
  public function write($id, $data): bool
  {
    // Decode the data so that we can set the expiry time and IP address and then encode it again.
    session_decode($data);
    assert(!isset($_SESSION['_expiry']), "'_expiry' is a reserved data key");
    assert(!isset($_SESSION['_ip']), "'_ip' is a reserved data key");
    $expiry = ($this->lifetime === 0) ? 0 : time() + $this->lifetime;
    $_SESSION['_expiry'] = $expiry;
    if ($this->include_ip)
    {
      $_SESSION['_ip'] = $server['REMOTE_ADDR'] ?? null;
    }
    $data = session_encode();

    $hash = self::getHash($this->algo, $data, $this->secret);
    return Cookie::cookieSet($id, $hash . '_' . base64_encode($data), $expiry);
  }


  /**
   * @return bool The return value (usually TRUE on success, FALSE on failure). Note this value is
   * returned internally to PHP for processing.
   */
  public function destroy($id): bool
  {
    return Cookie::cookieSet($id, '', time() - 42000);
  }


  /**
   * @return bool The return value (usually TRUE on success, FALSE on failure). Note this value is
   * returned internally to PHP for processing.
   */
  public function gc($max_lifetime)
  {
    // Garbage collection is not required
    return true;
  }


  public function validateId($id) : bool
  {
    // Need to provide this method to circumvent a bug in some versions of PHP.
    // See https://github.com/php/php-src/issues/9668
    return isset($_COOKIE[$id]);
  }


  public function updateTimestamp($id, $data) : bool
  {
    // We only need to provide this method because it's part of SessionUpdateTimestampHandlerInterface
    // which we are implementing in order to provide validateId().
    return $this->write($id, $data);
  }


  private static function getHash(
    string $algo,
    string $data,
    #[\SensitiveParameter]
    string $key
  ) : string
  {
    if (!function_exists('hash_hmac'))
    {
      Errors::fatalError("It appears that your PHP has the hash functions " .
        "disabled, which are required for the 'cookie' " .
        "session scheme.");
    }

    return hash_hmac($algo, $data, $key);
  }


  private function validateSession() : void
  {
    global $server;

    // Check expiry time
    if (!isset($_SESSION['_expiry']))
    {
      throw new SessionHandlerCookieException('Cookie expiry time not set');
    }
    if (($_SESSION['_expiry'] !== 0) && ($_SESSION['_expiry'] <= time()))
    {
      throw new SessionHandlerCookieException('Cookie has expired');
    }

    // Check IP address
    if ($this->include_ip)
    {
      if (isset($_SESSION['_ip']))
      {
        if (!isset($server['REMOTE_ADDR']) || ($_SESSION['_ip'] !== $server['REMOTE_ADDR']))
        {
          throw new SessionHandlerCookieException('IP address has changed');
        }
      }
      else
      {
        if (isset($_SESSION['REMOTE_ADDR']))
        {
          throw new SessionHandlerCookieException('IP address should be NULL');
        }
      }
    }
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
