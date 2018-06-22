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
         . htmlspecialchars($user) ?></a>
<?php
    if (isset($user_list_link))
    {
      print "<a href='$user_list_link'>" . get_vocab('user_list') . "</a>\n" ;
    }

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
}

?>
