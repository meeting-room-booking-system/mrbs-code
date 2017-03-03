<?php
namespace MRBS;

/*****************************************************************************
*
*   File name       auth_config.inc
*
*   Description     Authenticate users from a list in config.inc.php.
*
*   Notes           To use this authentication scheme, set in config.inc.php:
*         $auth["type"]  = "config";
*
*          Then for each user, add an entry formatted as:
*         $auth["user"]["username"] = "userpassword";
*
*   History
*    Available in the source control system
*
******************************************************************************/

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
  global $auth;

  // Check if we do not have a username/password
  if(!isset($user) || !isset($pass) || strlen($pass)==0)
  {
    return false;
  }

  if ((isset($auth["user"][$user]) &&
       ($auth["user"][$user] == $pass)
        ) ||
      (isset($auth["user"][utf8_strtolower($user)]) &&
       ($auth["user"][utf8_strtolower($user)] == $pass)
        ))
  {
    return $user;    // User validated
  }

  return false;      // User unknown or password invalid
}

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user)
{
  global $auth;
  $admins = $auth['admin'];
  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }

  // Check if the user is can modify
  for($i = 0; isset($admins[$i]); $i++)
  {
    if(strcasecmp($user, $admins[$i]) == 0)
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

