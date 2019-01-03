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
      $data = \MRBS\mrbs_getcookie('SessionToken',
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
       
    \MRBS\mrbs_setcookie('SessionToken',
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
  
}
