<?php
namespace MRBS;

/*
 * Authentication scheme that uses an external script as the source
 * for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"]  = "MRBS";    // Or any other string
 * $auth["type"]   = "ext";
 * $auth["prog"]   = "authenticationprogram"; // The full path to the external
 *                                            // script
 * $auth["params"] = "<...>"                  // Parameters to pass to
 *                                            // the script, #USERNAME#
 *                                            // and #PASSWORD#
 *                                            // will be expanded to
 *                                            // the values typed by
 *                                            // the user.
 *
 *                                            // e.g.
 *                                            // "/etc/htpasswd #USERNAME# #PASSWORD#"
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "username1";
 * $auth["admin"][] = "username2";
 *
 */

/* ~~JFL 2003/11/12 By default, use the http session mechanism */
if (!isset($auth['session'])) $auth['session']='http';

function version_check($vercheck)
{
  $minver = str_replace(".","", $vercheck);
  $curver = str_replace(".","", phpversion());

  if($curver >= $minver)
  {
    return true;
  }
  return false;
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
   
  // Generate the command line
  $cmd = $auth["prog"] . ' ' . $auth["params"];
  $cmd = str_replace('#USERNAME#', escapeshellarg($user), $cmd);
  $cmd = str_replace('#PASSWORD#', escapeshellarg($pass), $cmd);
   
  // Run the program
  exec($cmd, $output, $ret);
   
  // If it succeeded, return success
  if ($ret == 0)
  {
    return $user;
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
  if(!isset($user))
  {
    return 0;
  }

  // Check if the user is can modify
  for($i = 0; $admins[$i]; $i++)
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

