<?php
declare (strict_types = 1);
namespace MRBS\SessionHandler;

use SessionHandlerInterface;
use SessionIdInterface;
use SessionUpdateTimestampHandlerInterface;

abstract class SessionHandlerAbstract implements SessionHandlerInterface, SessionIdInterface, SessionUpdateTimestampHandlerInterface
{
  /**
   * @see https://www.php.net/manual/en/sessionhandlerinterface.close.php
   *
   * @return bool The return value (usually `TRUE` on success, `FALSE` on failure). Note this value is returned
   * internally to PHP for processing.
   */
  abstract public function close(): bool;

  /**
   * @see https://www.php.net/manual/en/sessionhandlerinterface.destroy.php
   *
   * @param $id string The session ID being destroyed.
   * @return bool The return value (usually `TRUE` on success, `FALSE` on failure). Note this value is returned
   * internally to PHP for processing.
   */
  abstract public function destroy($id): bool;

  /**
   * @see https://www.php.net/manual/en/sessionhandlerinterface.gc.php
   *
   * @param $max_lifetime int Sessions that have not updated for the last `max_lifetime` seconds will be removed.
   * @return int|false Returns the number of deleted sessions on success, or `false` on failure. Note this value is
   * returned internally to PHP for processing.
   */
  abstract public function gc($max_lifetime);

  /**
   * @see https://www.php.net/manual/en/sessionhandlerinterface.open.php
   *
   * @param $path string The path where to store/retrieve the session.
   * @param $name string The session name.
   * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned
   * internally to PHP for processing.
   */
  abstract public function open($path, $name): bool;

  /**
   * @see https://www.php.net/manual/en/sessionhandlerinterface.read.php
   *
   * @param $id string The session id.
   * @return string|false Returns an encoded string of the read data. If nothing was read, it must return `false`. Note
   * this value is returned internally to PHP for processing.
   */
  abstract public function read($id);

  /**
   * @see https://www.php.net/manual/en/sessionhandlerinterface.write.php
   *
   * @param $id string The session id.
   * @param $data string The encoded session data. This data is the result of the PHP internally encoding the
   * `$_SESSION` superglobal to a serialized string and passing it as this parameter. Please note sessions use an
   * alternative serialization method.
   * @return bool The return value (usually `true` on success, `false` on failure). Note this value is returned
   * internally to PHP for processing.
   */
  abstract public function write($id, $data): bool;

  /**
   * @see https://www.php.net/manual/en/sessionidinterface.create-sid.php
   *
   * @return string The new session ID. Note that this value is returned internally to PHP for processing.
   */
  public function create_sid(): string
  {
    // This method will be required in PHP 9.0 and its absence triggers a warning in PHP 8.6. We don't need
    // to do anything special though; just call the standard PHP function session_create_id().
    $attempts = 0;
    $max_attempts = 5;
    while ($attempts < $max_attempts)
    {
      $id = session_create_id();
      // If this session id is not already in use (ie not valid) then return it.
      if (!$this->validateId($id))
      {
        return $id;
      }
      // Otherwise keep on trying.
      $attempts++;
    }

    // It's extremely unlikely that even two attempts will be necessary, but, just in case, we guard
    // against a possible infinite loop.
    throw new \Exception("Could not create a unique session id after $max_attempts attempts.");
  }

  /**
   * @see https://www.php.net/manual/en/sessionupdatetimestamphandlerinterface.updatetimestamp.php
   *
   * @param $id string The session ID.
   * @param $data string The session data. The serialized session data may be used to determine whether the session data
   * has changed and therefore should be updated.
   * @return bool Returns `true` if the timestamp was updated, `false` otherwise. Note that this value is returned
   * internally to PHP for processing.
   */
  abstract public function updateTimestamp($id, $data) : bool;

  /**
   * @see https://www.php.net/manual/en/sessionupdatetimestamphandlerinterface.validateid.php
   *
   * @param $id string The session ID.
   * @return bool Returns `true` for valid ID, `false` otherwise. Note that this value is returned internally to PHP for
   * processing.
   */
  abstract public function validateId($id) : bool;
}
