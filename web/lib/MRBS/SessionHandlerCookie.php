<?php
declare(strict_types=1);
namespace MRBS;

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

class SessionHandlerCookie implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
  public function __construct()
  {
  }

  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function open($path, $name): bool
  {
    // Nothing to do here
    return true;
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function close(): bool
  {
    // Nothing to do here
    return true;
  }


  // Returns an encoded string of the read data. If nothing was read, it must
  // return an empty string. Note this value is returned internally to PHP for
  // processing.
  public function read($id)
  {
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function write($id, $data): bool
  {
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function destroy($id): bool
  {
  }


  // The return value (usually TRUE on success, FALSE on failure). Note this value is
  // returned internally to PHP for processing.
  public function gc($max_lifetime)
  {
    // Garbage collection is not required
    return true;
  }


  // Need to provide this method to circumvent a bug in some versions of PHP.
  // See https://github.com/php/php-src/issues/9668
  public function validateId($id) : bool
  {
  }


  // We only need to provide this method because it's part of SessionUpdateTimestampHandlerInterface
  // which we are implementing in order to provide validateId().
  public function updateTimestamp($id, $data) : bool
  {
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
