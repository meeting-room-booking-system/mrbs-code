<?php
namespace MRBS\Session;


// Get user identity/password using the REMOTE_USER environment variable.
// Both identity and password equal the value of REMOTE_USER.
//
// To use this session scheme, set in config.inc.php:
//
//                    $auth['session']  = 'remote_user';
//                    $auth['type'] = 'none';
//
// If you want to display a login link, set in config.inc.php:
//
//                    $auth['remote_user']['login_link'] = '/login/link.html';
//
// If you want to display a logout link, set in config.inc.php:
//
//                    $auth['remote_user']['logout_link'] = '/logout/link.html';


use MRBS\User;
use function MRBS\auth;

class SessionRemoteUser extends SessionWithLogin
{

  // User is expected to already be authenticated by the web server, so do nothing
  public function authGet(?string $target_url=null, ?string $returl=null, ?string $error=null, bool $raw=false) : void
  {
  }


  public function getCurrentUser() : ?User
  {
    global $server;

    if ((!isset($server['REMOTE_USER'])) ||
        (!is_string($server['REMOTE_USER'])) ||
        (($server['REMOTE_USER'] === '')))
    {
      return null;
    }

    return auth()->getUser($server['REMOTE_USER']);
  }


  public function getLogonFormParams() : ?array
  {
    global $auth;

    if (isset($auth['remote_user']['login_link']))
    {
      return array(
          'action' => $auth['remote_user']['login_link'],
          'method' => 'get'
        );
    }
    else
    {
      return null;
    }
  }


  public function getLogoffFormParams() : ?array
  {
    global $auth;

    if (isset($auth['remote_user']['logout_link']))
    {
      return array(
          'action' => $auth['remote_user']['logout_link'],
          'method' => 'get'
        );
    }
    else
    {
      return null;
    }
  }
}
