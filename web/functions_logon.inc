<?php
namespace MRBS;

/*
  Display the login form. 
  Will eventually return to $target_url with query string returl=$returl
*/
function printLoginForm($action, $target_url, $returl)
{
  $html = '';

  $html .= "<form class=\"form_general\" id=\"logon\" method=\"post\"".
           " action=\"" . htmlspecialchars($action) . "\">\n";
  $html .= "<fieldset>\n";
  $html .= "<legend>" . get_vocab("please_login") . "</legend>\n";
  
  $html .= "<div>\n";
  if (function_exists(__NAMESPACE__ . "\\authValidateEmail"))
  {
    $placeholder = get_vocab("username_or_email");
  }
  else
  {
    $placeholder = get_vocab("username");
  }
  $html .= "<label for=\"NewUserName\">" . get_vocab("user") . ":</label>\n";
  $html .= "<input type=\"text\" id=\"NewUserName\" name=\"NewUserName\" placeholder=\"$placeholder\">\n";
  $html .= "</div>\n";
  
  $html .= "<div>\n";
  $html .= "<label for=\"NewUserPassword\">" . get_vocab("users.password") . ":</label>\n";
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
