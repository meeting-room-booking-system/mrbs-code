<?php 
namespace MRBS;

/********************************************************************************************\
*                                                                                            *
*   File name       session_remote_user.inc                                                  *
*                                                                                            *
*   Description     Get user identity/password using the REMOTE_USER environment variable.   *
*                   Both identity and password equal the value of REMOTE_USER.               *
*                                                                                            *
*   Notes           To use this session scheme, set in config.inc.php:                       *
*                     $auth['session']  = 'remote_user';                                     *
*                     $auth['type'] = 'none';                                                *
*                                                                                            *
*                                                                                            *
*                   If you want to display a login link, set in config.inc.php:              *
*                     $auth['remote_user']['login_link'] = '/login/link.html';               *
*                                                                                            *
*                   If you want to display a logout link, set in config.inc.php:             *
*                     $auth['remote_user']['logout_link'] = '/logout/link.html';             *
*                                                                                            *
\*********************************************************************************************/

require_once MRBS_ROOT . '/session/functions_session.inc';

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
  // User is expected to already be authenticated by the web server, so do nothing
}

function getUserName()
{
  if ((!isset($_SERVER['REMOTE_USER'])) ||
      (!is_string($_SERVER['REMOTE_USER'])) ||
      (empty($_SERVER['REMOTE_USER'])))
  {
    return NULL;
  } 
  else
  {
    return $_SERVER['REMOTE_USER'];
  }
}


// Print the logon entry on the top banner.
function print_logon_box()
{
  global $auth;
  
  $user = getUserName();

  if (isset($user))
  {
    print_report_link($user);

    // Retrieve logout link from configuration, if specified
    if (isset($auth['remote_user']['logout_link']) &&
        is_string($auth['remote_user']['logout_link']) &&
        (!empty($auth['remote_user']['logout_link'])))
    {
      print '<a href="' . $auth['remote_user']['logout_link'] .'">' . get_vocab('logoff') . "</a>\n";
    }
  }
  else
  {
    if (isset($auth['remote_user']['login_link']) &&
        is_string($auth['remote_user']['login_link']) &&
        (!empty($auth['remote_user']['login_link'])))
    {
      print '<a href="' . $auth['remote_user']['login_link'] .'">' . get_vocab('login') . "</a>\n";
    }
    else
    {
      echo "<p class=\"error\">Error: REMOTE_USER was not set when it should have been.</p>";
      exit;
    }
  }
  
  print_userlist_link();
}

