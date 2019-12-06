<?php
namespace MRBS;

/*
 * Dummy authentication scheme, that accepts any user.
 * Useful for using mrbs without authentication,
 * or in combination with a session scheme that already enforces
 * its own authentication. For example that in IIS.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 *      $auth["type"]    = "none";
 *      $auth["session"] = your choice
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "nt_username1";
 * $auth["admin"][] = "nt_username2";
 *
 * See AUTHENTICATION  for more information.
 */

/**
 * Checks if the specified username/password pair are valid.
 *
 * For this authentication scheme always validates.
 *
 * @param   string  $user   The user name
 * @param   string  $pass   The password
 * @return  string  $user   Always valid
 */
function authValidateUser($user, $pass)
{
  return $user;
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

