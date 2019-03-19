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
    $user->level = self::getUserLevel($wp_user);
    
    return $user;
  }


  // Checks whether validation of a user by email address is possible and
  // allowed.  In the case of WordPress, wp_authenticate() accepts either
  // a username or email address and so this function always returns true.
  public function canValidateByEmail()
  {
    return true;
  }

  
  private static function getUserLevel(\WP_User $wp_user)
  {
    global $auth;
    
    // cache the user levels for performance
    static $user_levels = array();
    
    // User not logged in, user level '0'
    // Shouldn't get here anyway because the type hint won't allow it,
    // but we'll check anyway for completeness
    if(!isset($wp_user) || ($wp_user === false))
    {
      return 0;
    }
    
    if (!isset($user_levels[$wp_user->login]))
    {
      // Check to see if one of the user's roles is an MRBS blacklisted role
      if (isset($auth['wordpress']['blacklisted_roles']) &&
          self::check_roles($wp_user, $auth['wordpress']['blacklisted_roles']))
      {
        $user_levels[$wp_user->login] = 0;
      }
      // Check to see if one of the user's roles is an MRBS admin role
      elseif (isset($auth['wordpress']['admin_roles']) &&
              self::check_roles($wp_user, $auth['wordpress']['admin_roles']))
      {
        $user_levels[$wp_user->login] = 2;
      }
      // Check to see if one of the user's roles is an MRBS user role
      elseif (isset($auth['wordpress']['user_roles']) &&
              self::check_roles($wp_user, $auth['wordpress']['user_roles']))
      {
        $user_levels[$wp_user->login] = 1;
      }
      // Everybody else is access level '0'
      else
      {
        $user_levels[$wp_user->login] = 0;
      }
    }
    
    return $user_levels[$wp_user->login];
  }
  
  
  // Checks to see whether any of the user's roles are contained in $mrbs_roles, which can be a
  // string or an array of strings.
  private static function check_roles(\WP_User $wp_user, $mrbs_roles)
  {
    if (!isset($mrbs_roles))
    {
      return false;
    }
    
    // Turn $mrbs_roles into an array if it isn't already
    $mrbs_roles = (array)$mrbs_roles;

    // Put the roles into the standard WordPress format
    $mrbs_roles = array_map('self::standardise_role_name', $mrbs_roles);
    
    return (count(array_intersect($wp_user->roles, $mrbs_roles)) > 0);
  }
  
  
  // Convert a WordPress role name to lowercase and replace spaces by underscores.
  // Example "MRBS Admin" -> "mrbs_admin"
  private static function standardise_role_name($role)
  {
    return str_replace(' ', '_', \MRBS\utf8_strtolower($role));
  }
  
}