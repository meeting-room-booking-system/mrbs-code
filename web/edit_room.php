<?php
namespace MRBS;

use MRBS\Form\Form;
//use MRBS\Form\ElementDiv;
//use MRBS\Form\ElementInputCheckbox;
//use MRBS\Form\ElementInputNumber;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementFieldset;
//use MRBS\Form\ElementLegend;
use MRBS\Form\ElementP;
//use MRBS\Form\ElementSelect;
//use MRBS\Form\ElementSpan;
//use MRBS\Form\FieldButton;
//use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputCheckbox;
//use MRBS\Form\FieldInputCheckboxGroup;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
//use MRBS\Form\FieldInputTime;
use MRBS\Form\FieldSelect;
//use MRBS\Form\FieldSpan;
use MRBS\Form\FieldTextarea;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


function get_custom_fields($data)
{
  global $tbl_room, $standard_fields, $text_input_max;
  global $is_admin;
  
  $result = array();
  $disabled = !$is_admin;
  
  // Get the information about the fields in the room table
  $fields = db()->field_info($tbl_room);
  
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $standard_fields['room']))
    {
      $label = get_loc_field_name($tbl_room, $field['name']);
      $name = VAR_PREFIX . $field['name'];
      $value = $data[$field['name']];
      
      // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
      // assume are intended to be booleans)
      if (($field['nature'] == 'boolean') || 
          (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
      {
        $field = new FieldInputCheckbox();
        $field->setLabel($label)
              ->setControlAttributes(array('name'     => $name,
                                           'disabled' => $disabled))
              ->setChecked($value);
      }
      // Output a textarea if it's a character string longer than the limit for a
      // text input
      elseif (($field['nature'] == 'character') && isset($field['length']) && ($field['length'] > $text_input_max))
      {
        $field = new FieldTextarea();
        $field->setLabel($label)
              ->setControlAttributes(array('name'     => $name,
                                           'disabled' => $disabled))
              ->setControlText($value);
      }
      // Otherwise output a text input
      else
      {
        $field = new FieldInputText();
        $field->setLabel($label)
              ->setControlAttributes(array('name'     => $name,
                                           'value'    => $value,
                                           'disabled' => $disabled));
      }
      $result[] = $field;
    }
  }
  
  return $result;
}


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
  global $is_admin, $auth;
  
  $disabled = !$is_admin;
  
  $fieldset = new ElementFieldset();

  // The area select
  $areas = get_area_names($all=true);
  $field = new FieldSelect();
  $field->setLabel(get_vocab('area'))
        ->setControlAttributes(array('name'     => 'new_area',
                                     'disabled' => $disabled))
        ->addSelectOptions($areas, $data['area_id']);
  $fieldset->addElement($field);
  
  // Room name
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('name'     => 'room_name',
                                     'value'    => $data['room_name'],
                                     'required' => true,
                                     'disabled' => $disabled));
  $fieldset->addElement($field);
  
  // Sort key
  if ($is_admin)
  {
    $field = new FieldInputText();
    $field->setLabel(get_vocab('sort_key'))
          ->setLabelAttribute('title', get_vocab('sort_key_note'))
          ->setControlAttributes(array('name'     => 'sort_key',
                                       'value'    => $data['sort_key'],
                                       'disabled' => $disabled));
    $fieldset->addElement($field);
  }
  
  // Status - Enabled or Disabled
  if ($is_admin)
  {
    $options = array('0' => get_vocab('enabled'),
                     '1' => get_vocab('disabled'));
    $value = ($data['disabled']) ? '1' : '0';
    $field = new FieldInputRadioGroup();
    $field->setLabel(get_vocab('status'))
          ->setLabelAttributes(array('title' => get_vocab('disabled_room_note')))
          ->addRadioOptions($options, 'room_disabled', $value, true, $disabled);
    $fieldset->addElement($field);
  }
  
  // Description
  $field = new FieldInputText();
  $field->setLabel(get_vocab('description'))
        ->setControlAttributes(array('name'     => 'description',
                                     'value'    => $data['description'],
                                     'disabled' => $disabled));
  $fieldset->addElement($field);
  
  // Capacity
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('capacity'))
        ->setControlAttributes(array('name'     => 'capacity',
                                     'min'      => '0',
                                     'value'    => $data['capacity'],
                                     'disabled' => $disabled));
  $fieldset->addElement($field);
  
  // Area admin email
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('room_admin_email'))
        ->setLabelAttribute('title', get_vocab('email_list_note'))
        ->setControlAttributes(array('name'     => 'room_admin_email',
                                     'value'    => $data['room_admin_email'],
                                     'multiple' => true,
                                     'disabled' => $disabled));
  $fieldset->addElement($field);
  
  // The custom HTML
  if ($is_admin && $auth['allow_custom_html'])
  {
    // Only show the raw HTML to admins.  Non-admins will see the rendered HTML
    $field = new FieldTextarea();
    $field->setLabel(get_vocab('custom_html'))
          ->setLabelAttribute('title', get_vocab('custom_html_note'))
          ->setControlAttribute('name', 'custom_html')
          ->setControlText($data['custom_html']);
    $fieldset->addElement($field);
  }
  
  // Then the custom fields
  $fields = get_custom_fields($data);
  $fieldset->addElements($fields);
  
  // The Submit and Back buttons
  $field = new FieldInputSubmit();
  
  $back = new ElementInputSubmit();
  $back->setAttributes(array('value'      => get_vocab('backadmin'),
                             'formaction' => 'admin.php'));
  $field->addLabelClass('no_suffix')
        ->addLabelElement($back)
        ->setControlAttribute('value', get_vocab('change'));
  if (!$is_admin)
  {
    $field->removeControl();
  }
  $fieldset->addElement($field);
  
  return $fieldset;
}


// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$required_level = (isset($max_level) ? $max_level : 2);
$is_admin = (authGetUserLevel($user) >= $required_level);

print_header($day, $month, $year, isset($area) ? $area : null, isset($room) ? $room : null);

// Get the details for this room
if (empty($room) || is_null($data = get_room_details($room)))
{
  fatal_error(get_vocab('invalid_room'));
}

$errors = get_form_var('errors', 'array');

// Generate the form
$form = new Form();

$attributes = array('id'     => 'edit_room',
                    'class'  => 'standard',
                    'action' => 'edit_room_handler.php',
                    'method' => 'post');
                    
// Non-admins will only be allowed to view room details, not change them
$legend = ($is_admin) ? get_vocab('editroom') : get_vocab('viewroom');
                    
$form->setAttributes($attributes)
     ->addHiddenInput('room', $data['id'])
     ->addHiddenInput('old_area', $data['area_id'])
     ->addHiddenInput('old_room_name', $data['room_name']);

$outer_fieldset = new ElementFieldset();

$outer_fieldset->addLegend($legend)
               ->addElement(get_fieldset_errors($errors))
               ->addElement(get_fieldset_general($data));

$form->addElement($outer_fieldset);

$form->render();

if ($auth['allow_custom_html'])
{
  // Now the custom HTML
  echo "<div id=\"custom_html\">\n";
  // no htmlspecialchars() because we want the HTML!
  echo (isset($data['custom_html'])) ? $data['custom_html'] . "\n" : "";
  echo "</div>\n";
}


output_trailer();