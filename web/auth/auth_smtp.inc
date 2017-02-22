<?php
namespace MRBS;

/*
 * Authentication scheme that uses SMTP as the source for user
 * authentication. Requires the Net_SMTP Pear package
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    // Or any other string
 * $auth["type"]  = "smtp";
 * $auth['smtp']['server'] = 'myserver.example.org'; // Your SMTP server
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "smtpuser1";
 * $auth["admin"][] = "smtpuser2";
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
  if (!isset($user) || !isset($pass) || strlen($pass)==0)
  {
    return false;
  }

  require 'Net/SMTP.php';

  $smtp = new Net_SMTP($auth['smtp']['server']);

  if ($smtp)
  {
    $e = $smtp->connect();
    if (!PEAR::isError($e))
    {
      if (!PEAR::isError($smtp->auth($user, $pass)))
      {
        return $user;
      }

      $smtp->disconnect();
    }
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
  if (!isset($user))
  {
    return 0;
  }

  // Check if the user is can modify
  for ($i = 0; $admins[$i]; $i++)
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


