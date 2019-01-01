<?php
namespace MRBS\Session;

class SessionHttp extends Session
{
  
  public static function authGet()
  {
    global $auth;
    
    header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
    header("HTTP/1.0 401 Unauthorized");
  }
  
  
  public static function getUsername()
  {
    // We save the results of the user validation so that we avoid any performance
    // penalties in authValidateUser, which can be severe if for example we are using
    // LDAP authentication
    static $authorised_user = null;

    if (isset($_SERVER['PHP_AUTH_USER']))
    {
      $user = \MRBS\unslashes($_SERVER['PHP_AUTH_USER']);

      if ((isset($authorised_user) && ($authorised_user == $user)) ||
          (\MRBS\authValidateUser($user, self::getAuthPassword()) !== false))
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
  
  
  public static function getLogoffFormParams()
  {
    // Just return NULL - you can't logoff
    // (well, there are ways of achieving a logoff but we haven't implemrnted them)
  }
  
  
  private static function getAuthPassword()
  {
    if (isset($_SERVER['PHP_AUTH_PW']))
    {
      return \MRBS\unslashes($_SERVER['PHP_AUTH_PW']);
    }
    else
    {
      return null;
    }
  }
}
