<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldSelect;
use MRBS\Form\Form;

require "defaultincludes.inc";

function print_form() : void
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
  $areas = get_area_names();
  $field->setLabel(get_vocab('area'))
        ->addSelectOptions($areas, $area, true)
        ->setControlAttributes(array(
              'name' => 'area'
            ));
  $fieldset->addElement($field);

  // Room
  $field = new FieldSelect();
  $options = array();
  foreach($areas as $area_id => $area_name)
  {
    $rooms = get_room_names($area_id);
    if (!empty($rooms))
    {
      $options[$area_name] = $rooms;
    }
  }
  $field->setLabel(get_vocab('room'))
        ->addSelectOptions($options, $room, true)
        ->setControlAttributes(array(
          'name' => 'room'
        ));

  $fieldset->addElement($field);

  $form->addElement($fieldset);

  // The Back and Enter buttons
  $fieldset = new ElementFieldset();
  $field = new FieldDiv();

  // Only include a Back button if there's somewhere to go back to
  $return_url = session()->getReferrer();
  if (isset($return_url))
  {
    $back = new ElementInputSubmit();
    $back->setAttributes(array(
        'name' => 'back_button',
        'value' => get_vocab('back'),
        'formnovalidate' => true)
      );
    $form->addHiddenInput('return_url', $return_url);
  }

  $submit = new ElementInputSubmit();
  $submit->setAttributes(array(
      'class' => 'default_action',
      'name'  => 'enter_button',
      'value' => get_vocab('enter'))
    );

  $field->setAttribute('class', 'submit_buttons')
        ->addLabelClass('no_suffix')
        ->addControlElement($submit);

  if (isset($back))
  {
    $field->addLabelElement($back);
  }

  $fieldset->addElement($field);
  $form->addElement($fieldset);

  $form->render();
}

// Check the user is authorised for this page
if (!checkAuthorised(this_page()))
{
  exit;
}

// Get non-standard form variables
$mode = get_form_var('mode');
$return_url = get_form_var('return_url');
$back_button = get_form_var('back_button');
$enter_button = get_form_var('enter_button');

if (!empty($back_button))
{
  location_header((!empty($return_url)) ? $return_url : multisite("index.php"));
  // location_header() includes an exit
}

if (!empty($enter_button))
{
  // Phase 2 - Check the CSRF token enter kiosk mode
  Form::checkToken();
  location_header(multisite("index.php?kiosk=$mode&area=$area&room=$room"));
  // location_header() includes an exit
}

// Phase 1

// Print the page header
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

print_header($context);

echo "<h1>" . get_vocab('kiosk_mode') . "</h1>\n";
print_form();
print_footer();
