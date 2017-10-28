<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;
use MRBS\Form\FieldTextarea;

// TO DO -------------------------------------------
$errors = array();  // Temporary measure (should come from trying to update the database)
//$errors = array('invalid_email', 'invalid_resolution', 'too_many_slots');  // testing
// -------------------------------------------------

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


function get_timezone_options()
{
  global $zoneinfo_outlook_compatible;
  
  $special_group = "Others";
  $timezones = array();
  $timezone_identifiers = timezone_identifiers_list();
  
  foreach ($timezone_identifiers as $value)
  {
    if (strpos($value, '/') === FALSE)
    {
      // There are some timezone identifiers (eg 'UTC') on some operating
      // systems that don't fit the Continent/City model.   We'll put them
      // into the special group
      $continent = $special_group;
      $city = $value;
    }
    else
    {
      // Note: timezone identifiers can have three components, eg
      // America/Argentina/Tucuman.    To keep things simple we will
      // treat anything after the first '/' as a single city and
      // limit the explosion to two
      list($continent, $city) = explode('/', $value, 2);
    }
    // Check that there's a VTIMEZONE definition
    $tz_dir = ($zoneinfo_outlook_compatible) ? TZDIR_OUTLOOK : TZDIR;  
    $tz_file = "$tz_dir/$value.ics";
    // UTC is a special case because we can always produce UTC times in iCalendar
    if (($city=='UTC') || is_readable($tz_file))
    {
      $key = ($continent == $special_group) ? $city : "$continent/$city";
      $timezones[$continent][$key] = $city;
    }
  }
  
  return $timezones;
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
  global $timezone;
  
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
  
  // Timezone
  $field = new FieldSelect();
  $field->setLabel(get_vocab('timezone'))
        ->setControlAttributes(array('id'   => 'area_timezone',
                                     'name' => 'area_timezone'))
        ->addSelectOptions(get_timezone_options(), $timezone);
  $fieldset->addElement($field);
  
  // Area admin email
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('area_admin_email'))
        ->setControlAttributes(array('id'       => 'area_admin_email',
                                     'name'     => 'area_admin_email',
                                     'value'    => $data['area_admin_email'],
                                     'multiple' => null));
  $fieldset->addElement($field);
  
  // The custom HTML
  $field = new FieldTextarea();
  $field->setLabel(get_vocab('custom_html'))
        ->setLabelAttributes(array('title' => get_vocab('custom_html_note')))
        ->setControlAttributes(array('id'       => 'custom_html',
                                     'name'     => 'custom_html'))
        ->setControlText($data['custom_html']);
  $fieldset->addElement($field);
  
  // Mode - Times or Periods
  $options = array('1' => get_vocab('mode_periods'),
                   '0' => get_vocab('mode_times'));
  $value = ($data['enable_periods']) ? '1' : '0';
  $field = new FieldInputRadioGroup();
  $field->setAttribute('id', 'mode')
        ->setLabel(get_vocab('mode'))
        ->addRadioOptions($options, 'area_enable_periods', $value, true);
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