<?php
/*****************************************************************************\
*                                                                            *
*   File name      session_cookie.inc                                        *
*                                                                            *
*   Description    Manage sessions via cookies stored in the client browser. *
*                                                                            *
*   URL arguments  UserName       The user name                              *
*                  UserPassword   His password                               *
*                  TargetURL      Where we were going before login.          *
*                                                                            *
*   Notes          To use this session mechanism, set in config.inc.php:     *
*                  $auth["session"]  = "cookie";                             *
*                                                                            *
*   History                                                                  *
*                  2003/11/09 JFL Created this file                          *
*                  Remaining history in ChangeLog and CVS logs               *
*                                                                            *
\*****************************************************************************/

// $Id$

// We use some functions from here
require_once "functions.inc";
require_once('Blowfish.php');

$blowfish = new Crypt_Blowfish($auth['session_cookie']['secret']);

// Get non-standard form variables
$Action = get_form_var('Action', 'string');
$NewUserName = get_form_var('NewUserName', 'string');
$NewUserPassword = get_form_var('NewUserPassword', 'string');
$TargetURL = get_form_var('TargetURL', 'string');
$returl = get_form_var('returl', 'string');

if (isset($cookie_path_override))
{
  $cookie_path = $cookie_path_override;
}
else
{
  $cookie_path = $PHP_SELF;
  // Strip off everything after the last '/' in $PHP_SELF
  $cookie_path = preg_replace('/[^\/]*$/', '', $cookie_path);
}

/* Delete old-style cookies */
if (!empty($_COOKIE) && isset($_COOKIE["UserName"]))
{
  setcookie("UserName", '', time()-42000, $cookie_path);
}

/*
  Target of the form with sets the URL argument "Action=SetName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "SetName"))
{
  // Avoid errors with error level ALL
  if ( !isset( $NewUserName ) )
  {
    $NewUserName = "";
  }
  /* First make sure the password is valid */
  if ($NewUserName == "") 
  {
    // Delete cookie
    setcookie("SessionToken", '', time()-42000, $cookie_path);
  }
  else
  {
    if (!authValidateUser($NewUserName, $NewUserPassword))
    {
      print_header(0, 0, 0, 0, "");
      echo "<p>".get_vocab('unknown_user')."</p>\n";
      printLoginForm($TargetURL);
      exit();
    }
    else
    {
      $UserName     = $NewUserName;
      $UserPassword = $NewUserPassword;
    }

    if ($auth['session_cookie']['session_expire_time'] == 0)
    {
      $expiry_time = 0;
    }
    else
    {
      $expiry_time = time()+$auth['session_cookie']['session_expire_time'];
    }
    $token = "AUTH".$UserName."|".$expiry_time;
    if ($auth['session_cookie']['include_ip'])
    {
      $token .= "|".$REMOTE_ADDR;
    }
    setcookie("SessionToken", $blowfish->encrypt($token), $expiry_time, $cookie_path);
    }
    // preserve the original $HTTP_REFERER by sending it as a GET parameter
    if (!empty($returl))
    {
      // check to see whether there's a query string already
      if (strpos($TargetURL, '?') === false)
      {
        $TargetURL .= "?returl=" . urlencode($returl);
      }
      else
      {
        $TargetURL .= "&returl=" . urlencode($returl);
      }
    }
    header ("Location: $TargetURL"); /* Redirect browser to initial page */
    /* Note HTTP 1.1 mandates an absolute URL. Most modern browsers support relative URLs,
        which allows to work around problems with DNS inconsistencies in the server name.
        Anyway, if the browser cannot redirect automatically, the manual link below will work. */
    print_header(0, 0, 0, 0, "");
    echo "<p>Please click <a href=\"".htmlspecialchars($TargetURL)."\">here</a> if you're not redirected automatically to the page you requested.</p>\n";

    // Print footer and exit
    print_footer(TRUE);
}

