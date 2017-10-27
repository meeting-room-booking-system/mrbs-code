<?php
namespace MRBS;

use MRBS\Form\Form;

/*****************************************************************************\
*                                                                            *
*   File name      session_cookie.inc                                        *
*                                                                            *
*   Description    Manage sessions via cookies stored in the client browser. *
*                                                                            *
*   URL arguments  UserName       The user name                              *
*                  UserPassword   His password                               *
*                  target_url      Where we were going before login.          *
*                                                                            *
*   Notes          To use this session mechanism, set in config.inc.php:     *
*                  $auth["session"]  = "cookie";                             *
*                                                                            *
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
  global $QUERY_STRING;
  global $returl;

  print_header();

  echo "<p>".get_vocab("norights")."</p>\n";
  
  $target_url = this_page();
  if (isset($QUERY_STRING))
  {
    $target_url = $target_url . "?" . $QUERY_STRING;
  }
  print_login_form(this_page(), $target_url, $returl);

  exit();
}

function getUserName()
{
  global $REMOTE_ADDR, $auth;
  
  static $cached_username = null;
  static $have_checked_cookie = false;

  if ($have_checked_cookie)
  {
    return $cached_username;
  }
  
  // If the cached username isn't set, we have to decode the cookie, but
  // first set the flag, so we will only do this once
  $have_checked_cookie = true;

  if (!empty($_COOKIE) && isset($_COOKIE["SessionToken"]))
  {
    $token = unslashes($_COOKIE["SessionToken"]);
  }

  if (isset($token) && ($token != ""))
  {
    list($hash, $base64_data) = explode("_", $token);
    
    $json_data = base64_decode($base64_data);

    if (!function_exists('hash_hmac'))
    {
      fatal_error("It appears that your PHP has the hash functions ".
                  "disabled, which are required for the 'cookie' ".
                  "session scheme.");
    }
    if (hash_hmac(
                  $auth["session_cookie"]["hash_algorithm"],
                  $json_data,
                  $auth['session_cookie']['secret']
                 ) == $hash)
    {
      $session_data = json_decode($json_data, true);
          
      /* Check for valid session data */
      if (isset($session_data['user']) &&
          isset($session_data['expiry']))
      {
        // Have basic data

        if ((($auth["session_cookie"]["session_expire_time"] == 0) &&
             ($session_data['expiry'] == 0)) ||
            ($session_data['expiry'] > time()))
        {
          // Expiry is OK
          
          if (!isset($session_data['ip']) ||
              ($session_data['ip'] == $REMOTE_ADDR))
          {
            // IP is OK
            $cached_username = $session_data['user'];
          }
        }
      }
      else
      {
        // Basic data checks failed
      }
    }
    else
    {
      error_log("Token is invalid, cookie has been tampered with or secret may have changed");
    }
  }

  return $cached_username;
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
    print_logoff_button($target_url);
  }
  else
  {
    print_unknown_user();
    print_logon_button($target_url);
  }
  
  print_userlist_link();
}


function logoff_user()
{
  // Delete cookie
  $cookie_path = get_cookie_path();
  setcookie("SessionToken", '', time()-42000, $cookie_path);
}


global $PHP_SELF, $HTTP_REFERER;
global $auth;

// Get non-standard form variables
$action = get_form_var('action', 'string', null, INPUT_POST);
$username = get_form_var('username', 'string', null, INPUT_POST);
$password = get_form_var('password', 'string', null, INPUT_POST);
$target_url = get_form_var('target_url', 'string', null, INPUT_POST);
$returl = get_form_var('returl', 'string', null, INPUT_POST);

// We need to preserve the original calling page, so that it's there when we eventually get to the
// target_url (especially if that's edit_entry.php).  If this is the first time through then $HTTP_REFERER
// holds the original caller.    If this is the second time through we will have stored it in $returl.
if (!isset($returl))
{
  $returl = isset($HTTP_REFERER) ? $HTTP_REFERER : '';
}

$cookie_path = get_cookie_path();


// Delete old-style cookies
if (!empty($_COOKIE) && isset($_COOKIE["UserName"]))
{
  setcookie("UserName", '', time()-42000, $cookie_path);
}


if (isset($action))
{
  // Target of the form with sets the URL argument "action=QueryName".
  // Will eventually return to URL argument "target_url=whatever".
  if ($action == "QueryName")
  {
    print_header();
    print_login_form(this_page(), $target_url, $returl);
    exit();
  }
  
  
  // Target of the form with sets the URL argument "action=SetName".
  // Will eventually return to URL argument "target_url=whatever".
  if ($action == "SetName")
  {
    // If we're going to do something then check the CSRF token first
    Form::checkToken();
    
    // Avoid errors with error level ALL
    if ( !isset( $username ) )
    {
      $username = "";
    }
    /* First make sure the password is valid */
    if ($username == "") 
    {
      logoff_user();
    }
    else
    {
      if (($valid_username = authValidateUser($username, $password)) === false)
      {
        print_header();
        print_login_failed();
        print_login_form(this_page(), $target_url, $returl);
        exit();
      }
      else
      {
        $UserName     = $valid_username;
        $UserPassword = $password;
      }

      if ($auth['session_cookie']['session_expire_time'] == 0)
      {
        $expiry_time = 0;
      }
      else
      {
        $expiry_time = time()+$auth['session_cookie']['session_expire_time'];
      }

      $session_data = array();
      $session_data['user'] = $UserName;
      $session_data['expiry'] = $expiry_time;
      if ($auth['session_cookie']['include_ip'])
      {
        $session_data['ip'] = $REMOTE_ADDR;
      }
      $json_data = json_encode($session_data);
      if (!function_exists('hash_hmac'))
      {
        fatal_error("It appears that your PHP has the hash functions " .
                    "disabled, which are required for the 'cookie' " .
                    "session scheme.");
      }
      $hash = hash_hmac(
                        $auth["session_cookie"]["hash_algorithm"],
                        $json_data,
                        $auth['session_cookie']['secret']
                       );

      setcookie("SessionToken",
                "${hash}_".base64_encode($json_data),
                $expiry_time,
                $cookie_path);
      }
      // preserve the original $HTTP_REFERER by sending it as a GET parameter
      if (!empty($returl))
      {
        // check to see whether there's a query string already
        if (strpos($target_url, '?') === false)
        {
          $target_url .= "?returl=" . urlencode($returl);
        }
        else
        {
          $target_url .= "&returl=" . urlencode($returl);
        }
      }
      header ("Location: $target_url"); /* Redirect browser to initial page */
      /* Note HTTP 1.1 mandates an absolute URL. Most modern browsers support relative URLs,
          which allows to work around problems with DNS inconsistencies in the server name.
          Anyway, if the browser cannot redirect automatically, the manual link below will work. */
      print_header();
      echo "<p>Please click <a href=\"".htmlspecialchars($target_url)."\">here</a> if you're not redirected automatically to the page you requested.</p>\n";

      // Print footer and exit
      print_footer(TRUE);
  }
}
