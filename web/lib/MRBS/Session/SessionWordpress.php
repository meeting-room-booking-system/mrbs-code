<?php
namespace MRBS\Session;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';


class SessionWordpress extends Session
{
  
  public static function getUsername()
  {
    if (!is_user_logged_in())
    {
      return null;
    }
    
    $current_user = wp_get_current_user();
    return $current_user->user_login;
  }
}
