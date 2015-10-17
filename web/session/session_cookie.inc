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

    $session_data = array();
    $session_data['user'] = $UserName;
    $session_data['expiry'] = $expiry_time;
    if ($auth['session_cookie']['include_ip'])
    {
      $session_data['ip'] = $REMOTE_ADDR;
    }
    $json_data = json_encode($session_data);
    if (!function_exists('hash_hmac'))
    {
      fatal_error(FALSE, "It appears that your PHP has the hash functions ".
                          "disabled, which are required for the 'cookie' ".
                          "session scheme.");
    }
    $hash = hash_hmac(
                      $auth["session_cookie"]["hash_algorithm"],
                      $json_data,
                      $auth['session_cookie']['secret']
                     );

    setcookie("SessionToken",
              "${hash}_".base64_encode($json_data),
              $expiry_time,
              $cookie_path);
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
  global $HTTP_REFERER;
  global $returl;
?>
<p>
  
</p>
<form class="form_general" id="logon" method="post" action="<?php htmlspecialchars(this_page()) ?>">
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
  echo "</div>";  // Close of the contents div
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
  global $REMOTE_ADDR, $auth;
  
  static $cached_username = null;

  //error_log("Called getUserName()");

  if (!is_null($cached_username))
  {
    //error_log("cached");
    return $cached_username;
  }
  
  // If the cached username isn't set, we have to decode the cookie, but
  // first set the cached username to an empty string, so we will only do
  // this once
  $cached_username = "";

  if (!empty($_COOKIE) && isset($_COOKIE["SessionToken"]))
  {
    $token = unslashes($_COOKIE["SessionToken"]);
  }
  else
  {
    global $HTTP_COOKIE_VARS;
    if (!empty($HTTP_COOKIE_VARS) && isset($HTTP_COOKIE_VARS["SessionToken"]))
    {
      $token = unslashes($HTTP_COOKIE_VARS["SessionToken"]);
    }
  }

  if (isset($token) && ($token != ""))
  {
    list($hash, $base64_data) = explode("_", $token);
    
    $json_data = base64_decode($base64_data);

    if (!function_exists('hash_hmac'))
    {
      fatal_error(FALSE, "It appears that your PHP has the hash functions ".
                          "disabled, which are required for the 'cookie' ".
                          "session scheme.");
    }
    if (hash_hmac(
                  $auth["session_cookie"]["hash_algorithm"],
                  $json_data,
                  $auth['session_cookie']['secret']
                 ) == $hash)
    {
      $session_data = json_decode($json_data, true);
          
      /* Check for valid session data */
      if (isset($session_data['user']) &&
          isset($session_data['expiry']))
      {
        // Have basic data

        if ((($auth["session_cookie"]["session_expire_time"] == 0) &&
             ($session_data['expiry'] == 0)) ||
            ($session_data['expiry'] > time()))
        {
          // Expiry is OK
          
          if (!isset($session_data['ip']) ||
              ($session_data['ip'] == $REMOTE_ADDR))
          {
            // IP is OK
            $cached_username = $session_data['user'];
          }
        }
      }
      else
      {
        // Basic data checks failed
      }
    }
    else
    {
      error_log("Token is invalid, cookie has been tampered with or secret may have changed");
    }
  }

  return $cached_username;
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
  $user=getUserName();
  if ($user != "")
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
    print "<a id=\"user_list_link\" href=\"$user_list_link\">" . get_vocab('user_list') . "</a>\n";
  }
}

