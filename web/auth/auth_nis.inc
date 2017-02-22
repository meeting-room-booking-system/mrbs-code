<?php
namespace MRBS;

/*
 * Authentication scheme that uses NIS as the source for user
 * authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    // Or any other string
 * $auth["type"]  = "nis";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "nisuser1";
 * $auth["admin"][] = "nisuser2";
 *
 */

/* ~~JFL 2003/11/12 By default, use the http session mechanism */
if (!isset($auth['session']))
{
  $auth['session']='http';
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
  global $auth;

  // Check if we do not have a username/password
  if(!isset($user) || !isset($pass))
  {
    return false;
  }

  // untaint $user
  //preg_match("/(^\w+).*/",$user,$regs);
  //$user = $regs[1];

  $rs = yp_match (yp_get_default_domain(), "passwd.byname", $user);
  if ( ! empty ( $rs ) )
  {
    $rs = explode ( ":", $rs );
    if ( $rs[1] == crypt ( $pass, $rs[1] ) )
    {
      return $user;
    }
    else
    {
      return false;
    }
  }

  // return failure
  return false;
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
  if (!isset($user))
  {
    return 0;
  }

  // Check if the user is can modify
  for ($i = 0; $admins[$i]; $i++)
  {
    if (strcasecmp($user, $admins[$i]) == 0)
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

