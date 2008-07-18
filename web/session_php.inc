<?php
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
*                                                                             *
*   History                                                                   *
*    2003/11/09 JFL Created this file                                         *
*    Remaining history in ChangeLog and CVS logs                              *
*                                                                             *
\*****************************************************************************/

// $Id$

global $PHP_SELF;

// Get form variables
$Action = get_form_var('Action', 'string');
$NewUserName = get_form_var('NewUserName', 'string');
$NewUserPassword = get_form_var('NewUserPassword', 'string');
$TargetURL = get_form_var('TargetURL', 'string');

if (isset($cookie_path_override))
{
  $cookie_path = $cookie_path_override;
}
else
{
  $cookie_path = $PHP_SELF;
  $cookie_path = ereg_replace('[^/]*$', '', $cookie_path);
}
session_set_cookie_params(0, $cookie_path);
session_start();

/*
  Target of the form with sets the URL argument "Action=SetName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "SetName"))
{
  /* First make sure the password is valid */
  if ($NewUserName == "")
  {

    // Unset the session variables
    if (isset($_SESSION))
    {
      $_SESSION = array();
    }
    else
    {
      global $HTTP_SESSION_VARS;
      $HTTP_SESSION_VARS = array();
    }
  }
  else
  {
    if (!authValidateUser($NewUserName, $NewUserPassword))
    {
      print_header(0, 0, 0, 0);
      echo "<p>".get_vocab('unknown_user')."</p>\n";
      printLoginForm($TargetURL);
      exit();
    }

    if (isset($_SESSION))
    {
      $_SESSION["UserName"] = $NewUserName;
    }
    else
    {
      global $HTTP_SESSION_VARS;
      $HTTP_SESSION_VARS["UserName"] = $NewUserName;
    }
  }

  header ("Location: $TargetURL"); /* Redirect browser to initial page */
  /* Note HTTP 1.1 mandates an absolute URL. Most modern browsers support relative URLs,
    which allows to work around problems with DNS inconsistencies in the server name.
    Anyway, if the browser cannot redirect automatically, the manual link below will work. */
  print_header(0, 0, 0, 0);
  echo "<br>\n";
  echo "<p>Please click <a href=\"".htmlspecialchars($TargetURL)."\">here</a> if you're not redirected automatically to the page you requested.</p>\n";
  echo "</body>\n";
  echo "</html>\n";
  exit();
}

/*
  Display the login form. Used by two routines below.
  Will eventually return to $TargetURL.
*/
function printLoginForm($TargetURL)
{
  global $PHP_SELF;
?>
<form class="form_general" id="logon" method="post" action="<?php echo htmlspecialchars(basename($PHP_SELF)) ?>">
  <fieldset>
  <legend><?php echo get_vocab("please_login") ?></legend>
    <div>
      <label for="NewUserName"><?php echo get_vocab("user_name") ?>:</label>
      <input type="text" id="NewUserName" name="NewUserName">
    </div>
    <div>
      <label for="NewUserPassword"><?php echo get_vocab("user_password") ?>:</label>
      <input type="password" id="NewUserPassword" name="NewUserPassword">
    </div>
  <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>" >
  <input type="hidden" name="Action" value="SetName" >
  <div id="logon_submit">
    <input class="submit" type="submit" value=" <?php echo get_vocab('login') ?> " >
  </div>
  </fieldset>
</form>
</body>
</html>
<?php
}

/*
  Target of the form with sets the URL argument "Action=QueryName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "QueryName"))
{
  print_header(0, 0, 0, 0);
  printLoginForm($TargetURL);
  exit();
}

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
  global $PHP_SELF, $QUERY_STRING;

  print_header(0, 0, 0, 0);

  echo "<p>".get_vocab("norights")."</p>\n";

  $TargetURL = basename($PHP_SELF);
  if (isset($QUERY_STRING))
  {
    $TargetURL = $TargetURL . "?" . $QUERY_STRING;
  }
  printLoginForm($TargetURL);

  exit();
}

function getUserName()
{
  if (isset($_SESSION) && isset($_SESSION["UserName"]) && ($_SESSION["UserName"] != ""))
  {
    return $_SESSION["UserName"];
  }
  else
  {
    global $HTTP_SESSION_VARS;
    if (isset($HTTP_SESSION_VARS["UserName"]) && ($HTTP_SESSION_VARS["UserName"] != ""))
    {
      return $HTTP_SESSION_VARS["UserName"];
    }
  }
}

// Print the logon entry on the top banner.
function PrintLogonBox()
{
  global $PHP_SELF, $QUERY_STRING, $user_list_link, $user_link, $day, $month;
  global $year, $auth;

  $TargetURL = basename($PHP_SELF);
  if (isset($url_base) && ($url_base != ""))
  {
    $TargetURL = $url_base . '/' . $TargetURL;
  }
  if (isset($QUERY_STRING))
  {
    $TargetURL = $TargetURL . "?" . $QUERY_STRING;
  }
  $user=getUserName();
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
                <form method="post" action="admin.php">
                  <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>" >
                  <input type="hidden" name="Action" value="SetName" >
                  <input type="hidden" name="NewUserName" value="" >
                  <input type="hidden" name="NewUserPassword" value="" >
                  <input type="submit" value=" <?php echo get_vocab('logoff') ?> " >
                </form>
<?php
  }
  else
  {
?>
                <a name="logonBox" href=""><?php echo get_vocab('unknown_user'); ?></a>
                <form method=post action="admin.php">
                  <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>" >
                  <input type="hidden" name="Action" value="QueryName" >
                  <input type="submit" value=" <?php echo get_vocab('login') ?> " >
                </form>
<?php
  } 
  if (isset($user_list_link))
  {
    print "<a href=\"$user_list_link\">" . get_vocab('user_list') . "</a>\n";
  }
}
?>
