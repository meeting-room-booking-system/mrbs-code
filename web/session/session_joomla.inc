<?php
namespace MRBS;

use MRBS\Form\Form;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';
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
  return JFactory::getUser()->username;
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
  $mainframe = JFactory::getApplication('site');
  $mainframe->logout();
}


global $auth;

// Get non-standard form variables
$action = get_form_var('action', 'string', null, INPUT_POST);
$username = get_form_var('username', 'string', null, INPUT_POST);
$password = get_form_var('password', 'string', null, INPUT_POST);
$target_url = get_form_var('target_url', 'string', null, INPUT_POST);
$returl = get_form_var('returl', 'string', null, INPUT_POST);

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
    /* First make sure the password is valid */
    if ($username == "")
    {
      logoff_user();
    }
    else
    {
      $credentials = array();
      $credentials['username'] = $username;
      $credentials['password'] = $password;
      $mainframe = JFactory::getApplication('site');
   
      if (!$mainframe->login($credentials))
      {
        print_header();
        print_login_form(multisite(this_page()), $target_url, $returl, get_vocab('unknown_user'));
        exit();
      }
      
      // Preserve the original $_SERVER['HTTP_REFERER'] by sending it as a GET parameter
      if (!empty($returl))
      {
        // check to see whether there's a query string already
        if (strpos($target_url, '?') === false)
        {
          $target_url .= "?returl=$returl";
        }
        else
        {
          $target_url .= "&returl=$returl";
        }
      }
    }

    location_header($target_url); /* Redirect browser to initial page */
  }
}
