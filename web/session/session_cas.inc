<?php 
namespace MRBS;

use \phpCAS;

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
  global $auth;
  
  $user = getUserName();

  if (isset($user))
  {
    print_report_link($user);

    // Retrieve logout link from configuration, if specified
    if (isset($auth['cas']['logout_link']) &&
        is_string($auth['cas']['logout_link']) &&
        (!empty($auth['cas']['logout_link'])))
    {
      print '<a href="' . $auth['cas']['logout_link'] .'">' . get_vocab('logoff') . "</a>\n";
    }

  }
  else
  {
    if (isset($auth['cas']['login_link']) &&
        is_string($auth['cas']['login_link']) &&
        (!empty($auth['cas']['login_link'])))
    {
      print '<a href="' . $auth['cas']['login_link'] .'">' . get_vocab('login') . "</a>\n";
    }
    else
    {
      echo "<p class=\"error\">Error: cas_set was not included when it should have been.</p>";
      exit;
    }
  }
  
  print_userlist_link();
}

?>
