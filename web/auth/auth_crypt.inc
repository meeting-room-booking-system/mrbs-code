<?php

/* $Id$
 *
 * Authentication scheme that uses an external script as the source
 * for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["type"]   = "crypt";
 * $auth["crypt"]["passwd_file] = "/etc/httpd/mrbs_passwd";
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
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */
function authValidateUser($user, $pass)
{
  global $auth;
   
  // Check if we do not have a username/password
  if(!isset($user) || !isset($pass))
  {
    return 0;
  }

  if (!isset($auth["crypt"]["passwd_file"]))
  {
    error_log("auth_crypt: passwd file not specified");
    return 0;
  }
  $fh = fopen($auth["crypt"]["passwd_file"], "r");
  if (!$fh)
  {
    error_log("auth_crypt: couldn't open passwd file\n");
    return 0;
  }
  $ret = 0; // Default to failure
  while ($line = fgets($fh))
  {
    if (preg_match("/^\Q$user\E:(.*)/", $line, $matches))
    {
      if (crypt($pass, $matches[1]) == $matches[1])
      {
        $ret = 1; // Success!
      }
    }
  }
  fclose($fh);
  return $ret;
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

