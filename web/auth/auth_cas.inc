<?php
namespace MRBS;

use \phpCAS;

// Initialise CAS
if ($auth['cas']['debug'])
{
  phpCAS::setDebug();
  phpCAS::setVerbose(true);
}

phpCAS::client(CAS_VERSION_2_0, $auth['cas']['host'], $auth['cas']['port'], $auth['cas']['context']);

if ($auth['cas']['no_server_validation'])
{
  phpCAS::setNoCasServerValidation();
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
  if (phpCAS::isAuthenticated())
  {
    return $user;
  } 
  
  return false;
}


/* authGetUserLevel($user)
 * 
 * Determines the user's access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The user's access level
 */
function authGetUserLevel($user)
{
  global $auth;

  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }

  // Check whether the user is an admin
  foreach ($auth['admin'] as $admin)
  {
    if(strcasecmp($user, $admin) === 0)
    {
      return 2;
    }
  }

  // Everybody else is access level '1'
  return 1;
}


// Gets the user's email address.   Returns an empty
// string if one can't be found
function authGetUserEmail($user)
{
  if (!isset($user) || $user === '')
  {
    return '';
  }
  
  return get_default_email($user);
}


?>
