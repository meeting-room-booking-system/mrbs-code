<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';
require_once MRBS_ROOT . '/auth/functions_auth.inc';

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