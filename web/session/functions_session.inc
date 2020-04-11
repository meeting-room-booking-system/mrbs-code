<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;


/*
  Display the login form. 
  Will eventually return to $target_url with query string returl=$returl
  If $error is set then an $error is printed.
  If $raw is true then the message is not HTML escaped
*/
function print_login_form($action, $target_url, $returl, $error=null, $raw=false)
{
  $form = new Form();
  $form->setAttributes(array('class'  => 'standard',
                             'id'     => 'logon',
                             'method' => 'post',
                             'action' => $action));
  
  // Hidden inputs
  $hidden_inputs = array('returl'     => $returl,
                         'target_url' => $target_url,
                         'action'     => 'SetName');
  $form->addHiddenInputs($hidden_inputs);
  
  // Now for the visible fields
  if (isset($error))
  {
    $p = new ElementP();
    $p->setText($error, false, $raw);
    $form->addElement($p);
  }
  
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
        ->setControlAttributes(array('id'           => 'username',
                                     'name'         => 'username',
                                     'placeholder'  => $placeholder,
                                     'required'     => true,
                                     'autofocus'    => true,
                                     'autocomplete' => 'username'));               
  $fieldset->addElement($field);
  
  // The password field
  $field = new FieldInputPassword();
  $field->setLabel(get_vocab('users.password'))
        ->setControlAttributes(array('id'           => 'password',
                                     'name'         => 'password',
                                     'autocomplete' => 'current-password'));               
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => get_vocab('login')));
  $fieldset->addElement($field);
  
  $form->addElement($fieldset);
  
  $form->render();
  
  // Print footer and exit
  print_footer(TRUE);
}


// Generate the username link, which gives a report on the user's upcoming bookings.
function print_report_link($user)
{
  // If possible, provide a link to the Report page, otherwise the Search page
  // and if that's not possible just print the username with no link.  (Note that
  // the Search page isn't the perfect solution because it searches for any bookings
  // containing the search string, not just those created by the user.)
  if (checkAuthorised('report.php', true))
  {
    $attributes = array('action' => multisite('report.php'));
    $hidden_inputs = array('phase'        => '2',
                           'creatormatch' => $user);
  }
  elseif (checkAuthorised('search.php', true))
  {
    $attributes = array('action' => multisite('search.php'));
    $hidden_inputs = array('search_str' => $user);
  }
  else
  {
    echo '<span>' . htmlspecialchars($user) . '</span>';
    return;
  }
  
  // We're authorised for either Report or Search so print the form.
  $form = new Form();
  
  $attributes['id'] = 'show_my_entries';
  $attributes['method'] = 'post';
  $form->setAttributes($attributes)
       ->addHiddenInputs($hidden_inputs);
                               
  $submit = new ElementInputSubmit();
  $submit->setAttributes(array('title' => get_vocab('show_my_entries'),
                               'value' => $user));
  $form->addElement($submit);
                               
  $form->render();
}


function print_logon_button($target_url)
{
  global $auth;
  global $multisite, $site;
  
  if ($auth['session'] == 'cas')
  {
    $action = $target_url;
  }
  else
  {
    $action = 'admin.php';
    if ($multisite && isset($site) && ($site !== ''))
    {
      $action .= "?site=$site";
    }
  }
  
  $form = new Form();
  $form->setAttributes(array('method' => 'post',
                             'action' => $action));
  
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
                             'action' => multisite('admin.php')));
  
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
