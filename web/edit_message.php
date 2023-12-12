<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputDate;
use MRBS\Form\FieldTextarea;
use MRBS\Form\Form;

require 'defaultincludes.inc';


function get_field_display_until(array $message): FieldInputDate
{
  $field = new FieldInputDate();
  $field->setLabel(get_vocab('display_until'))
        ->setControlAttribute('name', 'message_until');

  if (isset($message['until']) && ($message['until'] !== ''))
  {
    // Translate the stored date and time into a date
    if (false !== ($date = DateTime::createFromFormat('Y-m-d\TH:i:s', $message['until'])))
    {
      $field->setControlAttribute(
        'value',
        $date->modify('-1 day')->format('Y-m-d')
      );
    }
  }

  return $field;
}


function get_field_message_text(array $message): FieldTextarea
{
  $field = new FieldTextarea();
  $field->setLabel(get_vocab('message'))
        ->setControlAttribute('name', 'message_text')
        ->setControlText($message['text'] ?? '');
  return $field;
}


function get_fieldset_submit_buttons() : ElementFieldset
{
  $fieldset = new ElementFieldset();

  // The back and submit buttons
  $field = new FieldDiv();

  $back = new ElementInputSubmit();
  $back->setAttributes(array(
    'name'           => 'back_button',
    'value'          => get_vocab('back'),
    'formnovalidate' => true)
  );

  $submit = new ElementInputSubmit();
  $submit->setAttributes(array(
    'class' => 'default_action',
    'name'  => 'save_button',
    'value' => get_vocab('save'))
  );

  $field->setAttribute('class', 'submit_buttons')
        ->addLabelClass('no_suffix')
        ->addLabelElement($back)
        ->addControlElement($submit);

  $fieldset->addElement($field);

  return $fieldset;
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

// Get the current message, if any
$message = message_get();

// Construct the form
$form = new Form();

$form->setAttributes(array(
  'class'  => 'standard',
  'id'     => 'message',
  'action' => multisite('edit_message_handler.php'),
  'method' => 'post')
);

$fieldset = new ElementFieldset();
$fieldset->addLegend(get_vocab('edit_message'));

$fieldset->addElement(get_field_message_text($message))
         ->addElement(get_field_display_until($message));

$form->addElement($fieldset)
     ->addElement(get_fieldset_submit_buttons())
     ->render();

print_footer();
