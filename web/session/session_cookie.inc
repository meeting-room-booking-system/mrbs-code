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
  global $returl;

  print_header(); 
  $target_url = multisite(this_page(true));
  print_login_form(multisite(this_page()), $target_url, $returl);
  
  exit();
}

function getUserName()
{
  global $auth, $server;
  
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
    $token = $_COOKIE["SessionToken"];
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
              ($session_data['ip'] == $server['REMOTE_ADDR']))
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
  $target_url = multisite(this_page(true));
  $user = getUserName();
  
  if (isset($user))
  {
    print_report_link($user);
    print_logoff_button($target_url);
  }
  else
  {
    print_logon_button($target_url);
  }
}


function logoff_user()
{
  // Delete cookie
  $cookie_path = get_cookie_path();
  setcookie("SessionToken", '', time()-42000, $cookie_path);
}


global $auth, $server;

// Get non-standard form variables
$action = get_form_var('action', 'string', null, INPUT_POST);
$username = get_form_var('username', 'string', null, INPUT_POST);
$password = get_form_var('password', 'string', null, INPUT_POST);
$target_url = get_form_var('target_url', 'string', null, INPUT_POST);
$returl = get_form_var('returl', 'string', null, INPUT_POST);

$username = trim($username); // It's easy for extra spaces to appear, especially on a mobile device

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
    print_login_form(multisite(this_page()), $target_url, $returl);
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
        print_login_form(multisite(this_page()), $target_url, $returl, get_vocab('unknown_user'));
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
        $session_data['ip'] = $server['REMOTE_ADDR'];
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
                
      if (!empty($returl))
      {
        // check to see whether there's a query string already
        $target_url .= (strpos($target_url, '?') === false) ? '?' : '&';
        $target_url .= 'returl=' . urlencode($returl);
      }
    }
    
    location_header($target_url); /* Redirect browser to initial page */
  }
}
