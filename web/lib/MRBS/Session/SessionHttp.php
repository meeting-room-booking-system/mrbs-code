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
  
  
  public function getCurrentUser()
  {
    global $server;
    
    if (!isset($server['PHP_AUTH_USER']) || ($server['PHP_AUTH_USER'] === ''))
    {
      return null;
    }
    
    if (\MRBS\auth()->validateUser($server['PHP_AUTH_USER'], self::getAuthPassword()) === false)
    {
      return null;
    }
    
    return \MRBS\auth()->getUser($server['PHP_AUTH_USER']);
  }
  
  
  public function getLogoffFormParams()
  {
    // Just return NULL - you can't logoff
    // (well, there are ways of achieving a logoff but we haven't implemented them)
  }
  
  
  private static function getAuthPassword()
  {
    global $server;
    
    return (isset($server['PHP_AUTH_PW'])) ? $server['PHP_AUTH_PW'] : null;
  }
}
