<?php
namespace MRBS;

use MRBS\Form\Element;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\Form;

require "defaultincludes.inc";


function generate_reset_request_form()
{
  $can_reset_by_email = auth()->canResetByEmail();

  $form = new Form();
  $form->setAttributes(array(
      'class'  => 'standard',
      'id'     => 'lost_password',
      'method' => 'post',
      'action' => multisite('reset_password_handler.php')
    ));

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(\MRBS\get_vocab('password_reset'));

  $field = new FieldDiv();
  $p = new ElementP();
  $text = ($can_reset_by_email) ? get_vocab('enter_username_or_email') : get_vocab('enter_username');
  $text .= ' ' . get_vocab('will_be_sent_instructions');
  $p->setText($text);
  $field->addControlElement($p);

  $fieldset->addElement($field);

  $tag = ($can_reset_by_email) ? 'username_or_email' : 'users.name';
  $placeholder = \MRBS\get_vocab($tag);

  $field = new FieldInputText();
  $field->setLabel(\MRBS\get_vocab('user'))
        ->setLabelAttributes(array('title' => $placeholder))
        ->setControlAttributes(array('id'           => 'username',
                                     'name'         => 'username',
                                     'placeholder'  => $placeholder,
                                     'required'     => true,
                                     'autofocus'    => true,
                                     'autocomplete' => 'username'));
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  // The submit button
  $fieldset = new ElementFieldset();
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => \MRBS\get_vocab('get_new_password')));
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  $form->render();
}


function generate_reset_form($user, $key)
{
  global $pwd_policy;

  $form = new Form();
  $form->setAttributes(array(
    'class'  => 'standard',
    'id'     => 'lost_password',
    'method' => 'post',
    'action' => multisite('reset_password_handler.php')
  ));

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(\MRBS\get_vocab('password_reset'));

  $field = new FieldDiv();
  $p = new ElementP();
  $text = get_vocab('enter_new_password');
  if (isset($pwd_policy))
  {
    $text .= ' ' . get_vocab('pwd_must_contain');
  }
  $p->setText($text);
  $field->addControlElement($p);

  if (isset($pwd_policy))
  {
    $ul = new Element('ul');
    $ul->setAttribute('id', 'pwd_policy');
    foreach ($pwd_policy as $rule => $value)
    {
      if ($value != 0)
      {
        $li = new Element('li');
        $li->setText(get_vocab('policy_' . $rule, $value));
        $ul->addElement($li);
      }
    }
    $field->addControlElement($ul);
  }

  $fieldset->addElement($field);

  for ($i=0; $i<2; $i++)
  {
    $field = new FieldInputPassword();
    $field->setLabel(\MRBS\get_vocab('users.password'))
          ->setControlAttributes(array('id' => "password$i",
                                       'name' => "password$i",
                                       'autocomplete' => 'new-password'));
    $fieldset->addElement($field);
  }

  $form->addElement($fieldset);

  // The submit button
  $fieldset = new ElementFieldset();
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => \MRBS\get_vocab('reset_password')));
  $fieldset->addElement($field);

  $form->addElement($fieldset);
  $form->render();
}


function generate_invalid_link()
{
  echo "<h2>" . get_vocab('invalid_link') . "</h2>\n";
  echo "<p>" . get_vocab('link_invalid') . "</p>";
}


// If we haven't got the ability to reset passwords then get out of here
if (!auth()->canResetPassword())
{
  location_header('index.php');
}

// Check the user is authorised for this page
checkAuthorised(this_page());

$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => isset($room) ? $room : null
  );

print_header($context);

$action = get_form_var('action', 'string');

if (isset($action) && ($action == 'reset'))
{
  $user = get_form_var('user', 'string');
  $key = get_form_var('key', 'string');
  if (auth()->isValidReset($user, $key))
  {
    generate_reset_form($user, $key);
  }
  else
  {
    generate_invalid_link();
  }
}
else
{
  generate_reset_request_form();
}

print_footer();
