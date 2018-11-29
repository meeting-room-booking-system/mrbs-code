<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';

// Check we've got the right session scheme
if ($auth['session'] !== 'wordpress')
{
  die("MRBS configuration error: auth_wordpress needs \$auth['session'] set to 'wordpress'.");
}


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
function authValidateUser($user, $pass)
{
  return (is_wp_error(wp_authenticate($user, $pass))) ? false : $user;
}


// Checks whether validation of a user by email address is possible and
// allowed.  In the case of WordPress, wp_authenticate() accepts either
// a username or email address and so this function always returns true.
function canValidateByEmail()
{
  return true;
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

  // Check to see if the user has admin rights
  if (isset($auth['wordpress']['admin_roles']))
  {
    // Check to see if one of the user's roles is an MRBS admin role
    if (check_roles($wp_user, $auth['wordpress']['admin_roles']))
    {
      $user_levels[$user] = 2;
      return $user_levels[$user];
    }
  }
  
  // Check to see if the user has user rights
  if (isset($auth['wordpress']['user_roles']))
  {
    // Check to see if one of the user's roles is an MRBS user role
    if (check_roles($wp_user, $auth['wordpress']['user_roles']))
    {
      $user_levels[$user] = 1;
      return $user_levels[$user];
    }
  }
  
  // Everybody else is access level '0'
  $user_levels[$user] = 0;
  
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


// Checks to see whether any of the user's roles are contained in $mrbs_roles, which can be a
// string or an array of strings.
function check_roles(\WP_User $wp_user, $mrbs_roles)
{
  if (!isset($mrbs_roles))
  {
    return false;
  }
  
  // Turn $mrbs_roles into an array if it isn't already
  $mrbs_roles = (array)$mrbs_roles;

  // Put the roles into the standard WordPress format
  $mrbs_roles = array_map(__NAMESPACE__ . "\\standardise_role_name", $mrbs_roles);
  
  return (count(array_intersect($wp_user->roles, $mrbs_roles)) > 0);
}