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


class SessionRemoteUser extends SessionWithLogin
{

  // User is expected to already be authenticated by the web server, so do nothing
  public static function authGet()
  {
  }
  
  
  public static function getUsername()
  {
    if ((!isset($_SERVER['REMOTE_USER'])) ||
        (!is_string($_SERVER['REMOTE_USER'])) ||
        (empty($_SERVER['REMOTE_USER'])))
    {
      return null;
    } 
    else
    {
      return $_SERVER['REMOTE_USER'];
    }
  }
  
  
  public static function getLogonFormParams()
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
  
  
  public static function getLogoffFormParams()
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
