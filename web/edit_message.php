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

$form = new Form();

$form->setAttributes(array(
  'class'  => 'standard',
  'id'     => 'message',
  'action' => multisite('edit_message_handler.php'),
  'method' => 'post')
);

$fieldset = new ElementFieldset();
$fieldset->addLegend(get_vocab('edit_message'));

$text_field = new FieldTextarea();
$text_field->setLabel(get_vocab('message'))
           ->setControlAttribute('name', 'message_text');

$until_field = new FieldInputDate();
$until_field->setLabel(get_vocab('display_until'))
            ->setControlAttribute('name', 'message_until');

$fieldset->addElement($text_field)
         ->addElement($until_field);

$form->addElement($fieldset)
     ->addElement(get_fieldset_submit_buttons())
     ->render();

print_footer();
