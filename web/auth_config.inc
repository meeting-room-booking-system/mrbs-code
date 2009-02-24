<?php 
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
*         $auth["user"]["username"] == "userpassword";
*
*   History
*    Available in the source control system
*
******************************************************************************/

// $Id$

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
  global $auth;

  // Check if we do not have a username/password
  if(!isset($user) || !isset($pass) || strlen($pass)==0)
  {
    return 0;
  }

  if ((isset($auth["user"][$user]) &&
       ($auth["user"][$user] == $pass)
        ) ||
      (isset($auth["user"][strtolower($user)]) &&
       ($auth["user"][strtolower($user)] == $pass)
        ))
  {
    return 1; // User validated
  }

  return 0;      // User unknown or password invalid
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

?>
