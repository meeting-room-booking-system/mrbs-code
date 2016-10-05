<?php
namespace MRBS;

define('WP_USE_THEMES', false);
require_once MRBS_ROOT . '/'. $auth['wordpress']['rel_path'] . '/wp-load.php';

// Get non-standard form variables
$Action = get_form_var('Action', 'string');
$NewUserName = get_form_var('NewUserName', 'string');
$NewUserPassword = get_form_var('NewUserPassword', 'string');
$TargetURL = get_form_var('TargetURL', 'string');
$returl = get_form_var('returl', 'string');


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


/*
  Target of the form with sets the URL argument "Action=SetName".
  Will eventually return to URL argument "TargetURL=whatever".
*/
if (isset($Action) && ($Action == "SetName"))
{
  if ($NewUserName == "")
  {
    wp_logout();
  }
  else
  {
    $credentials = array();
    $credentials['user_login'] = $NewUserName;
    $credentials['user_password'] = $NewUserPassword;
    $credentials['remember'] = false;
    $wp_user = wp_signon($credentials);
    if (is_wp_error($wp_user))
    {
      $errors = $wp_user;  // $errors is a WordPress global
      print_header(0, 0, 0, 0, "");
      echo "<p>" . apply_filters ('login_errors', $wp_user->get_error_message()) . "</p>\n";
      printLoginForm($TargetURL);
      exit();
    }
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
  echo "<br>\n";
  echo "<p>Please click <a href=\"" . htmlspecialchars($TargetURL) . "\">here</a> if you're not redirected automatically to the page you requested.</p>\n";
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

  // __() and esc_attr__() are WordPress functions
  
  echo '<form class="form_general" id="logon" method="post" action="' . htmlspecialchars(this_page()) . "\">\n";
  echo "<fieldset>\n";
  echo '<legend>' . get_vocab("please_login") . "</legend>\n";
  echo "<div>\n";
  echo '<label for="NewUserName">' .  __('Username or Email') . ":</label>\n";
  echo "<input type=\"text\" id=\"NewUserName\" name=\"NewUserName\">\n";
  echo "</div>\n";
  echo "<div>\n";
  echo '<label for="NewUserPassword">' . __('Password') . ":</label>\n";
  echo "<input type=\"password\" id=\"NewUserPassword\" name=\"NewUserPassword\">\n";
  echo "</div>\n";

  // We need to preserve the original calling page, so that it's there when we eventually get
  // to the TargetURL (especially if that's edit_entry.php).  If this is the first time through then $HTTP_REFERER holds
  // the original caller.    If this is the second time through we will have stored it in $returl.
  if (!isset($returl))
  {
    $returl = isset($HTTP_REFERER) ? $HTTP_REFERER : "";
  }
  echo "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";

  echo '<input type="hidden" name="TargetURL" value="' . htmlspecialchars($TargetURL) ."\">\n";
  echo "<input type=\"hidden\" name=\"Action\" value=\"SetName\">\n";
  echo "<div id=\"logon_submit\">\n";
  echo '<input class="submit" type="submit" value="' . esc_attr__('Log In') . "\">\n";
  echo "</div>\n";
  echo "</fieldset>\n";
  echo "</form>\n";
  echo "</div>\n";  // Close of the contents div
  
  // Print footer and exit
  print_footer(TRUE);
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

  $TargetURL = this_page();
  if (isset($QUERY_STRING))
  {
    $TargetURL = $TargetURL . "?" . $QUERY_STRING;
  }
  printLoginForm($TargetURL);

  exit();
}


function getUserName()
{
  if (!is_user_logged_in())
  {
    return NULL;
  }
  
  $current_user = wp_get_current_user();
  return $current_user->user_login;
}


// Print the logon entry on the top banner.
function PrintLogonBox()
{
  global $PHP_SELF, $QUERY_STRING, $user_list_link, $day, $month, $year;

  $TargetURL = this_page();
  if (isset($url_base) && ($url_base !== ''))
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
      "from_year=$year&amp;to_day=1&amp;to_month=12&amp;to_year=2030&amp;areamatch=&amp;".
      "roommatch=&amp;namematch=&amp;descrmatch=&amp;summarize=1&amp;sortby=r&amp;display=d&amp;".
      "sumby=d&amp;creatormatch=".urlencode($user); ?>

                <a href="<?php echo "$search_string\" title=\""
         . get_vocab('show_my_entries') . "\">" . get_vocab('you_are')." "
         . htmlspecialchars($user) ?></a>
                <form method="post" action="admin.php">
                  <div>
                    <input type="hidden" name="TargetURL" value="<?php echo htmlspecialchars($TargetURL) ?>">
                    <input type="hidden" name="Action" value="SetName">
                    <input type="hidden" name="NewUserName" value="">
                    <input type="hidden" name="NewUserPassword" value="">
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

