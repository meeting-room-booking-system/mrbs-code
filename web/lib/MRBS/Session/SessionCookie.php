<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\SessionHandler\SessionHandlerCookie;
use SessionHandler;
use function MRBS\get_cookie_path;

/**
 * Manage sessions via cookies stored in the client browser.
 */
class SessionCookie extends SessionPhp
{

  private static $cookie_path;


  public function __construct()
  {
    global $auth;

    self::$cookie_path = get_cookie_path();

    // Delete old-style cookies
    if (!empty($_COOKIE) && isset($_COOKIE["UserName"]))
    {
      setcookie('UserName', '', time()-42000, self::$cookie_path);
    }

    $this->lifetime = $auth['session_cookie']['session_expire_time'] ?? 0;

    parent::__construct();
  }


  public function init(int $lifetime) : void
  {
    global $auth;

    if (session_status() === PHP_SESSION_ACTIVE)
    {
      // We've already started sessions
      return;
    }

    // We have to use output buffering to ensure that the cookie is set before any other output is sent.
    ob_start();

    $handler = new SessionHandlerCookie(
      $auth['session_cookie']['secret'],
      $auth['session_cookie']['hash_algorithm'],
      $lifetime,
      self::$cookie_path,
      $auth['session_cookie']['include_ip']
    );
    session_set_save_handler($handler, true);

    if (false === session_start())
    {
      $message = "Could not start DB sessions, trying ordinary PHP sessions.";
      trigger_error($message, E_USER_WARNING);
      // Restore the default PHP session handler and try again.
      $handler = new SessionHandler();
      session_set_save_handler($handler, true);
      if (false === session_start())
      {
        throw new \Exception("Could not start sessions");
      }
    }
  }

}
