<?php

define('WP_USE_THEMES', false);
require_once MRBS_ROOT . '/'. $auth['wordpress']['rel_path'] . '/wp-load.php';

/* authValidateUser($user, $pass)
 * 
 * Checks if the specified username/password pair are valid
 * 
 * $user  - The user name
 * $pass  - The password
 * 
 * Returns:
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */

function authValidateUser($user, $pass)
{
  return !is_wp_error(wp_authenticate($user, $pass));
}


/* authGetUserLevel($user)
 * 
 * Determines the user's access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user)
{
  global $auth;
  
  // cache the user levels for performance
  static $user_levels = array();
  
  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }

  if (isset($user_levels[$user]))
  {
    return $user_levels[$user];
  }
  
  $wp_user = get_user_by('login', $user);
  if ($wp_user === FALSE)
  {
    return 0;
  }

  if (isset($auth['wordpress']['admin_roles']))
  {
    // Turn the admin_roles into an array if it isn't alreadt
    $admin_roles = $auth['wordpress']['admin_roles'];
    if (!is_array($admin_roles))
    {
      $admin_roles = array($admin_roles);
    }
    // Put the roles into the standard WordPress format
    $admin_roles = array_map('standardise_role_name', $admin_roles);
    
    // Check to see if one of the user's roles is an admin role
    foreach($wp_user->roles as $user_role)
    {
      if (in_array($user_role, $admin_roles))
      {
        $user_levels[$user] = 2;
        return $user_levels[$user];
      }
    }
  }
  
  $user_levels[$user] = 1;
  return $user_levels[$user];
}


// Gets the user's email address.   Returns an empty
// string if one can't be found
function authGetUserEmail($user)
{
  $wp_user = get_user_by('login', $user);
  if ($wp_user === FALSE)
  {
    return '';
  }
  
  return (isset($wp_user->user_email)) ? $wp_user->user_email : '';
}


// Convert a WordPress role name to lowercase and replace spaces by underscores.
// Example "MRBS Admin" -> "mrbs_admin"
function standardise_role_name($role)
{
  return str_replace(' ', '_', utf8_strtolower($role));
}