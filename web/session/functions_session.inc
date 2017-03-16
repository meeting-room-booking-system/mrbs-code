<?php
namespace MRBS;


function print_login_failed()
{
  echo "<p>" . get_vocab('unknown_user') . "</p>\n";
}


/*
  Display the login form. 
  Will eventually return to $target_url with query string returl=$returl
*/
function print_login_form($action, $target_url, $returl)
{
  $html = '';
  
  $html .= "<form class=\"form_general\" id=\"logon\" method=\"post\"".
           " action=\"" . htmlspecialchars($action) . "\">\n";
  $html .= "<fieldset>\n";
  $html .= "<legend>" . get_vocab("please_login") . "</legend>\n";
  
  $html .= "<div>\n";
  
  if (function_exists(__NAMESPACE__ . "\\canValidateByEmail")
      && canValidateByEmail())
  {
    $placeholder = get_vocab("username_or_email");
  }
  else
  {
    $placeholder = get_vocab("users.name");
  }
  
  $html .= "<label for=\"NewUserName\" title=\"$placeholder\">" . 
           get_vocab("user") . "</label>\n";
  $html .= "<input type=\"text\" id=\"NewUserName\" name=\"NewUserName\" " .
           " placeholder=\"$placeholder\">\n";
  $html .= "</div>\n";
  
  $html .= "<div>\n";
  $html .= "<label for=\"NewUserPassword\">" . get_vocab("users.password") . "</label>\n";
  $html .= "<input type=\"password\" id=\"NewUserPassword\" name=\"NewUserPassword\">\n";
  $html .= "</div>\n";


  $html .= "<input type=\"hidden\" name=\"returl\" value=\"" . htmlspecialchars($returl) . "\">\n";
  $html .= "<input type=\"hidden\" name=\"target_url\" value=\"" . htmlspecialchars($target_url) . "\">\n";
  $html .= "<input type=\"hidden\" name=\"Action\" value=\"SetName\">\n";
  
  $html .= "<div id=\"logon_submit\">\n";
  $html .= "<input class=\"submit\" type=\"submit\" value=\"" . get_vocab('login') . "\">\n";
  $html .= "</div>\n";
  
  $html .= "</fieldset>\n";
  $html .= "</form>\n";

  $html .= "</div>";  // Close of the contents div
  
  echo $html;
  
  // Print footer and exit
  print_footer(TRUE);
}


// Generate the "You are xxxx" link, which gives a report on the user's upcoming bookings.
function print_report_link($user)
{
  $href = "report.php?creatormatch=" . urlencode($user) . "&amp;phase=2";
  $html = "<a href=\"$href\" title=\"" . get_vocab('show_my_entries') . "\">" .
           get_vocab('you_are') . " " . htmlspecialchars($user) .
          "</a>\n";
  echo $html;
}


function print_unknown_user()
{
  $html = "<a href=\"\">" . get_vocab('unknown_user') . "</a>\n";
  echo $html;
}


function print_logon_button($target_url)
{
  $html = '';
  
  $html .= "<form method=\"post\" action=\"admin.php\">\n";
  $html .= "<div>\n";
  $html .= "<input type=\"hidden\" name=\"target_url\" value=\"" . htmlspecialchars($target_url) . "\">\n";
  $html .= "<input type=\"hidden\" name=\"Action\" value=\"QueryName\">\n";
  $html .= "<input type=\"submit\" value=\"" . get_vocab('login') . "\">\n";
  $html .= "</div>\n";
  $html .= "</form>\n";
  
  echo $html;
}


function print_logoff_button($target_url)
{
  $html = '';
  
  $html .= "<form method=\"post\" action=\"admin.php\">\n";
  $html .= "<div>\n";
  $html .= "<input type=\"hidden\" name=\"target_url\" value=\"" . htmlspecialchars($target_url) . "\">\n";
  $html .= "<input type=\"hidden\" name=\"Action\" value=\"SetName\">\n";
  $html .= "<input type=\"hidden\" name=\"NewUserName\" value=\"\">\n";
  $html .= "<input type=\"hidden\" name=\"NewUserPassword\" value=\"\">";
  $html .= "<input type=\"submit\" value=\"" . get_vocab('logoff') . "\">\n";
  $html .= "</div>\n";
  $html .= "</form>\n";
  
  echo $html;
}


function print_userlist_link()
{
  global $auth;
  
  if ($auth['type'] == 'db')
  {
    echo "<a id=\"user_list_link\" href=\"edit_users.php\">" . get_vocab('user_list') . "</a>\n";
  }
}
