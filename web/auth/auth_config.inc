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

