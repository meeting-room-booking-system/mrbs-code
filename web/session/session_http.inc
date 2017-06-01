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
  global $PHP_AUTH_PW;

  if (isset($PHP_AUTH_PW))
  {
    return unslashes($PHP_AUTH_PW);
  }
  else
  {
    return null;
  }
}

function getUserName()
{
  global $PHP_AUTH_USER;
  
  // We save the results of the user validation so that we avoid any performance
  // penalties in authValidateUser, which can be severe if for example we are using
  // LDAP authentication
  static $authorised_user = NULL;

  if (isset($PHP_AUTH_USER))
  {
    $user = unslashes($PHP_AUTH_USER);

    if ((isset($authorised_user) && ($authorised_user == $user)) ||
        (authValidateUser($user, getAuthPassword()) !== false))
    {
      $authorised_user = $user;
    }
    else
    {
      $authorised_user = NULL;
    }
  }
  else
  {
    $authorised_user = NULL;
  }
  
  return $authorised_user;
}

// Print the logon entry on the top banner.
function print_logon_box()
{
  global $QUERY_STRING;
  
  $target_url = this_page();

  if (isset($QUERY_STRING))
  {
    $target_url = $target_url . "?" . $QUERY_STRING;
  }
  
  $user = getUserName();
  
  if (isset($user))
  {
    print_report_link($user);
  }
  else
  {
    print_unknown_user();
    print_logon_button($target_url);
  }
  
  print_userlist_link();
}

