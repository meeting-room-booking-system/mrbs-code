<?php
namespace MRBS;

/*****************************************************************************\
*                                                                             *
*   File name       auth_http.inc                                             *
*                                                                             *
*   Description     Get user identity using the HTTP basic authentication.    *
*                                                                             *
*   Notes           To use this session scheme, set in config.inc.php:        *
*                       $auth["session"]  = "http";                           *
*                                                                             *
\*****************************************************************************/

require_once MRBS_ROOT . '/session/functions_session.inc';

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
  global $auth;
  header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
  header("HTTP/1.0 401 Unauthorized");
}

function getAuthPassword()
{
  global $server;
  
  return (isset($server['PHP_AUTH_PW'])) ? $server['PHP_AUTH_PW'] : null;
}

function getUserName()
{
  global $server;
  
  // We save the results of the user validation so that we avoid any performance
  // penalties in authValidateUser, which can be severe if for example we are using
  // LDAP authentication
  static $authorised_user = null;

  if (isset($server['PHP_AUTH_USER']))
  {
    $user = $server['PHP_AUTH_USER'];

    if ((isset($authorised_user) && ($authorised_user == $user)) ||
        (authValidateUser($user, getAuthPassword()) !== false))
    {
      $authorised_user = $user;
    }
    else
    {
      $authorised_user = null;
    }
  }
  else
  {
    $authorised_user = null;
  }
  
  return $authorised_user;
}

// Print the logon entry on the top banner.
function print_logon_box()
{
  $target_url = multisite(this_page(true));
  $user = getUserName();
  
  if (isset($user))
  {
    print_report_link($user);
  }
  else
  {
    print_logon_button($target_url);
  }
}