/*
  Display the login form. Used by two routines below.
  Will eventually return to $TargetURL.
*/
function printLoginForm($TargetURL)
{
  global $PHP_SELF, $HTTP_REFERER;
  global $returl;
?>
<p>
  
</p>
<form class="form_general" id="logon" method="post" action="<?php echo htmlspecialchars(basename($PHP_SELF)) ?>">
  <fieldset>
  <legend><?php echo get_vocab("please_login") ?></legend>
    <div>
      <label for="NewUserName"><?php echo get_vocab("users.name") ?>:</label>
      <input type="text" id="NewUserName" name="NewUserName">
    </div>
    <div>
      <label for="NewUserPassword"><?php echo get_vocab("users.password") ?>:</label>
      <input type="password" id="NewUserPassword" name="NewUserPassword">
    </div>
    <?php
    // We need to preserve the original calling page, so that it's there when we eventually get
    // to the TargetURL (especially if that's edit_entry.php).  If this is the first time through then $HTTP_REFERER holds
    // the original caller.    If this is the second time through we will have stored it in $returl.
    if (!isset($returl))
    {
      $returl = isset($HTTP_REFERER) ? $HTTP_REFERER : "";
    }
    echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
    ?>
    <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>">
    <input type="hidden" name="Action" value="SetName">
    <div id="logon_submit">
      <input class="submit" type="submit" value="<?php echo get_vocab('login') ?> ">
    </div>
  </fieldset>
</form>
<?php
  // Print footer and exit
  print_footer(TRUE);
}

/*
  Target of the form with sets the URL argument "Action=QueryName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "QueryName"))
{
  print_header(0, 0, 0, 0, "");
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

  print_header(0, 0, 0, 0, "");

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
  global $blowfish, $REMOTE_ADDR, $auth;

  $encrypted_token = NULL;
  $username = NULL;

  if (!empty($_COOKIE) && isset($_COOKIE["SessionToken"]))
  {
    $encrypted_token = unslashes($_COOKIE["SessionToken"]);
  }
  else
  {
    global $HTTP_COOKIE_VARS;
    if (!empty($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS["SessionToken"]))
    {
      $encrypted_token = unslashes($HTTP_COOKIE_VARS["SessionToken"]);
    }
  }

  if (isset($encrypted_token))
  {
    $token = $blowfish->decrypt($encrypted_token);

    /* Remove the NULs off the end of the decrypted string */
    $token = preg_replace('/\0+$/', "", $token);

    if (substr($token, 0, 4) == "AUTH")
    {
      $parts = explode("|",substr($token,4));

      /* Check for a valid token */
      if (isset($parts[0]) &&
          isset($parts[1]) &&
          ((($auth["session_cookie"]["session_expire_time"] == 0) &&
            ($parts[1] == 0)) || ($parts[1] > time())) &&
          (!isset($parts[2]) ||
           (isset($parts[2]) && ($parts[2] == $REMOTE_ADDR))))
      {
        $username = $parts[0];
      }
      else
      {
        // We can't delete the cookie, as it's too late - we'll just ignore it
      }
    }
    else
    {
      error_log("Token is invalid, secret may have changed");
    }
  }

  return $username;
}

// Print the logon entry on the top banner.
function PrintLogonBox()
{
  global $PHP_SELF, $QUERY_STRING, $user_list_link, $day, $month, $year;
  
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
    $search_string = "report.php?from_day=$day&amp;from_month=$month&amp;".
      "from_year=$year&amp;to_day=1&amp;to_month=12&amp;to_year=2030&amp;".
      "areamatch=&amp;roommatch=&amp;namematch=&amp;descrmatch=&amp;summarize=1&amp;".
      "sortby=r&amp;display=d&amp;sumby=d&amp;creatormatch=$user";
?>
      <a href="<?php echo "$search_string\" title=\""
         . get_vocab('show_my_entries') . "\">
        " . get_vocab('you_are')." "
         . htmlspecialchars($user) ?>
      </a>
      <form method="post" action="admin.php">
        <div>
          <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>">
          <input type="hidden" name="Action" value="SetName">
          <input type="hidden" name="UserName" value="">
          <input type="hidden" name="UserPassword" value="">
          <input type="submit" value=" <?php echo get_vocab('logoff') ?> ">
        </div>
      </form>
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
    print "<a href=\"$user_list_link\">" . get_vocab('user_list') . "</a>\n";
  }
}

?>
