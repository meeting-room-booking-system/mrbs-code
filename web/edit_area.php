<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementLegend;
use MRBS\Form\ElementP;
use MRBS\Form\ElementSpan;
use MRBS\Form\FieldButton;
use MRBS\Form\FieldInputCheckboxGroup;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputTime;
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


function get_fieldset_times($data)
{
  global $enable_periods;
  global $morningstarts, $morningstarts_minutes;
  global $eveningends, $eveningends_minutes;
  global $resolution, $default_duration, $default_duration_all_day;
  
  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'time_settings');
  
  // If we're using JavaScript, don't display the time settings section
  // if we're using periods (the JavaScript will display it if we change)
  if ($enable_periods)
  {
    $fieldset->setAttribute('class', 'js_none');
  }
  
  $span = new ElementSpan();
  $span->setAttribute('class', 'js_none')
       ->setText(' (' . get_vocab('times_only') . ')');
       
  $legend = new ElementLegend();
  $legend->setText(get_vocab('time_settings'), $text_at_start=true)
         ->addElement($span);
  
  $fieldset->addLegend($legend);
  
  // First slot start
  $field = new FieldInputTime();
  $value = sprintf('%02d:%02d', $morningstarts, $morningstarts_minutes);
  $field->setLabel(get_vocab('area_first_slot_start'))
        ->setControlAttributes(array('id'    => 'area_morningstarts',
                                     'name'  => 'area_morningstarts',
                                     'value' => $value));
  $fieldset->addElement($field);
  
  // Resolution
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('area_res_mins'))
        ->setControlAttributes(array('id'    => 'area_res_mins',
                                     'name'  => 'area_res_mins',
                                     'min'   => '1',
                                     'step'  => '1',
                                     'value' => (int) $resolution/60));
  $fieldset->addElement($field);
                                     
  // Duration
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('area_def_duration_mins'))
        ->setControlAttributes(array('id'    => 'area_def_duration_mins',
                                     'name'  => 'area_def_duration_mins',
                                     'min'   => '1',
                                     'step'  => '1',
                                     'value' => (int) $default_duration/60));
  $options = array('1' => get_vocab('all_day'));
  $checkbox_group = new FieldInputCheckboxGroup();
  $checkbox_group->addCheckboxOptions($options, 'area_def_duration_all_day', $default_duration_all_day);
  $field->addElement($checkbox_group);
  $fieldset->addElement($field);
        
  // Last slot start
  // The contents of this field will be overwritten by JavaScript if enabled.    The JavaScript version is a drop-down
  // select input with options limited to those times for the last slot start that are valid.   The options are
  // dynamically regenerated if the start of the first slot or the resolution change.    The code below is
  // therefore an alternative for non-JavaScript browsers.
  $field = new FieldInputTime();
  $value = sprintf('%02d:%02d', $eveningends, $eveningends_minutes);
  $field->setAttributes(array('id'    => 'last_slot',
                              'class' => 'js_hidden'))
        ->setLabel(get_vocab('area_last_slot_start'))
        ->setControlAttributes(array('id'    => 'area_eveningends',
                                     'name'  => 'area_eveningends',
                                     'value' => $value));
  $fieldset->addElement($field);
        
  
  return $fieldset;
}


function get_fieldset_periods($data)
{
  global $enable_periods, $periods;
  
  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'period_settings');
  
  // If we're using JavaScript, don't display the periods settings section
  // if we're using rimes (the JavaScript will display it if we change)
  if (!$enable_periods)
  {
    $fieldset->setAttribute('class', 'js_none');
  }
  $fieldset->addLegend(get_vocab('period_settings'));
  
  // For the JavaScript to work, and MRBS to make sense, there has to be at least
  // one period defined.  So if for some reason, which shouldn't happen, there aren't
  // any periods defined, then force there to be one by creating a single period name
  // with an empty string.   Because the input is a required input, then it will have
  // to be saved with a period name.
  $period_names = empty($periods) ? array('') : $periods;
  
  foreach ($period_names as $period_name)
  {
    $field = new FieldInputText();
    $span = new ElementSpan();
    $span->setAttribute('class', 'delete_period');
    $field->setAttribute('class', 'period_name')
          ->setControlAttributes(array('name'     => 'area_periods[]',
                                       'value'    => $period_name,
                                       'required' => null))
          ->addElement($span);
    $fieldset->addElement($field);
  }
  
  $field = new FieldButton();
  $field->setControlAttributes(array('type' => 'button',
                                     'id'   => 'add_period'))
        ->setControlText(get_vocab('add_period'));
  $fieldset->addElement($field);
  
  return $fieldset;
}


function get_fieldset_booking_policies($data)
{
  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'booking_policies')
           ->addLegend(get_vocab('booking_policies'));
  
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

$outer_fieldset->addLegend(get_vocab('editarea'))
               ->addElement(get_fieldset_errors($errors))
               ->addElement(get_fieldset_general($data))
               ->addElement(get_fieldset_times($data))
               ->addElement(get_fieldset_periods($data))
               ->addElement(get_fieldset_booking_policies($data));

$form->addElement($outer_fieldset);

$form->render();


output_trailer();