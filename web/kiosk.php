<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldSelect;
use MRBS\Form\Form;

require "defaultincludes.inc";


function get_field_password(bool $new) : FieldInputPassword
{
  $field = new FieldInputPassword();
  $field->setLabel(get_vocab('kiosk_password'))
        ->setControlAttributes(array(
              'name'      => 'kiosk_password',
              'required'  => true)
            );

  if ($new)
  {
    $field->setControlAttribute('autocomplete', 'new-password');
  }


  return $field;
}


function get_fieldset_buttons(?string $returl, string $save_name, string $save_value) : ElementFieldset
{
  // The Back and Enter buttons
  $fieldset = new ElementFieldset();
  $field = new FieldDiv();

  // Only include a Back button if there's somewhere to go back to
  if (isset($returl))
  {
    $back = new ElementInputSubmit();
    $back->setAttributes(array(
        'name' => 'back_button',
        'value' => get_vocab('back'),
        'formnovalidate' => true)
      );
  }

  $submit = new ElementInputSubmit();
  $submit->setAttributes(array(
      'class' => 'default_action',
      'name'  => $save_name,
      'value' => $save_value)
    );

  $field->setAttribute('class', 'submit_buttons')
        ->addLabelClass('no_suffix')
        ->addControlElement($submit);

  if (isset($back))
  {
    $field->addLabelElement($back);
  }

  $fieldset->addElement($field);

  return $fieldset;
}


function print_enter_form() : void
{
  global $kiosk_default_mode;
  global $area, $room;

  $form = new Form();

  $form->setAttributes(array(
      'class'   => 'standard',
      'id'      => 'kiosk',
      'action'  => multisite(this_page()),
      'method'  => 'post')
    );

  $fieldset = new ElementFieldset();
  $fieldset->addLegend('');

  // The mode
  $field = new FieldInputRadioGroup();
  $options = array(
      'area' => get_vocab('area'),
      'room' => get_vocab('room')
    );

  $value = (isset($kiosk_default_mode) && array_key_exists($kiosk_default_mode, $options)) ? $kiosk_default_mode : 'room';

  $field->setLabel(get_vocab('mode'))
        ->addRadioOptions($options, 'mode', $value, true);

  $fieldset->addElement($field);

  // Area
  $field = new FieldSelect();
  $areas = new Areas();
  $area_names = $areas->getNames();
  $field->setLabel(get_vocab('area'))
        ->addSelectOptions($area_names, $area, true)
        ->setControlAttributes(array(
              'name' => 'area'
            ));
  $fieldset->addElement($field);

  // Room
  $field = new FieldSelect();
  $options = array();
  foreach($area_names as $area_id => $area_name)
  {
    $rooms = new Rooms($area_id);
    if (!empty($rooms))
    {
      $options[$area_name] = $rooms->getNames();
    }
  }
  $field->setLabel(get_vocab('room'))
        ->addSelectOptions($options, $room, true)
        ->setControlAttributes(array(
          'name' => 'room'
        ));

  $fieldset->addElement($field);

  // The kiosk password
  $fieldset->addElement(get_field_password(true));

  $form->addElement($fieldset);

  // The Back and Enter buttons
  $return_url = session()->getReferrer();
  $form->addElement(get_fieldset_buttons($return_url, 'enter_button', get_vocab('enter')));
  if (isset($return_url))
  {
    $form->addHiddenInput('return_url', $return_url);
  }


  $form->render();
}


function print_exit_form() : void
{
  $form = new Form();

  $form->setAttributes(array(
      'class'   => 'standard',
      'id'      => 'kiosk',
      'action'  => multisite(this_page()),
      'method'  => 'post')
  );

  $fieldset = new ElementFieldset();
  $fieldset->addLegend('');

  $fieldset->addElement(get_field_password(false));
  $form->addElement($fieldset);

  $return_url = $_SESSION['kiosk_url'] ?? null;
  $form->addElement(get_fieldset_buttons($return_url, 'exit_button', get_vocab('exit')));
  if (isset($return_url))
  {
    $form->addHiddenInput('return_url', $return_url);
  }

  $form->render();
}


$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => $room ?? null,
    'kiosk'     => $kiosk ?? null
  );

if (!empty(get_form_var('back_button')))
{
  $return_url = get_form_var('return_url');
  location_header((!empty($return_url)) ? $return_url : multisite("index.php"));
  // location_header() includes an exit
}

// Check whether they are trying to exit kiosk mode
if (!empty($kiosk) && isset($_SESSION['kiosk_password_hash']))
{
  // Check the CSRF token
  Form::checkToken();
  print_header($context);
  echo "<h1>" . get_vocab('exit_kiosk_mode') . "</h1>\n";
  print_exit_form();
  print_footer(true);
}

$kiosk_password = get_form_var('kiosk_password');

if (!empty(get_form_var('exit_button')))
{
  // Phase 2 (Exit) - Check the CSRF token
  Form::checkToken();
  $location = $_SESSION['kiosk_url'] ?? 'index.php&kiosk=' . $kiosk_default_mode;
  if (isset($_SESSION['kiosk_password_hash']) && password_verify($kiosk_password, $_SESSION['kiosk_password_hash']))
  {
    unset($_SESSION['kiosk_url']);
    unset($_SESSION['kiosk_password_hash']);
    $location = remove_query_parameter($location, 'kiosk');
  }
  location_header($location);
  // location_header() includes an exit
}

// Check the user is authorised for this page
if (!checkAuthorised(this_page()))
{
  exit;
}

if (!empty(get_form_var('enter_button')))
{
  // Phase 2 (Enter) - Check the CSRF token enter kiosk mode
  Form::checkToken();
  $mode = get_form_var('mode');

  if (method_exists(session(), 'logoffUser'))
  {
    session()->logoffUser();
  }
  session()->init(0); // We only want the session to expire when the browser is closed

  $kiosk_url = multisite("index.php?kiosk=$mode&area=$area&room=$room");
  $_SESSION['kiosk_password_hash'] = password_hash($kiosk_password, PASSWORD_DEFAULT);
  $_SESSION['kiosk_url'] = $kiosk_url;

  location_header($kiosk_url);
  // location_header() includes an exit
}

// Phase 1

// Print the page header
print_header($context);
echo "<h1>" . get_vocab('enter_kiosk_mode') . "</h1>\n";
print_enter_form();
print_footer();
