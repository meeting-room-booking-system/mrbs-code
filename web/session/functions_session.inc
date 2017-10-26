<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputHidden;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;


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
  $form = new Form();
  $form->setAttributes(array('class'  => 'form_general',
                             'id'     => 'logon',
                             'method' => 'post',
                             'action' => $action));
  
  // Hidden input - 'returl'
  $element = new ElementInputHidden();
  $element->setAttributes(array('name'  => 'returl',
                                'value' => $returl));
  $form->addElement($element);
  
  // Hidden input - 'target_url'
  $element = new ElementInputHidden();
  $element->setAttributes(array('name'  => 'target_url',
                                'value' => $target_url));
  $form->addElement($element);
  
  // Hidden input - 'action'
  $element = new ElementInputHidden();
  $element->setAttributes(array('name'  => 'action',
                                'value' => 'SetName'));
  $form->addElement($element);
  
  // Now for the visible fields
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('please_login'));
  
  // The username field
  if (function_exists(__NAMESPACE__ . "\\canValidateByEmail")
      && canValidateByEmail())
  {
    $placeholder = get_vocab("username_or_email");
  }
  else
  {
    $placeholder = get_vocab("users.name");
  }
  
  $field = new FieldInputText();
  $field->setLabel(get_vocab('user'))
        ->setLabelAttributes(array('title' => $placeholder))
        ->setControlAttributes(array('id'          => 'username',
                                     'name'        => 'username',
                                     'placeholder' => $placeholder));               
  $fieldset->addElement($field);
  
  // The password field
  $field = new FieldInputPassword();
  $field->setLabel(get_vocab('users.password'))
        ->setControlAttributes(array('id'          => 'password',
                                     'name'        => 'password'));               
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => get_vocab('login')));
  $fieldset-> addElement($field);
  
  $form->addElement($fieldset);
  
  $form->render();
  
  
  // The code below doesn't really belong here and ideally ought to be moved
  // to somewhere where you can see the opening <div> etc.

  echo "</div>\n";  // Close of the contents div
  
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
  $html .= "<input type=\"hidden\" name=\"action\" value=\"QueryName\">\n";
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
  $html .= "<input type=\"hidden\" name=\"action\" value=\"SetName\">\n";
  $html .= "<input type=\"hidden\" name=\"username\" value=\"\">\n";
  $html .= "<input type=\"hidden\" name=\"password\" value=\"\">";
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


function get_cookie_path()
{
  global $cookie_path_override, $PHP_SELF;
  
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
  
  return $cookie_path;
}
