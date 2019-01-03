<?php
namespace MRBS\Session;

class SessionCookie extends SessionWithLogin
{
  
  public function getUsername()
  {
    global $auth;
    
    static $cached_username = null;
    static $have_checked_cookie = false;

    if (!$have_checked_cookie)
    {
      $data = self::getCookie('SessionToken',
                              $auth['session_cookie']['hash_algorithm'],
                              $auth['session_cookie']['secret']);

      $cached_username = (isset($data['user'])) ? $data['user'] : null;
      $have_checked_cookie = true;
    }
    
    return $cached_username;
  }
  
  
  public function logonUser($username)
  {
    global $auth;
    
    if ($auth['session_cookie']['session_expire_time'] == 0)
    {
      $expiry_time = 0;
    }
    else
    {
      $expiry_time = time() + $auth['session_cookie']['session_expire_time'];
    }
       
    self::setCookie('SessionToken',
                    $auth['session_cookie']['hash_algorithm'],
                    $auth['session_cookie']['secret'],
                    array('user' => $username),
                    $expiry_time);
  }
  
  
  public function logoffUser()
  {
    // Delete cookie
    $cookie_path = \MRBS\get_cookie_path();
    setcookie("SessionToken", '', time()-42000, $cookie_path);
  }
  
  
  // Wrapper for setting cookies
  public static function setCookie($name, $hash_algorithm, $secret, array $data, $expiry=0)
  {
    global $auth;
    
    assert(!isset($data['expiry']), "'expiry' is a reserved data key");
    assert(!isset($data['ip']), "'ip' is a reserved data key");
    
    $data['expiry'] = $expiry;
    
    if ($auth['session_cookie']['include_ip'])
    {
      $data['ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }
    
    $json_data = json_encode($data);
    
    if (!function_exists('hash_hmac'))
    {
      fatal_error("It appears that your PHP has the hash functions " .
                  "disabled, which are required for the 'cookie' " .
                  "session scheme.");
    }
    
    $hash = hash_hmac($hash_algorithm,
                      $json_data,
                      $secret);

    setcookie($name,
              "${hash}_" . base64_encode($json_data),
              $expiry,
              \MRBS\get_cookie_path());
  }


  public static function getCookie($name, $hash_algorithm, $secret)
  {
    global $auth;
    
    if (empty($_COOKIE) || !isset($_COOKIE[$name]))
    {
      return array();
    }
    
    $token = unslashes($_COOKIE[$name]);
    
    if (!isset($token) || ($token === ''))
    {
      throw new \Exception('Token is invalid');
    }
    
    list($hash, $base64_data) = explode('_', $token);
    $json_data = base64_decode($base64_data);

    if (!function_exists('hash_hmac'))
    {
      fatal_error("It appears that your PHP has the hash functions ".
                  "disabled, which are required for the 'cookie' ".
                  "session scheme.");
    }
    
    if (hash_hmac($hash_algorithm,
                  $json_data,
                  $secret) != $hash)
    {
      throw new \Exception('Cookie has been tampered with or secret may have changed');
    }
                    
    $data = json_decode($json_data, true);
    
    // Check expiry time
    if (!isset($data['expiry']))
    {
      throw new \Exception('Cookie expiry time not set');
    }
    if (($data['expiry'] !== 0) && ($data['expiry'] <= time()))
    {
      // Cookie has expired
      return array();
    }
    
    // Check IP address
    if ($auth['session_cookie']['include_ip'])
    {
      if ((!isset($data['ip']) && !isset($_SERVER['REMOTE_ADDR'])) ||
           ($data['ip'] !== $_SERVER['REMOTE_ADDR']))
      {
        $message = 'IP address should be ' . $data['ip'] . ', but REMOTE_ADDR is ' .
                   $_SERVER['REMOTE_ADDR'];
        throw new \Exception($message);
      }
    }

    // Everything looks OK.  Clear the internal data keys and return the data.
    unset($data['ip']);
    unset($data['expiry']);
    
    return $data;
  }
}
