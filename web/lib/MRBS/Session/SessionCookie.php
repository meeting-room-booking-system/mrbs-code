<?php
namespace MRBS\Session;

class SessionCookie extends SessionWithLogin
{
  
  public function getUsername()
  {
    global $auth;
    
    static $cached_username = null;
    static $have_checked_cookie = false;

    if ($have_checked_cookie)
    {
      return $cached_username;
    }
    
    // If the cached username isn't set, we have to decode the cookie, but
    // first set the flag, so we will only do this once
    $have_checked_cookie = true;

    if (!empty($_COOKIE) && isset($_COOKIE["SessionToken"]))
    {
      $token = \MRBS\unslashes($_COOKIE["SessionToken"]);
    }

    if (isset($token) && ($token != ""))
    {
      list($hash, $base64_data) = explode("_", $token);
      
      $json_data = base64_decode($base64_data);

      if (!function_exists('hash_hmac'))
      {
        fatal_error("It appears that your PHP has the hash functions ".
                    "disabled, which are required for the 'cookie' ".
                    "session scheme.");
      }
      if (hash_hmac(
                    $auth["session_cookie"]["hash_algorithm"],
                    $json_data,
                    $auth['session_cookie']['secret']
                   ) == $hash)
      {
        $session_data = json_decode($json_data, true);
            
        /* Check for valid session data */
        if (isset($session_data['user']) &&
            isset($session_data['expiry']))
        {
          // Have basic data

          if ((($auth["session_cookie"]["session_expire_time"] == 0) &&
               ($session_data['expiry'] == 0)) ||
              ($session_data['expiry'] > time()))
          {
            // Expiry is OK
            
            if (!isset($session_data['ip']) ||
                ($session_data['ip'] == $_SERVER['REMOTE_ADDR']))
            {
              // IP is OK
              $cached_username = $session_data['user'];
            }
          }
        }
        else
        {
          // Basic data checks failed
        }
      }
      else
      {
        error_log("Token is invalid, cookie has been tampered with or secret may have changed");
      }
    }

    return $cached_username;
  }
  
  
  public function logonUser($username)
  {
    global $auth;

    $cookie_path = \MRBS\get_cookie_path();
    
    if ($auth['session_cookie']['session_expire_time'] == 0)
    {
      $expiry_time = 0;
    }
    else
    {
      $expiry_time = time() + $auth['session_cookie']['session_expire_time'];
    }

    $session_data = array();
    $session_data['user'] = $username;
    $session_data['expiry'] = $expiry_time;
    
    if ($auth['session_cookie']['include_ip'])
    {
      $session_data['ip'] = $_SERVER['REMOTE_ADDR'];
    }
    
    $json_data = json_encode($session_data);
    
    if (!function_exists('hash_hmac'))
    {
      fatal_error("It appears that your PHP has the hash functions " .
                  "disabled, which are required for the 'cookie' " .
                  "session scheme.");
    }
    
    $hash = hash_hmac($auth['session_cookie']['hash_algorithm'],
                      $json_data,
                      $auth['session_cookie']['secret']);

    setcookie("SessionToken",
              "${hash}_" . base64_encode($json_data),
              $expiry_time,
              $cookie_path);
  }
  
  
  public function logoffUser()
  {
    // Delete cookie
    $cookie_path = \MRBS\get_cookie_path();
    setcookie("SessionToken", '', time()-42000, $cookie_path);
  }
  
}
