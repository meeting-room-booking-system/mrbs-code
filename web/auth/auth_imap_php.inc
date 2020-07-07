<?php
namespace MRBS;

/*
 * Authentication scheme that uses IMAP as the source for user
 * authentication. It requires you to have the PHP 'imap' extension
 * installed and enabled.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    // Or any other string
 * $auth["type"]  = "imap_php";
 *
 * You must also configure at least:
 *
 * $auth["imap_php"]["hostname"] = "mailserver.hostname";
 *
 * You can also specify any of the following options:
 *
 * // Specifies the port number to connect to
 * $auth["imap_php"]["port"] = 993;
 *
 * // Use SSL
 * $auth["imap_php"]["ssl"] = TRUE;
 *
 * // Use TLS
 * $auth["imap_php"]["tls"] = TRUE;
 *
 * // Turn off SSL/TLS certificate validation
 * $auth["imap_php"]["novalidate-cert"] = TRUE;
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "imapuser1";
 * $auth["admin"][] = "imapuser2";
 * 
 * Returns:
 *   false    - The pair are invalid or do not exist
 *   string   - The validated username
 */
function authValidateUser($user, $pass)
{
  global $auth;

  // If required, check that the username is from the permitted domain
  if (isset($auth['imap_php']['user_domain']))
  {
    if (!filter_var($user, FILTER_VALIDATE_EMAIL))
    {
      return false;
    }
    
    list($local_part, $domain) = explode('@', $user);
    
    if ($domain != $auth['imap_php']['user_domain'])
    {
      return false;
    }
  }
  
  $location = "{".$auth["imap_php"]["hostname"];

  if (isset($auth["imap_php"]["port"]))
  {
    $location .= ":".$auth["imap_php"]["port"];
  }

  $location .= "/imap";

  if (!empty($auth["imap_php"]["ssl"]))
  {
    $location .= "/ssl";
  }

  if (!empty($auth["imap_php"]["tls"]))
  {
    $location .= "/tls";
  }

  if (!empty($auth["imap_php"]["novalidate-cert"]))
  {
    $location .= "/novalidate-cert";
  }

  $location .= "}INBOX";

  $mbox = imap_open($location, $user, $pass);

  if ($mbox !== false)
  {
    imap_close($mbox);
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


