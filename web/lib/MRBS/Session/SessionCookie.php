<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\SessionHandler\Cookie;
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


  public function init(int $lifetime) : void
  {
    $old_session_id = session_id();
    parent::init($lifetime);
    $new_session_id = session_id();
    SessionHandlerCookie::updateExpiry($new_session_id, ($lifetime === 0) ? 0 : time() + $lifetime);
    // Not entirely sure why this is necessary, but the old session data cookie is sometimes not deleted.
    if (($old_session_id !== '') && ($old_session_id !== $new_session_id))
    {
      Cookie::delete($old_session_id);
    }
  }


  public function logoffUser() : void
  {
    unset($_SESSION['user']);
    session_regenerate_id(true);
    session_write_close();
  }

}
