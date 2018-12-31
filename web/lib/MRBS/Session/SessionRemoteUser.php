<?php
namespace MRBS\Session;

class SessionRemoteUser extends Session
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
}
