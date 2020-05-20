<?php
namespace MRBS;

use MRBS\Form\Form;

/*****************************************************************************\
*                                                                             *
*   File name       session_php.inc                                           *
*                                                                             *
*   Description     Use PHP built-in sessions handling                        *
*                                                                             *
*   Notes           To use this authentication scheme, set in                 *
*                   config.inc.php:                                           *
*                       $auth["session"]  = "php";                            *
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
  global $returl;

  print_header();
  $target_url = multisite(this_page(true));
  print_login_form(multisite(this_page()), $target_url, $returl);

  exit();
}


function getUserName()
{
  if (isset($_SESSION["UserName"]) && ($_SESSION["UserName"] !== ''))
  {
    return $_SESSION["UserName"];
  }

  return null;
}


// Print the logon entry on the top banner.
function print_logon_box()
{
  $target_url = multisite(this_page(true));
  $user=getUserName();

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
  // Unset the session variables
  session_unset();
  session_destroy();
}

global $auth;

// Get non-standard form variables
$action = get_form_var('action', 'string', null, INPUT_POST);
$username = get_form_var('username', 'string', null, INPUT_POST);
$password = get_form_var('password', 'string', null, INPUT_POST);
$target_url = get_form_var('target_url', 'string', null, INPUT_POST);
$returl = get_form_var('returl', 'string', null, INPUT_POST);

$username = trim($username); // It's easy for extra spaces to appear, especially on a mobile device

// Check to see if we've been inactive for longer than allowed and if so logout
// the user
if (!empty($auth["session_php"]["inactivity_expire_time"]))
{
  if (isset($_SESSION['LastActivity']) &&
      ((time() - $_SESSION['LastActivity']) > $auth["session_php"]["inactivity_expire_time"]))
  {
    unset($_SESSION["UserName"]);
  }
  // Ajax requests don't count as activity, unless it's the special Ajax request used
  // to record client side activity.
  $activity = get_form_var('activity', 'int');
  if ($activity || !is_ajax() || !isset($_SESSION['LastActivity']))
  {
    $_SESSION['LastActivity'] = time();
  }
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

    // First make sure the password is valid
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

      // Successful login.  As a defence against session fixation, regenerate
      // the session id and delete the old session.
      session_regenerate_id(true);
      $_SESSION["UserName"] = $valid_username;

      if (!empty($returl))
      {
        // check to see whether there's a query string already
        $target_url .= (strpos($target_url, '?') === false) ? '?' : '&';
        $target_url .= 'returl=' . urlencode($returl);
      }
    }

    // Problems have been reported on Windows IIS with session data not being
    // written out without a call to session_write_close()
    session_write_close();
    location_header($target_url); /* Redirect browser to initial page */
  }
}
