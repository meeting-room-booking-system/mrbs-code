<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
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
  
  // Hidden inputs
  $hidden_inputs = array('returl'     => $returl,
                         'target_url' => $target_url,
                         'action'     => 'SetName');
  $form->addHiddenInputs($hidden_inputs);
  
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
                                     'placeholder' => $placeholder,
                                     'required'    => null,
                                     'autofocus'   => null));               
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
  $fieldset->addElement($field);
  
  $form->addElement($fieldset);
  
  $form->render();
  
  
  // The code below doesn't really belong here and ideally ought to be moved
  // to somewhere where you can see the opening <div> etc.

  echo "</div>\n";  // Close of the contents div
  
  // Print footer and exit
  print_footer(TRUE);
}


// Generate the username link, which gives a report on the user's upcoming bookings.
function print_report_link($user)
{
  $form = new Form();
  $form->setAttributes(array('id'     => 'show_my_entries',
                             'action' => 'report.php',
                             'method' => 'post'))
       ->addHiddenInputs(array('phase'        => '2',
                               'creatormatch' => $user));
                               
  $submit = new ElementInputSubmit();
  $submit->setAttributes(array('title' => get_vocab('show_my_entries'),
                               'value' => $user));
  $form->addElement($submit);
                               
  $form->render();
}


function print_unknown_user()
{
  $html = "<a href=\"\">" . get_vocab('unknown_user') . "</a>\n";
  echo $html;
}


function print_logon_button($target_url)
{
  $form = new Form();
  $form->setAttributes(array('method' => 'post',
                             'action' => 'admin.php'));
  
  // Add the hidden fields
  $hidden_inputs = array('target_url' => $target_url,
                         'action'     => 'QueryName');
  $form->addHiddenInputs($hidden_inputs);
  
  // The submit button
  $element = new ElementInputSubmit();
  $element->setAttribute('value', get_vocab('login'));
  $form->addElement($element);
  
  $form->render();
}


function print_logoff_button($target_url)
{
  $form = new Form();
  $form->setAttributes(array('method' => 'post',
                             'action' => 'admin.php'));
  
  // Add the hidden fields
  $hidden_inputs = array('target_url' => $target_url,
                         'action'     => 'SetName',
                         'username'   => '',
                         'password'   => '');
  $form->addHiddenInputs($hidden_inputs);
  
  // The submit button
  $element = new ElementInputSubmit();
  $element->setAttribute('value', get_vocab('logoff'));
  $form->addElement($element);
  
  $form->render();
}


function print_userlist_link()
{
  global $auth;
  
  if ($auth['type'] == 'db')
  {
    echo "<a id=\"user_list_link\" href=\"edit_users.php\">" . get_vocab('user_list') . "</a>\n";
  }
}
