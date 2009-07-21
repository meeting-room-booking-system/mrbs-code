<?php 
/********************************************************************************************\
*                                                                                            *
*   File name       session_remote_user.inc                                                  *
*                                                                                            *
*   Description     Get user identity/password using the REMOTE_USER environment variable.   *
*                   Both identity and password equal the value of REMOTE_USER.               *
*                                                                                            *
*   Notes           To use this session scheme, set in config.inc.php:                       *
*                     $auth['session']  = 'remote_user';                                     *
*                     $auth['type'] = 'none';
*                                                                                            *
*                   If you want to display a logout link, set in config.inc.php:             *
*                     $auth['remote_user']['logout_link'] = '/logout/link.html';             *
*                                                                                            *
*   History                                                                                  *
*    2006-01-04 Bjorn.Wiberg@its.uu.se  Created based on session_http.inc                    *
*    2006-02-16 Bjorn.Wiberg@its.uu.se  Made logout link a configuration item                *
*                                                                                            *
\*********************************************************************************************/

// $Id$

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
function PrintLogonBox()
{
  global $user_list_link, $day, $month, $year;
  global $auth;
  
  $user = getUserName();

  if (isset($user))
  {
    // words 'you are xxxx' becomes a link to the
    // report page with only entries created by xxx. Past entries are not
    // displayed but this can be changed
    $search_string = "report.php?From_day=$day&amp;From_month=$month&amp;".
      "From_year=$year&amp;To_day=1&amp;To_month=12&amp;To_year=2030&amp;areamatch=&amp;".
      "roommatch=&amp;namematch=&amp;descrmatch=&amp;summarize=1&amp;sortby=r&amp;display=d&amp;".
      "sumby=d&amp;creatormatch=$user"; ?>

      <a name="logonBox" href="<?php echo "$search_string\" title=\""
         . get_vocab('show_my_entries') . "\">" . get_vocab('you_are')." "
         .$user ?></a>
<?php
    if (isset($user_list_link))
    {
      print "<a href='$user_list_link'>" . get_vocab('user_list') . "</a>\n" ;
    }

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
    echo "<p class=\"error\">Error: REMOTE_USER was not set when it should have been.</p>";
    exit;
  }
}

?>
