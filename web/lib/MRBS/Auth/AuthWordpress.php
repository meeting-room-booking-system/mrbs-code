<?php
namespace MRBS\Auth;

use \MRBS\User;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';


class AuthWordpress extends Auth
{
  
  public function getUser($username)
  {
    $wp_user = get_user_by('login', $username);
    
    if ($wp_user === false)
    {
      return null;
    }
    
    $user = new User($username);
    $user->display_name = $wp_user->display_name;
    $user->email = $wp_user->user_email;
    
    return $user;
  }
  
}