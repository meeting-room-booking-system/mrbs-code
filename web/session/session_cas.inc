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
  $target_url = this_page(true);
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
  phpCAS::logout();
}


global $HTTP_REFERER;

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
    
    header ("Location: $target_url"); /* Redirect browser to initial page */
    /* Note HTTP 1.1 mandates an absolute URL. Most modern browsers support relative URLs,
      which allows to work around problems with DNS inconsistencies in the server name.
      Anyway, if the browser cannot redirect automatically, the manual link below will work. */
    print_header();
    echo "<br>\n";
    echo "<p>Please click <a href=\"".htmlspecialchars($target_url)."\">here</a> if you're not redirected automatically to the page you requested.</p>\n";

    // Print footer and exit
    print_footer(TRUE);
  }
}
