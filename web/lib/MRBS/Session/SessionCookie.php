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

  public function __construct()
  {
    global $auth;

    // We have to use output buffering to ensure that the cookies are set before any other output is sent.
    ob_start();

    // Delete old-style cookies
    if (!empty($_COOKIE) && isset($_COOKIE["UserName"]))
    {
      setcookie('UserName', '', time()-42000, get_cookie_path());
    }

    // Set the session lifetime
    $this->lifetime = $auth['session_cookie']['session_expire_time'] ?? 0;

    parent::__construct();
  }


  protected function getSessionHandler() : SessionHandlerCookie
  {
    global $auth;

    // Set the session handler
    return new SessionHandlerCookie(
      $auth['session_cookie']['secret'],
      $auth['session_cookie']['hash_algorithm'],
      $auth['session_cookie']['include_ip']
    );
  }


  public function logoffUser() : void
  {
    unset($_SESSION['user']);
    session_regenerate_id(true);
    // Don't call session_write_close() as it seems to leave an extra cookie in the browser.  Not sure why.
  }

}
