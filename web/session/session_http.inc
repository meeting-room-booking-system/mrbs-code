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


// Print the logon entry on the top banner.
function print_logon_box()
{
  $target_url = this_page(true);
  $user = session()::getUsername();
  
  if (isset($user))
  {
    print_report_link($user);
  }
  else
  {
    print_logon_button($target_url);
  }
}

