<?php
namespace MRBS;

require_once MRBS_ROOT . '/auth/cms/wordpress.inc';

// Get non-standard form variables
$Action = get_form_var('Action', 'string');
$NewUserName = get_form_var('NewUserName', 'string');
$NewUserPassword = get_form_var('NewUserPassword', 'string');
$target_url = get_form_var('target_url', 'string');
$returl = get_form_var('returl', 'string');

// We need to preserve the original calling page, so that it's there when we eventually get to the
// target_url (especially if that's edit_entry.php).  If this is the first time through then $HTTP_REFERER
// holds the original caller.    If this is the second time through we will have stored it in $returl.
if (!isset($returl))
{
  $returl = isset($HTTP_REFERER) ? $HTTP_REFERER : '';
}

/*
  Target of the form with sets the URL argument "Action=QueryName".
  Will eventually return to URL argument "target_url=whatever".
*/
if (isset($Action) && ($Action == "QueryName"))
{
  print_header(0, 0, 0, 0, "");
  printLoginForm(this_page(), $target_url, $returl);
  exit();
}


/*
  Target of the form with sets the URL argument "Action=SetName".
  Will eventually return to URL argument "target_url=whatever".
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
      printLoginForm(this_page(), $target_url, $returl);
      exit();
    }
  }
  
  // preserve the original $HTTP_REFERER by sending it as a GET parameter
  if (!empty($returl))
  {
    // check to see whether there's a query string already
    if (strpos($target_url, '?') === false)
    {
      $target_url .= "?returl=" . urlencode($returl);
    }
    else
    {
      $target_url .= "&returl=" . urlencode($returl);
    }
  }

  header ("Location: $target_url"); /* Redirect browser to initial page */
  
  /* Note HTTP 1.1 mandates an absolute URL. Most modern browsers support relative URLs,
    which allows to work around problems with DNS inconsistencies in the server name.
    Anyway, if the browser cannot redirect automatically, the manual link below will work. */
  print_header(0, 0, 0, 0, "");
  echo "<br>\n";
  echo "<p>Please click <a href=\"" . htmlspecialchars($target_url) . "\">here</a> if you're not redirected automatically to the page you requested.</p>\n";
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
  global $QUERY_STRING;
  global $returl;

  print_header(0, 0, 0, 0, "");

  echo "<p>".get_vocab("norights")."</p>\n";

  $target_url = this_page();
  if (isset($QUERY_STRING))
  {
    $target_url = $target_url . "?" . $QUERY_STRING;
  }
  printLoginForm(this_page(), $target_url, $returl);

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
function print_logon_box()
{
  global $QUERY_STRING, $user_list_link, $day, $month, $year;

  $target_url = this_page();
  if (isset($url_base) && ($url_base !== ''))
  {
    $target_url = $url_base . '/' . $target_url;
  }
  if (isset($QUERY_STRING))
  {
    $target_url = $target_url . "?" . $QUERY_STRING;
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
                    <input type="hidden" name="target_url" value="<?php echo htmlspecialchars($target_url) ?>">
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
                    <input type="hidden" name="target_url" value="<?php echo htmlspecialchars($target_url) ?>">
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

