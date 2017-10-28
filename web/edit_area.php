<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputText;

// TO DO -------------------------------------------
$errors = array();  // Temporary measure (should come from trying to update the database)
//$errors = array('invalid_email', 'invalid_resolution', 'too_many_slots');  // testing
// -------------------------------------------------

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


function get_fieldset_errors($errors)
{
  $fieldset = new ElementFieldset();
  $fieldset->addLegend('')
           ->setAttribute('class', 'error');
  
  foreach ($errors as $error)
  {
    $element = new ElementP();
    $element->setText(get_vocab($error));
    $fieldset-> addElement($element);
  }
  
  return $fieldset;
}


function get_fieldset_general($data)
{
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('general_settings'));
  
  // Area name
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('id'    => 'area_name',
                                     'name'  => 'area_name',
                                     'value' => $data['area_name']));
  $fieldset->addElement($field);
  
  // Sort key
  $field = new FieldInputText();
  $field->setLabel(get_vocab('sort_key'))
        ->setLabelAttributes(array('title' => get_vocab('sort_key_note')))
        ->setControlAttributes(array('id'    => 'sort_key',
                                     'name'  => 'sort_key',
                                     'value' => $data['sort_key']));
  $fieldset->addElement($field);
                                     
  // Status - Enabled or Disabled
  $options = array('0' => get_vocab("enabled"),
                   '1' => get_vocab("disabled"));
  $value = ($data['disabled']) ? '1' : '0';
  $field = new FieldInputRadioGroup();
  $field->setAttribute('id', 'status')
        ->setLabel(get_vocab('status'))
        ->setLabelAttributes(array('title' => get_vocab('disabled_area_note')))
        ->addRadioOptions($options, 'area_disabled', $value, true);
  $fieldset->addElement($field);
  
  return $fieldset;
}


// Check the user is authorised for this page
checkAuthorised();

print_header($day, $month, $year, isset($area) ? $area : null, isset($room) ? $room : null);

// Get the details for this area
if (!isset($area) || is_null($data = get_area_details($area)))
{
  echo "<p>" . get_vocab('invalid_area') . "</p>\n";
}

// Generate the form
$form = new Form();

$attributes = array('id'     => 'edit_area',
                    'class'  => 'standard',
                    'action' => 'edit_area_handler.php',
                    'method' => 'post');
                    
$form->setAttributes($attributes)
     ->addHiddenInput('area', $area);

$outer_fieldset = new ElementFieldset();
$outer_fieldset->addLegend(get_vocab('editarea'));
               
$outer_fieldset->addElement(get_fieldset_errors($errors));
$outer_fieldset->addElement(get_fieldset_general($data));

$form->addElement($outer_fieldset);

$form->render();


output_trailer();