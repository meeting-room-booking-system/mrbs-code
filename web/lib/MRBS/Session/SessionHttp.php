<?php
namespace MRBS\Session;

// Get user identity using the HTTP basic authentication


class SessionHttp extends SessionWithLogin
{

  public function authGet($target_url=null, $returl=null, $error=null, $raw=false)
  {
    global $auth;

    header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
    header("HTTP/1.0 401 Unauthorized");
  }


  public function getCurrentUser()
  {
    global $server;

    if (!isset($server['PHP_AUTH_USER']))
    {
      return null;
    }

    // Trim any whitespace because PHP_AUTH_USER can contain it.
    $php_auth_user = trim($server['PHP_AUTH_USER']);

    if ($php_auth_user === '')
    {
      return null;
    }

    if (\MRBS\auth()->validateUser($php_auth_user, self::getAuthPassword()) === false)
    {
      return null;
    }

    return \MRBS\auth()->getUser($php_auth_user);
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
