<?php
namespace MRBS;

/*
 * Authentication scheme that uses an Apache "auth basic" password file
 * for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["type"]   = "auth_basic";
 * $auth["auth_basic"]["passwd_file] = "/etc/httpd/htpasswd"; // Example
 * $auth["auth_basic"]["mode"] = "des"; // The mode of encryption used in
 *                                      // the file. Must be one of:
 *                                      // 'des', 'sha' or 'md5'.
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "username1";
 * $auth["admin"][] = "username2";
 *
 */

/* ~~JFL 2003/11/12 By default, use the http session mechanism */
if (!isset($auth['session'])) $auth['session']='http';


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

  if (!isset($auth["auth_basic"]["passwd_file"]))
  {
    error_log("auth_basic: passwd file not specified");
    return false;
  }
  if (!isset($auth["auth_basic"]["mode"]))
  {
    error_log("auth_basic: mode not specified");
    return false;
  }

  require_once "File/Passwd/Authbasic.php";

  $f = &File_Passwd::factory('Authbasic');
  $f->setFile($auth["auth_basic"]["passwd_file"]);
  $f->setMode($auth["auth_basic"]["mode"]);
  $f->load();

  if ($f->verifyPasswd($user, $pass) === true)
  {
    return $user;
  }

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

