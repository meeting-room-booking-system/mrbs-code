<?php
namespace MRBS\Auth;

use \MRBS\User;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';


class AuthWordpress extends Auth
{

  /* authValidateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The user name
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public function validateUser($user, $pass)
  {
    return (is_wp_error(wp_authenticate($user, $pass))) ? false : $user;
  }


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


  // Return an array of MRBS users, indexed by 'username' and 'display_name'
  public function getUsernames()
  {
    global $auth;

    $result = array();

    // We are only interested in MRBS users and admins
    $mrbs_roles = array_merge((array)$auth['wordpress']['admin_roles'],
      (array)$auth['wordpress']['user_roles']);

    // The 'role__in' argument to get_users() is only supported in Wordpress >= 4.4.
    // Before that we have to do it one role at a time with the 'role' argument.
    $can_use_role__in = version_compare(get_bloginfo('version'), '4.4', '>=');

    $args = array('fields'  => array('user_login', 'display_name'),
                  'orderby' => 'display_name',
                  'order'   => 'ASC');

    if ($can_use_role__in)
    {
      $args['role__in'] = $mrbs_roles;
      $users = get_users($args);
    }
    else
    {

      $users = array();
      $mrbs_roles = array_unique($mrbs_roles);
      foreach ($mrbs_roles as $mrbs_role)
      {
        $args['role'] = $mrbs_role;
        $users = array_merge($users, get_users($args));
      }
      // Remove duplicate users
      $users = array_map('unserialize', array_unique(array_map('serialize', $users)));
    }

    foreach ($users as $user)
    {
      $result[] = array('username'     => $user->user_login,
                        'display_name' => $user->display_name);
    }

    if (!$can_use_role__in)
    {
      // We need to sort the users in this case as we've only got an array of merged
      // sorted arrays.  So the small arrays are sorted but the merged array is not.
      self::sortUsers($result);
    }

    return $result;
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
