<?php 
namespace MRBS;

use \phpCAS;
use MRBS\Form\Form;

require_once MRBS_ROOT . '/session/functions_session.inc';


function authGet()
{
  // Useless Method
}


function getUserName()
{
  return (phpCAS::isAuthenticated()) ? phpCAS::getUser() : null;
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
  phpCAS::logout();
}


// Get non-standard form variables
$action = get_form_var('action', 'string', null, INPUT_POST);
$target_url = get_form_var('target_url', 'string', null, INPUT_POST);
$returl = get_form_var('returl', 'string', null, INPUT_POST);

if (isset($action))
{
  // Target of the form with sets the URL argument "action=QueryName".
  if ($action == "QueryName")
  {
    phpCAS::forceAuthentication();
  }
  
  
  // Target of the form with sets the URL argument "action=SetName".
  // Will eventually return to URL argument "target_url=whatever".
  if ($action == "SetName")
  {
    // If we're going to do something then check the CSRF token first
    Form::checkToken();
    
    // You should only get here using CAS authentication after clicking the logoff
    // link, no matter what the value of the form parameters.
    logoff_user();
    
    location_header($target_url); /* Redirect browser to initial page */
  }
}
