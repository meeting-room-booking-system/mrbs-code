<?php
namespace MRBS\Session;

// Get user identity using the HTTP basic authentication


class SessionHttp extends SessionWithLogin
{
  
  public function authGet($target_url=null, $error=null, $raw=false)
  {
    global $auth;
    
    header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
    header("HTTP/1.0 401 Unauthorized");
  }
  
  
  public function getUsername()
  {
    global $server;
    
    // We save the results of the user validation so that we avoid any performance
    // penalties in auth()->validateUser, which can be severe if for example we are using
    // LDAP authentication
    static $authorised_user = null;

    if (isset($server['PHP_AUTH_USER']))
    {
      $user = $server['PHP_AUTH_USER'];

      if ((isset($authorised_user) && ($authorised_user == $user)) ||
          (\MRBS\auth()->validateUser($user, self::getAuthPassword()) !== false))
      {
        $authorised_user = $user;
      }
      else
      {
        $authorised_user = null;
      }
    }
    else
    {
      $authorised_user = null;
    }
    
    return $authorised_user;
  }
  
  
  public function getLogoffFormParams()
  {
    // Just return NULL - you can't logoff
    // (well, there are ways of achieving a logoff but we haven't implemrnted them)
  }
  
  
  private static function getAuthPassword()
  {
    global $server;
    
    return (isset($server['PHP_AUTH_PW'])) ? $server['PHP_AUTH_PW'] : null;
  }
}
