<?php 
/*****************************************************************************\
*                                                                             *
*   File name       auth_http.inc                                             *
*                                                                             *
*   Description     Get user identity using the HTTP basic authentication.    *
*                                                                             *
*   Notes           To use this session scheme, set in config.inc.php:        *
*                       $auth["session"]  = "http";                           *
*                                                                             *
*   History                                                                   *
*    2003/11/12 JFL Created this file                                         *
*    Remaining history in ChangeLog and CVS logs                              *
*                                                                             *
\*****************************************************************************/

// $Id$

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
  global $auth;
  header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
  Header("HTTP/1.0 401 Unauthorized");
}

function getAuthPassword()
{
  global $PHP_AUTH_PW;

  if (isset($PHP_AUTH_PW))
  {
    return unslashes($PHP_AUTH_PW);
  }
  else
  {
    return null;
  }
}

function getUserName()
{
  global $PHP_AUTH_USER;
  
  // We save the results of the user validation so that we avoid any performance
  // penalties in authValidateUser, which can be severe if for example we are using
  // LDAP authentication
  static $authorised_user = NULL;

  if (isset($PHP_AUTH_USER))
  {
    $user = unslashes($PHP_AUTH_USER);

    if ((isset($authorised_user) && ($authorised_user == $user)) ||
        authValidateUser($user,getAuthPassword()))
    {
      $authorised_user = $user;
    }
    else
    {
      $authorised_user = NULL;
    }
  }
  else
  {
    $authorised_user = NULL;
  }
  
  return $authorised_user;
}

// Print the logon entry on the top banner.
function PrintLogonBox()
{
  global $PHP_SELF, $QUERY_STRING, $user_list_link, $day, $month, $year;
  
  $TargetURL = basename($PHP_SELF);
  if (isset($url_base) && ($url_base !== ''))
  {
    $TargetURL = $url_base . '/' . $TargetURL;
  }
  if (isset($QUERY_STRING))
  {
    $TargetURL = $TargetURL . "?" . $QUERY_STRING;
  }
  
  $user = getUserName();
  if (isset($user))
  {
    // words 'you are xxxx' becomes a link to the
    // report page with only entries created by xxx. Past entries are not
    // displayed but this can be changed
    $search_string = "report.php?from_day=$day&amp;from_month=$month&amp;".
      "from_year=$year&amp;to_day=1&amp;to_month=12&amp;to_year=2030&amp;areamatch=&amp;".
      "roommatch=&amp;namematch=&amp;descrmatch=&amp;summarize=1&amp;sortby=r&amp;display=d&amp;".
      "sumby=d&amp;creatormatch=$user";
?>


      <a href="<?php echo "$search_string\" title=\""
         . get_vocab('show_my_entries') . "\">" . get_vocab('you_are')." "
         . htmlspecialchars($user) ?></a>
<?php
  }
  else
  {
?>
    <a href=""><?php echo get_vocab('unknown_user'); ?></a>
    <form method="post" action="admin.php">
      <div>
        <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>">
        <input type="hidden" name="Action" value="QueryName">
        <input type="submit" value=" <?php echo get_vocab('login') ?> ">
      </div>
    </form>
<?php
  }
  if (isset($user_list_link))
  {
    print "<a id=\"user_list_link\" href=\"$user_list_link\">" . get_vocab('user_list') . "</a>\n" ;
  } 
}

