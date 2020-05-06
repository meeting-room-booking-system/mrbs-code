<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementDiv;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputCheckbox;
use MRBS\Form\ElementInputDate;
use MRBS\Form\ElementInputHidden;
use MRBS\Form\ElementInputNumber;
use MRBS\Form\ElementInputRadio;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementLabel;
use MRBS\Form\ElementSelect;
use MRBS\Form\ElementSpan;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputCheckbox;
use MRBS\Form\FieldInputCheckboxGroup;
use MRBS\Form\FieldInputDatalist;
use MRBS\Form\FieldInputDate;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldSelect;

// If you want to add some extra columns to the entry and repeat tables to
// record extra details about bookings then you can do so and this page should
// automatically recognise them and handle them.    NOTE: if you add a column to
// the entry table you must add an identical column to the repeat table.
//
// At the moment support is limited to the following column types:
//
// MySQL        PostgreSQL            Form input type
// -----        ----------            ---------------
// bigint       bigint                number
// int          integer               number
// mediumint                          number
// smallint     smallint              checkbox
// tinyint                            checkbox
// decimal      decimal               number
// numeric      numeric               number
// text         text                  textarea
// tinytext                           textarea
//              character varying     textarea
// varchar(n)   character varying(n)  text/textarea, depending on the value of n
//              character             text
// char(n)      character(n)          text/textarea, depending on the value of n
//
// NOTE 1: For char(n) and varchar(n) fields, a text input will be presented if
// n is less than or equal to $text_input_max, otherwise a textarea box will be
// presented.
//
// NOTE 2: PostgreSQL booleans are not supported, due to difficulties in
// handling the fields in a database independent way (a PostgreSQL boolean
// will return a PHP boolean type when read by a PHP query, whereas a MySQL
// tinyint returns an int).   In order to have a boolean field in the room
// table you should use a smallint in PostgreSQL or a smallint or a tinyint
// in MySQL.
//
// You can put a description of the column that will be used as the label in
// the form in the $vocab_override variable in the config file using the tag
// 'entry.[columnname]'.   (Note that it is not necessary to add a
// 'repeat.[columnname]' tag.   The entry tag is sufficient.)
//
// For example if you want to add a column recording the number of participants
// you could add a column to the entry and repeat tables called 'participants'
// of type int.  Then in the appropriate lang file(s) you would add the line
//
// $vocab_override['en']['entry.participants'] = "Participants";  // or appropriate translation
//
// If MRBS can't find an entry for the field in the lang file or $vocab_override,
// then it will use the fieldname, eg 'coffee_machine'.


require 'defaultincludes.inc';
require_once 'mrbs_sql.inc';
require_once 'functions_mail.inc';

$fields = db()->field_info($tbl_entry);
$custom_fields = array();

// Fill $edit_entry_field_order with not yet specified entries.
$entry_fields = array('create_by', 'name', 'description', 'start_time', 'end_time', 'room_id',
                      'type', 'confirmation_status', 'privacy_status');

foreach ($entry_fields as $field)
{
  if (!in_array($field, $edit_entry_field_order))
  {
    $edit_entry_field_order[] = $field;
  }
}

$custom_fields_map = array();
foreach ($fields as $field)
{
  $key = $field['name'];
  if (!in_array($key, $standard_fields['entry']))
  {
    $custom_fields_map[$key] = $field;
    if (!in_array($key, $edit_entry_field_order))
    {
      $edit_entry_field_order[] = $key;
    }
  }
}


function get_field_entry_input($params)
{
  global $select_options, $datalist_options;

  if (isset($params['field']))
  {
    if (!empty($select_options[$params['field']]))
    {
      $class = 'FieldSelect';
    }
    elseif (!empty($datalist_options[$params['field']]))
    {
      $class = 'FieldInputDatalist';
    }
    elseif ($params['field'] == 'entry.description')
    {
      $class = 'FieldTextarea';
    }
    else
    {
      $class = 'FieldInputText';
    }
  }
  else
  {
    $class = 'FieldInputText';
  }

  $full_class = __NAMESPACE__ . "\\Form\\$class";
  $field = new $full_class();
  $field->setLabel($params['label'])
        ->setControlAttribute('name', $params['name']);

  if (!empty($params['required']))
  {
    $field->setControlAttribute('required', true);
  }
  if (!empty($params['disabled']))
  {
    $field->setControlAttribute('disabled', true);
    $field->addHiddenInput($params['name'], $params['value']);
  }

  switch ($class)
  {
    case 'FieldSelect':
      $options = $select_options[$params['field']];
      $field->addSelectOptions($options, $params['value']);
      break;

    case 'FieldInputDatalist':
      $options = $datalist_options[$params['field']];
      $field->addDatalistOptions($options);
      // Drop through

    case 'FieldInputText':
      if (!empty($params['required']))
      {
        // Set a pattern as well as required to prevent a string of whitespace
        $field->setControlAttribute('pattern', REGEX_TEXT_POS);
      }
      // Drop through

    case 'FieldTextarea':
      if ($class == 'FieldTextarea')
      {
        $field->setControlText($params['value']);
      }
      else
      {
        $field->setControlAttribute('value', $params['value']);
      }
      if (isset($params['field']) &&
          (null !== ($maxlength = maxlength($params['field']))))
      {
        $field->setControlAttribute('maxlength', $maxlength);
      }
      break;

    default:
      throw new \Exception("Unknown class '$class'");
      break;
  }

  return $field;
}


function get_field_create_by($create_by, $disabled=false)
{
  if (function_exists(__NAMESPACE__ . "\\authGetUsernames"))
  {
    // We can get a list of all users, so present a <select> element.
    // The options will actually be provided later via Ajax, so all we
    // do here is present one option, ie the createby user.
    $options = array();
    $options[$create_by] = $create_by;

    $field = new FieldSelect();
    $field->setLabel(get_vocab('createdby'))
          ->setControlAttributes(array('name'     => 'create_by',
                                       'disabled' => $disabled))
          ->addSelectOptions($options, $create_by, true);
  }
  else
  {
    // We don't know all the available users, so we'll just present
    // a text input field and rely on the user to enter a valid username.
    $params = array('label'    => get_vocab('createdby'),
                    'name'     => 'create_by',
                    'field'    => 'entry.create_by',
                    'value'    => $create_by,
                    'required' => true,
                    'disabled' => $disabled);

    $field = get_field_entry_input($params);
  }

  return $field;
}


function get_field_name($value, $disabled=false)
{
  $params = array('label'    => get_vocab('namebooker'),
                  'name'     => 'name',
                  'field'    => 'entry.name',
                  'value'    => $value,
                  'required' => true,
                  'disabled' => $disabled);

  return get_field_entry_input($params);
}


function get_field_description($value, $disabled=false)
{
  global $is_mandatory_field;

  $params = array('label'    => get_vocab('fulldescription'),
                  'name'     => 'description',
                  'field'    => 'entry.description',
                  'value'    => $value,
                  'required' => !empty($is_mandatory_field['entry.description']),
                  'disabled' => $disabled);

  return get_field_entry_input($params);
}


// Generate a time or period selector starting with $first and ending with $last.
// $time is a full Unix timestamp and is the current value.  The selector returns
// the start time in seconds since the beginning of the day for the start of that slot.
// Note that these are nominal seconds and do not take account of any DST changes that
// may have happened earlier in the day.  (It's this way because we don't know what day
// it is as that's controlled by the date selector - and we can't assume that we have
// JavaScript enabled to go and read it)
//
//    $display_none parameter     sets the display style of the <select> to "none"
//    $disabled parameter         disables the input and also generate a hidden input, provided
//                                that $display_none is FALSE.  (This prevents multiple inputs
//                                of the same name)
//    $is_start                   Boolean.  Whether this is the start selector.  Default FALSE
function get_slot_selector($area, $id, $name, $current_s, $display_none=false, $disabled=false, $is_start=false)
{
  // Check that $resolution is positive to avoid an infinite loop below.
  // (Shouldn't be possible, but just in case ...)
  if (empty($area['resolution']) || ($area['resolution'] < 0))
  {
    throw new \Exception("Internal error - resolution is NULL or <= 0");
  }

  if ($area['enable_periods'])
  {
    $base = 12*SECONDS_PER_HOUR;  // The start of the first period of the day
  }

  // Build the options
  $options = array();

  $first = $area['first'];
  // If we're using periods then the last slot is actually the start of the last period,
  // or if we're using times and this is the start selector, then we don't show the last
  // time
  if ($area['enable_periods'] || $is_start)
  {
    $last = $area['last'] - $area['resolution'];
  }
  else
  {
    $last = $area['last'];
  }

  for ($s = $first; $s <= $last; $s += $area['resolution'])
  {
    if ($area['enable_periods'])
    {
      $options[$s] = $area['periods'][intval(($s-$base)/60)];
    }
    else
    {
      $options[$s] = hour_min($s);
    }
  }

  // Make sure that the selected option is within the range of available options.
  $selected = max($current_s, $first);
  $selected = min($selected, $last);

  $field = new ElementSelect();
  $field->setAttributes(array('id'       => $id,
                              'name'     => $name,
                              'disabled' => $disabled || $display_none))
        ->addSelectOptions($options, $selected, true);

  if ($disabled)
  {
    // If $disabled is set, give the element a class so that the JavaScript
    // knows to keep it disabled
    $field->addClass('keep_disabled');
  }
  if ($display_none)
  {
    $field->addClass('none');
  }

  if ($disabled && !$display_none)
  {
    $hidden = new ElementInputHidden();
    $hidden->setAttributes(array('name'  => $name,
                                 'value' => $selected));
    $field->next($hidden);
  }

  return $field;
}


// Generate the All Day checkbox for an area
function get_all_day($area, $input_id, $input_name, $display_none=false, $disabled=false)
{
  global $drag, $id;

  $element = new ElementDiv();

  if ($display_none || !$area['show_all_day'])
  {
    $element->addClass('none');
  }

  // (1) If $display_none or $disabled are set then we'll also disable the select so
  //     that there is only one select passing through the variable to the handler.
  // (2) If this is an existing booking that we are editing or copying, then we do
  //     not want the default duration applied
  $disable_field = $disabled || $display_none || !$area['show_all_day'];

  $checkbox = new ElementInputCheckbox();
  $checkbox->setAttributes(array('name'      => $input_name,
                                 'id'        => $input_id,
                                 'data-show' => ($area['show_all_day']) ? '1' : '0',
                                 'disabled'  => $disable_field))
           ->setChecked($area['default_duration_all_day'] && !isset($id) && !$drag);

  if ($disable_field)
  {
    // and if $disabled is set, give the element a class so that the JavaScript
    // knows to keep it disabled
    $checkbox->addClass('keep_disabled');
  }

  $label = new ElementLabel();
  $label->setText(get_vocab('all_day'))
        ->setAttribute('class', 'no_suffix');

  $label->addElement($checkbox);
  $element->addElement($label);

  return $element;
}


function get_field_start_time($value, $disabled=false)
{
  global $areas, $area_id;

  $date = getbookingdate($value);
  $start_date = format_iso_date($date['year'], $date['mon'], $date['mday']);
  $current_s = (($date['hours'] * 60) + $date['minutes']) * 60;

  $field = new FieldDiv();

  // Generate the live slot selector and all day checkbox
  $element_date = new ElementInputDate();
  $element_date->setAttributes(array('id'       => 'start_date',
                                     'name'     => 'start_date',
                                     'value'    => $start_date,
                                     'disabled' => $disabled,
                                     'required' => true));

  $field->setLabel(get_vocab('start'))
        ->addControlElement($element_date)
        ->addControlElement(get_slot_selector($areas[$area_id],
                                              'start_seconds',
                                              'start_seconds',
                                              $current_s,
                                              false,
                                              $disabled,
                                              true))
        ->addControlElement(get_all_day($areas[$area_id],
                                        'all_day',
                                        'all_day',
                                        false,
                                        $disabled));

  // Generate the templates for each area
  foreach ($areas as $a)
  {
    $field->addControlElement(get_slot_selector($a,
                                                'start_seconds' . $a['id'],
                                                'start_seconds',
                                                $current_s,
                                                true,
                                                true,
                                                true))
          ->addControlElement(get_all_day($a,
                                          'all_day' . $a['id'],
                                          'all_day',
                                          true,
                                          true));
  }

  return $field;
}


function get_field_end_time($value, $disabled=false)
{
  global $areas, $area_id;
  global $multiday_allowed;
  
  $date = getbookingdate($value, true);
  $end_date = format_iso_date($date['year'], $date['mon'], $date['mday']);
  $current_s = (($date['hours'] * 60) + $date['minutes']) * 60;

  $field = new FieldDiv();

  // Generate the live slot selector
  // If we're using periods the booking model is slightly different,
  // so subtract one period because the "end" period is actually the beginning
  // of the last period booked
  $element_date = new ElementInputDate();
  $element_date->setAttributes(array('id'       => 'end_date',
                                     'name'     => 'end_date',
                                     'value'    => $end_date,
                                     'disabled' => $disabled));

  // Don't show the end date if multiday bookings are not allowed
  if (!$multiday_allowed)
  {
    $element_date->setAttributes(array('style'    => 'visibility: hidden',
                                       'disabled' => true));
  }

  $a = $areas[$area_id];
  $this_current_s = ($a['enable_periods']) ? $current_s - $a['resolution'] : $current_s;

  $field->setLabel(get_vocab('end'))
        ->addControlElement($element_date)
        ->addControlElement(get_slot_selector($areas[$area_id],
                                              'end_seconds',
                                              'end_seconds',
                                              $this_current_s,
                                              false,
                                              $disabled,
                                              false));

  // Generate the templates
  foreach ($areas as $a)
  {
    $this_current_s = ($a['enable_periods']) ? $current_s - $a['resolution'] : $current_s;
    $field->addControlElement(get_slot_selector($a,
                                                'end_seconds' . $a['id'],
                                                'end_seconds',
                                                $this_current_s,
                                                true,
                                                true,
                                                false));
  }

  // An empty <span> to hold JavaScript messages
  $span = new ElementSpan();
  $span->setAttributes(array('id'    => 'end_time_error',
                             'class' => 'error'));
  $field->addControlElement($span);

  return $field;
}


function get_field_areas($value, $disabled=false)
{
  global $areas;

  // No point in being able to choose an area if there aren't more
  // than one of them.
  if (count($areas) < 2)
  {
    return null;
  }

  $field = new FieldSelect();

  $options = array();
  // go through the areas and create the options
  foreach ($areas as $a)
  {
    $options[$a['id']] = $a['area_name'];
  }

  // We will set the display to none and then turn it on in the JavaScript.  That's
  // because if there's no JavaScript we don't want to display it because we won't
  // have any means of changing the rooms if the area is changed.
  $field->setAttributes(array('id'    => 'div_areas'))
        ->addClass('none')
        ->setLabel(get_vocab('area'))
        ->setControlAttributes(array('name'     => 'area',
                                     'disabled' => $disabled))
        ->addSelectOptions($options, $value, true);

  return $field;
}


function get_field_rooms($value, $disabled=false)
{
  global $multiroom_allowed, $area_id, $areas, $rooms;

  // First of all generate the rooms for this area
  $field = new FieldSelect();

  $field->setLabel(get_vocab('rooms'));

  // No point telling them how to select multiple rooms if the input
  // is disabled or they aren't allowed to
  if ($multiroom_allowed && !$disabled)
  {
    $field->setLabelAttribute('title', get_vocab('ctrl_click'));
  }

  $field->setAttributes(array('class' => 'multiline',
                              'id'    => 'div_rooms'))
        ->setControlAttributes(array('id'       => 'rooms',
                                     'name'     => 'rooms[]',
                                     'multiple' => $multiroom_allowed, // If multiple is not set then required is unnecessary
                                     'required' => $multiroom_allowed, // and also causes an HTML5 validation error
                                     'disabled' => $disabled,
                                     'size'     => '5'))
        ->addSelectOptions($rooms[$area_id], $value, true);

  // Then generate templates for all the rooms
  foreach ($rooms as $a => $area_rooms)
  {
    $room_ids = array_keys($area_rooms);

    $select = new ElementSelect();
    $select->setAttributes(array('id'       => 'rooms' . $a,
                                 'name'     => 'rooms[]',
                                 'multiple' => $multiroom_allowed, // If multiple is not set then required is unnecessary
                                 'required' => $multiroom_allowed, // and also causes an HTML5 validation error
                                 'disabled' => true,
                                 'size'     => '5'))
           ->addClass('none')
           ->addSelectOptions($area_rooms, $room_ids[0], true);
    // Put in some data about the area for use by the JavaScript
    $select->setAttributes(array(
        'data-enable_periods'           => ($areas[$a]['enable_periods']) ? 1 : 0,
        'data-n_periods'                => count($areas[$a]['periods']),
        'data-default_duration'         => (isset($areas[$a]['default_duration']) && ($areas[$a]['default_duration'] != 0)) ? $areas[$a]['default_duration'] : SECONDS_PER_HOUR,
        'data-default_duration_all_day' => ($areas[$a]['default_duration_all_day']) ? 1 : 0,
        'data-max_duration_enabled'     => ($areas[$a]['max_duration_enabled']) ? 1 : 0,
        'data-max_duration_secs'        => $areas[$a]['max_duration_secs'],
        'data-max_duration_periods'     => $areas[$a]['max_duration_periods'],
        'data-max_duration_qty'         => $areas[$a]['max_duration_qty'],
        'data-max_duration_units'       => $areas[$a]['max_duration_units'],
        'data-timezone'                 => $areas[$a]['timezone']
      ));
    $field->addElement($select);

  } // foreach

  return $field;
}


function get_field_type($value, $disabled=false)
{
  global $booking_types, $is_mandatory_field;

  // Don't bother with types if there's only one of them (or even none)
  if (!isset($booking_types) || (count($booking_types) < 2))
  {
    return null;
  }

  // Get the options
  if (!empty($is_mandatory_field['entry.type']))
  {
    // Add a blank option to force a selection
    $options[''] = get_type_vocab('');
  }

  foreach ($booking_types as $key)
  {
    $options[$key] = get_type_vocab($key);
  }

  $field = new FieldSelect();

  $field->setLabel(get_vocab('type'))
        ->setControlAttributes(array('name'     => 'type',
                                     'disabled' => $disabled,
                                     'required' => !empty($is_mandatory_field['entry.type'])))
        ->addSelectOptions($options, $value, true);

  return $field;
}


function get_field_confirmation_status($value, $disabled=false)
{
  global $confirmation_enabled;

  if (!$confirmation_enabled)
  {
    return null;
  }

  $options = array(0 => get_vocab('tentative'),
                   1 => get_vocab('confirmed'));

  $value = ($value) ? 0 : 1;

  $field = new FieldInputRadioGroup();

  $field->setLabel(get_vocab('confirmation_status'))
        ->addRadioOptions($options, 'confirmed', $value, true, $disabled);

  return $field;
}


function get_field_privacy_status($value, $disabled=false)
{
  global $private_enabled, $private_mandatory;

  if (!$private_enabled)
  {
    return null;
  }

  $options = array(0 => get_vocab('public'),
                   1 => get_vocab('private'));

  $value = ($value) ? 1 : 0;

  $field = new FieldInputRadioGroup();

  $field->setLabel(get_vocab('privacy_status'))
        ->addRadioOptions($options, 'private', $value, true, $private_mandatory || $disabled);

  return $field;
}


function get_field_custom($key, $disabled=false)
{
  global $custom_fields, $custom_fields_map, $tbl_entry;
  global $is_mandatory_field, $text_input_max;

  // First check that the custom field exists.  It normally will, but won't if
  // $edit_entry_field_order contains a value for which a field doesn't exist.
  if (!isset($custom_fields_map[$key]))
  {
    return null;
  }

  $custom_field = $custom_fields_map[$key];

  // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
  // assume are intended to be booleans)
  if (($custom_field['nature'] == 'boolean') ||
    (($custom_field['nature'] == 'integer') && isset($custom_field['length']) && ($custom_field['length'] <= 2)) )
  {
    $class = 'FieldInputCheckbox';
  }
  // Output a textarea if it's a character string longer than the limit for a
  // text input
  elseif (($custom_field['nature'] == 'character') &&
           isset($custom_field['length']) &&
           ($custom_field['length'] > $text_input_max))
  {
    // HTML5 does not allow a pattern attribute for the textarea element
    $class = 'FieldTextarea';
  }
  // Otherwise check if it's an integer field
  elseif ((($custom_field['nature'] == 'integer') && ($custom_field['length'] > 2)) ||
          ($custom_field['nature'] == 'decimal'))
  {
    $class = 'FieldInputNumber';
  }
  // Otherwise it's a text input of some kind (which includes <select>s and
  // <datalist>s)
  else
  {
    $params = array('label'    => get_loc_field_name($tbl_entry, $key),
                    'name'     => VAR_PREFIX . $key,
                    'field'    => "entry.$key",
                    'value'    => (isset($custom_fields[$key])) ? $custom_fields[$key] : NULL,
                    'required' => !empty($is_mandatory_field["entry.$key"]),
                    'disabled' => $disabled);
    return get_field_entry_input($params);
  }

  $full_class = __NAMESPACE__ . "\\Form\\$class";
  $field = new $full_class();

  $field->setLabel(get_loc_field_name($tbl_entry, $key))
        ->setControlAttributes(array('name'     => VAR_PREFIX . $key,
                                     'disabled' => $disabled,
                                     'required' => !empty($is_mandatory_field["entry.$key"])));

  if ($custom_field['nature'] == 'decimal')
  {
    list( , $decimal_places) = explode(',', $custom_field['length']);
    $step = pow(10, -$decimal_places);
    $step = number_format($step, $decimal_places);
    $field->setControlAttribute('step', $step);
  }

  if ($class == 'FieldTextarea')
  {
    if (isset($custom_fields[$key]))
    {
      $field->setControlText($custom_fields[$key]);
    }
    if (null !== ($maxlength = maxlength("entry.$key")))
    {
      $field->setControlAttribute('maxlength', $maxlength);
    }
  }
  elseif ($class == 'FieldInputCheckbox')
  {
    $field->setControlChecked(!empty($custom_fields[$key]));
  }
  else
  {
    $field->setControlAttribute('value', (isset($custom_fields[$key])) ? $custom_fields[$key] : null);
  }

  return $field;
}


// Repeat type
function get_field_rep_type($value, $disabled=false)
{
  $field = new FieldDiv();

  $field->setAttributes(array('id'    => 'rep_type',
                              'class' => 'multiline'))
        ->setLabel(get_vocab('rep_type'));

  foreach (array(REP_NONE, REP_DAILY, REP_WEEKLY, REP_MONTHLY, REP_YEARLY) as $i)
  {
    $options[$i] = get_vocab("rep_type_$i");
  }
  $radio_group = new ElementDiv();
  $radio_group->setAttribute('class', 'group long')
              ->addRadioOptions($options, 'rep_type', $value, true);

  $field->addControlElement($radio_group);

  // No point in showing anything more if the repeat fields are disabled
  // and the repeat type is None
  if (!$disabled || ($value != REP_NONE))
  {
    // And no point in showing the weekly repeat details if the repeat
    // fields are disabled and the repeat type is not a weekly repeat
    if (!$disabled || ($value == REP_WEEKLY))
    {
      $field->addControlElement(get_fieldset_rep_weekly_details($disabled));
    }

    // And no point in showing the monthly repeat details if the repeat
    // fields are disabled and the repeat type is not a monthly repeat
    if (!$disabled || ($value == REP_MONTHLY))
    {
      $field->addControlElement(get_fieldset_rep_monthly_details($disabled));
    }
  }

  return $field;
}


// Repeat day
function get_field_rep_day($disabled=false)
{
  global $weekstarts, $strftime_format;
  global $rep_day;

  for ($i = 0; $i < DAYS_PER_WEEK; $i++)
  {
    // Display day name checkboxes according to language and preferred weekday start.
    $wday = ($i + $weekstarts) % DAYS_PER_WEEK;
    // We need to ensure the index is a string to force the array to be associative
    $options[$wday] = day_name($wday, $strftime_format['dayname_edit']);
  }

  $field = new FieldInputCheckboxGroup();

  $field->setAttribute('id', 'rep_day')
        ->setLabel(get_vocab('rep_rep_day'))
        ->addCheckboxOptions($options, 'rep_day[]', $rep_day, true, $disabled);

  return $field;
}


function get_fieldset_rep_weekly_details($disabled=false)
{
  $fieldset = new ElementFieldset();

  $fieldset->setAttributes(array('class' => 'rep_type_details js_none',
                                 'id'    => 'rep_weekly'));
  $fieldset->addElement(get_field_rep_day($disabled));

  return $fieldset;
}


// MONTH ABSOLUTE (eg Day 15 of every month)
function get_fieldset_month_absolute($disabled=false)
{
  global $month_type, $month_absolute;

  $fieldset = new ElementFieldset();

  $label = new ElementLabel();
  $label->setAttribute('class', 'no_suffix')
        ->setText(get_vocab('month_absolute'));

  $radio = new ElementInputRadio();
  $radio->setAttributes(array('name'     => 'month_type',
                              'value'    => REP_MONTH_ABSOLUTE,
                              'checked'  => ($month_type == REP_MONTH_ABSOLUTE),
                              'disabled' => $disabled));

  $label->addElement($radio);

  $fieldset->addElement($label);

  // We could in the future allow -1 to -31, meaning "the nth last day of
  // the month", but for the moment we'll keep it simple
  $options = array();
  for ($i=1; $i<=31; $i++)
  {
    $options[] = $i;
  }
  $select = new ElementSelect();
  $select->setAttributes(array('name'     => 'month_absolute',
                               'disabled' => $disabled))
         ->addSelectOptions($options, $month_absolute, false);

  $fieldset->addElement($select);

  return $fieldset;
}


// MONTH RELATIVE (eg the second Thursday of every month)
function get_fieldset_month_relative($disabled=false)
{
  global $month_type, $month_relative_ord, $month_relative_day;
  global $weekstarts, $RFC_5545_days;

  $fieldset = new ElementFieldset();

  $label = new ElementLabel();
  $label->setAttribute('class', 'no_suffix')
        ->setText(get_vocab('month_relative'));

  $radio = new ElementInputRadio();
  $radio->setAttributes(array('name'     => 'month_type',
                              'value'    => REP_MONTH_RELATIVE,
                              'checked'  => ($month_type == REP_MONTH_RELATIVE),
                              'disabled' => $disabled));

  $label->addElement($radio);

  $fieldset->addElement($label);

  // Note: the select box order does not internationalise very well and could
  // do with revisiting.   It assumes all languages have the same order as English
  // eg "the second Wednesday" which is probably not true.
  $options = array();
  foreach (array('1', '2', '3', '4', '5', '-1', '-2', '-3', '-4', '-5') as $i)
  {
    $options[$i] = get_vocab("ord_" . $i);
  }
  $select = new ElementSelect();
  $select->setAttributes(array('name'     => 'month_relative_ord',
                               'disabled' => $disabled))
         ->addSelectOptions($options, $month_relative_ord, true);

  $fieldset->addElement($select);

  $options = array();
  for ($i=0; $i<DAYS_PER_WEEK; $i++)
  {
    $i_offset = ($i + $weekstarts)%DAYS_PER_WEEK;
    $options[$RFC_5545_days[$i_offset]] = day_name($i_offset);
  }
  $select = new ElementSelect();
  $select->setAttributes(array('name'     => 'month_relative_day',
                               'disabled' => $disabled))
         ->addSelectOptions($options, $month_relative_day, true);

  $fieldset->addElement($select);

  return $fieldset;
}


function get_fieldset_rep_monthly_details($disabled=false)
{
  global $month_type;

  $fieldset = new ElementFieldset();

  // If the existing repeat type is other than a monthly repeat, we'll
  // need to define a default month type in case the user decides to change
  // to a monthly repeat
  if (!isset($month_type))
  {
    $month_type = REP_MONTH_ABSOLUTE;
  }

  $fieldset->setAttributes(array('class' => 'rep_type_details js_none',
                                 'id'    => 'rep_monthly'));
  $fieldset->addElement(get_fieldset_month_absolute($disabled))
           ->addElement(get_fieldset_month_relative($disabled));

  return $fieldset;
}


function get_field_rep_end_date($disabled=false)
{
  global $rep_end_year, $rep_end_month, $rep_end_day;

  $field = new FieldInputDate();

  $field->setLabel(get_vocab('rep_end_date'))
        ->setControlAttributes(array('name'     => 'rep_end_date',
                                     'value'    => format_iso_date($rep_end_year, $rep_end_month, $rep_end_day),
                                     'disabled' => $disabled));

  return $field;
}


function get_field_rep_interval($rep_interval, $disabled=false)
{
  global $rep_type;

  $field = new FieldInputNumber();

  $span = new ElementSpan();
  $span->setAttribute('id', 'interval_units')
       ->setText(get_rep_interval_units($rep_type, $rep_interval));

  $field->setLabel(get_vocab('rep_interval'))
        ->setControlAttributes(array('name'     => 'rep_interval',
                                     'min'      => 1,
                                     'value'    => $rep_interval,
                                     'disabled' => $disabled))
        ->addElement($span);

  return $field;
}

function get_field_skip_conflicts($disabled=false)
{
  global $skip_default;

  if ($disabled)
  {
    return null;
  }

  $field = new FieldInputCheckbox();

  $field->setLabel(get_vocab('skip_conflicts'))
        ->setControlAttribute('name', 'skip')
        ->setChecked(!empty($skip_default));

  return $field;
}


function get_fieldset_repeat()
{
  global $edit_type, $repeats_allowed;
  global $rep_type, $rep_interval;

  // If repeats aren't allowed or this is not a series then disable
  // the repeat fields - they're for information only
  // (NOTE: when repeat bookings are restricted to admins, an ordinary user
  // would not normally be able to get to the stage of trying to edit a series.
  // But we have to cater for the possibility because it could happen if (a) the
  // series was created before the policy was introduced or (b) the user has
  // been demoted since the series was created).
  $disabled = ($edit_type != "series") || !$repeats_allowed;

  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'rep_info');

  $fieldset->addElement(get_field_rep_type($rep_type, $disabled))
           ->addElement(get_field_rep_interval($rep_interval, $disabled))
           ->addElement(get_field_rep_end_date($disabled))
           ->addElement(get_field_skip_conflicts($disabled));

  return $fieldset;
}


function get_fieldset_booking_controls()
{
  global $mail_settings;

  $fieldset = new ElementFieldset();

  $fieldset->setAttribute('id', 'booking_controls');

  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('no_mail'))
        ->setControlAttribute('name', 'no_mail')
        ->setChecked($mail_settings['no_mail_default']);

  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_submit_buttons()
{
  $fieldset = new ElementFieldset();

  // The back and submit buttons
  $field = new FieldDiv();

  $back = new ElementInputSubmit();
  $back->setAttributes(array('name'           => 'back_button',
                             'value'          => get_vocab('back'),
                             'formnovalidate' => true));

  $submit = new ElementInputSubmit();
  $submit->setAttributes(array('class' => 'default_action',
                               'name'  => 'save_button',
                               'value' => get_vocab('save')));

  // div to hold the results of the Ajax checking of the booking
  $div = new ElementDiv();
  $span_conflict = new ElementSpan();
  $span_conflict->setAttribute('id', 'conflict_check');
  $span_policy = new ElementSpan();
  $span_policy->setAttribute('id', 'policy_check');
  $div->setAttribute('id', 'checks')
      ->addElement($span_conflict)
      ->addElement($span_policy);

  $field->setAttribute('class', 'submit_buttons')
        ->addLabelClass('no_suffix')
        ->addLabelElement($back)
        ->addControlElement($submit)
        ->addControlElement($div);

  $fieldset->addElement($field);



  return $fieldset;
}


// Returns the booking date for a given time.   If the booking day spans midnight and
// $t is in the interval between midnight and the end of the day then the booking date
// is really the day before.
//
// If $is_end is set then this is the end time and so if the booking day happens to
// last exactly 24 hours, when there will be two possible answers, we want the later
// one.
function getbookingdate($t, $is_end=false)
{
  global $eveningends, $eveningends_minutes, $resolution;

  $date = getdate($t);

  $t_secs = (($date['hours'] * 60) + $date['minutes']) * 60;
  $e_secs = (((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution) % SECONDS_PER_DAY;

  if (day_past_midnight())
  {
    if (($t_secs < $e_secs) ||
        (($t_secs == $e_secs) && $is_end))
    {
      $date = getdate(mktime($date['hours'], $date['minutes'], $date['seconds'],
                             $date['mon'], $date['mday'] -1, $date['year']));
      $date['hours'] += 24;
    }
  }

  return $date;
}


// Get non-standard form variables
$hour = get_form_var('hour', 'int');
$minute = get_form_var('minute', 'int');
$period = get_form_var('period', 'int');
$id = get_form_var('id', 'int');
$copy = get_form_var('copy', 'int');
$edit_type = get_form_var('edit_type', 'string', '');
$returl = get_form_var('returl', 'string');
// The following variables are used when coming via a JavaScript drag select
$drag = get_form_var('drag', 'int');
$start_seconds = get_form_var('start_seconds', 'int');
$end_seconds = get_form_var('end_seconds', 'int');
$selected_rooms = get_form_var('rooms', 'array');
$start_date = get_form_var('start_date', 'string');
$end_date = get_form_var('end_date', 'string');


// Check the CSRF token.
// Only check the token if the page is accessed via a POST request.  Therefore
// this page should not take any action, but only display data.
Form::checkToken($post_only=true);

// Get the return URL.  Need to do this before checkAuthorised().
// We might be going through edit_entry more than once, for example if we have to log on on the way.  We
// still need to preserve the original calling page so that once we've completed edit_entry_handler we can
// go back to the page we started at (rather than going to the default view).  If this is the first time
// through, then $server['HTTP_REFERER'] holds the original caller.    If this is the second time through
// we will have stored it in $returl.
if (!isset($returl))
{
  $returl = isset($server['HTTP_REFERER']) ? $server['HTTP_REFERER'] : '';
}

// Check the user is authorised for this page
checkAuthorised(this_page());

$current_username = getUserName();

// You're only allowed to make repeat bookings if you're an admin
// or else if $auth['only_admin_can_book_repeat'] is not set
$repeats_allowed = is_book_admin() || empty($auth['only_admin_can_book_repeat']);
// Similarly for multi-day
$multiday_allowed = is_book_admin() || empty($auth['only_admin_can_book_multiday']);
// Similarly for multiple room selection
$multiroom_allowed = is_book_admin() || empty($auth['only_admin_can_select_multiroom']);



if (isset($start_seconds))
{
  $minutes = intval($start_seconds/60);
  if ($enable_periods)
  {
    $period = $minutes - (12*60);
  }
  else
  {
    $hour = intval($minutes/60);
    $minute = $minutes%60;
  }
}

if (isset($start_date))
{
  list($year, $month, $day) = explode('-', $start_date);
  if (isset($end_date) && ($start_date != $end_date) && $repeats_allowed)
  {
    $rep_type = REP_DAILY;
    list($rep_end_year, $rep_end_month, $rep_end_day) = explode('-', $end_date);
  }
}


// This page will either add or modify a booking

// We need to know:
//  Name of booker
//  Description of meeting
//  Date (option select box for day, month, year)
//  Time
//  Duration
//  Internal/External

// Firstly we need to know if this is a new booking or modifying an old one
// and if it's a modification we need to get all the old data from the db.
// If we had $id passed in then it's a modification.

if (isset($id))
{
  $entry = get_entry_by_id($id);

  if (is_null($entry))
  {
    fatal_error(get_vocab("entryid") . $id . get_vocab("not_found"));
  }

  // We've possibly got a new room and area, so we need to update the settings
  // for this area.
  $area = get_area($entry['room_id']);
  get_area_settings($area);

  $private = $entry['private'];
  if ($private_mandatory)
  {
    $private = $private_default;
  }
  // Need to clear some data if entry is private and user
  // does not have permission to edit/view details
  if (isset($copy) && ($current_username != $entry['create_by']))
  {
    // Entry being copied by different user
    // If they don't have rights to view details, clear them
    $privatewriteable = getWritable($entry['create_by'], $entry['room_id']);
    $keep_private = (is_private_event($private) && !$privatewriteable);
  }
  else
  {
    $keep_private = FALSE;
  }

  // default settings
  $rep_day = array();
  $rep_type = REP_NONE;
  $rep_interval = 1;

  foreach ($entry as $column => $value)
  {
    switch ($column)
    {
      // Don't bother with these columns
      case 'id':
      case 'timestamp':
      case 'reminded':
      case 'info_time':
      case 'info_user':
      case 'info_text':
      case 'private':    // We have already done private above
        break;

      // These columns cannot be made private
      case 'room_id':
        // We need to preserve the original room_id for existing bookings and pass
        // it through to edit_entry_handler.    We need this because we need to know
        // in edit_entry_handler which room contains the original booking.   It's
        // possible in this form to select multiple rooms, or even change the room.
        // We will need to know which booking is the "original booking" because the
        // original booking will keep the same ical_uid and have the ical_sequence
        // incremented, whereas new bookings will have a new ical_uid and start with
        // an ical_sequence of 0.    (If there is more than one room when we get to
        // edit_entry_handler and the original room isn't among them, then we will
        // just have to make an arbitrary choice as to which is the room containing
        // the original booking.)
        // NOTE:  We do not set the original_room_id if we are copying an entry,
        // because when we are copying we are effectively making a new entry and
        // so we want edit_entry_handler to assign a new UID, etc.
        if (!$copy)
        {
          $original_room_id = $entry['room_id'];
        }
      case 'ical_uid':
      case 'ical_sequence':
      case 'ical_recur_id':
      case 'entry_type':
      case 'tentative':
        $$column = $entry[$column];
        break;

      // These columns can be made private [not sure about 'type' though - haven't
      // checked whether it makes sense/works to make the 'type' column private]
      case 'name':
      case 'description':
      case 'type':
        $$column = ($keep_private && isset($is_private_field["entry.$column"]) && $is_private_field["entry.$column"]) ? '' : $entry[$column];
        break;

      case 'repeat_id':
        $rep_id      = $entry['repeat_id'];
        break;

      case 'create_by':
        // If we're copying an existing entry then we need to change the create_by (they could be
        // different if it's an admin doing the copying)
        $create_by   = (isset($copy)) ? $current_username : $entry['create_by'];
        break;

      case 'start_time':
        $start_time = $entry['start_time'];
        break;

      case 'end_time':
        $end_time = $entry['end_time'];
        $duration = $entry['end_time'] - $entry['start_time'] - cross_dst($entry['start_time'], $entry['end_time']);
        break;

      default:
        $custom_fields[$column] = ($keep_private && isset($is_private_field["entry.$column"]) && $is_private_field["entry.$column"]) ? '' : $entry[$column];
        break;
    }
  }


  if(($entry_type == ENTRY_RPT_ORIGINAL) || ($entry_type == ENTRY_RPT_CHANGED))
  {
    $sql = "SELECT rep_type, start_time, end_time, end_date, rep_opt, rep_interval,
                   month_absolute, month_relative
              FROM $tbl_repeat
             WHERE id=?
             LIMIT 1";

    $res = db()->query($sql, array($rep_id));

    if ($res->count() != 1)
    {
      fatal_error(get_vocab("repeat_id") . $rep_id . get_vocab("not_found"));
    }

    $row = $res->next_row_keyed();
    unset($res);

    $rep_type = $row['rep_type'];

    if (!isset($rep_type))
    {
      $rep_type = REP_NONE;
    }

    // If it's a repeating entry get the repeat details
    if ($rep_type != REP_NONE)
    {
      $rep_interval = $row['rep_interval'];

      // If we're editing the series we want the start_time and end_time to be the
      // start and of the first entry of the series, not the start of this entry
      if ($edit_type == "series")
      {
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
      }

      $rep_end_day   = (int)strftime('%d', $row['end_date']);
      $rep_end_month = (int)strftime('%m', $row['end_date']);
      $rep_end_year  = (int)strftime('%Y', $row['end_date']);
      // Get the end date in string format as well, for use when
      // the input is disabled
      $rep_end_date = utf8_strftime('%A %d %B %Y',$row['end_date']);

      switch ($rep_type)
      {
        case REP_WEEKLY:
          for ($i=0; $i<DAYS_PER_WEEK; $i++)
          {
            if ($row['rep_opt'][$i])
            {
              $rep_day[] = $i;
            }
          }
          break;
        case REP_MONTHLY:
          if (isset($row['month_absolute']))
          {
            $month_type = REP_MONTH_ABSOLUTE;
            $month_absolute = $row['month_absolute'];
          }
          elseif (isset($row['month_relative']))
          {
            $month_type = REP_MONTH_RELATIVE;
            $month_relative = $row['month_relative'];
          }
          else
          {
            trigger_error("Invalid monthly repeat", E_USER_WARNING);
          }
          break;
        default:
          break;
      }
    }
  }
}
else
{
  // It is a new booking. The data comes from whichever button the user clicked
  $edit_type     = "series";
  $name          = $default_name;
  $create_by     = $current_username;
  $description   = $default_description;
  $type          = (empty($is_mandatory_field['entry.type'])) ? $default_type : '';
  $room_id       = $room;
  $private       = $private_default;
  $tentative     = !$confirmed_default;

  // Get the hour and minute, converting a period to its MRBS time
  // Set some sensible defaults
  if ($enable_periods)
  {
    if (isset($period))
    {
      $hour = 12 + intval($period/60);
      $minute = $period % 60;
    }
    else
    {
      $hour = 0;
      $minute = 0;
    }
  }
  else
  {
    if (!isset($hour) || !isset($minute))
    {
      $hour = $morningstarts;
      $minute = $morningstarts_minutes;
    }
  }

  $start_time = mktime($hour, $minute, 0, $month, $day, $year);

  // If the start time is not on a slot boundary, then make it so.  (It's just possible that it won't be
  // if (a) somebody messes with the query string or (b) somebody changes morningstarts or the
  // resolution in another browser window and then this page is refreshed with the same query string).
  $start_first_slot = get_start_first_slot($month, $day, $year);
  $start_time = max($start_first_slot, $start_time);
  if (($start_time - $start_first_slot)%$resolution != 0)
  {
    $start_time = $start_first_slot + intval(($start_time - $start_first_slot)/$resolution);  // rounds down
  }

  if (isset($end_seconds))
  {
    $end_minutes = intval($end_seconds/60);
    $end_hour = intval($end_minutes/60);
    $end_minute = $end_minutes%60;
    $end_time = mktime($end_hour, $end_minute, 0, $month, $day, $year);
    $duration = $end_time - $start_time - cross_dst($start_time, $end_time);
  }
  else
  {
    // Set the duration
    if ($enable_periods)
    {
      $duration = 60;  // one period
    }
    else
    {
      $duration = (isset($default_duration)) ? $default_duration : SECONDS_PER_HOUR;
    }

    // Make sure the duration doesn't exceed the maximum
    if (!is_book_admin() && $max_duration_enabled)
    {
      $duration = min($duration, (($enable_periods) ? $max_duration_periods : $max_duration_secs));
    }

    // If the duration is not an integral number of slots, then make
    // it so.   And make the duration at least one slot long.
    if ($duration%$resolution != 0)
    {
      $duration = intval(round($duration/$resolution));
      $duration = max(1, $duration);
      $duration = $duration * $resolution;
    }

    $end_time = $start_time + $duration;

    // Make sure the end_time falls within a booking day.   So if there are no
    // restrictions, bring it back to the nearest booking day.   If the user is not
    // allowed multi-day bookings then make sure it is on the first booking day.
    if (is_book_admin() || !$auth['only_admin_can_book_multiday'])
    {
      $end_time = fit_to_booking_day($end_time, $back=true);
    }
    else
    {
      $end_time = min($end_time, get_end_last_slot($month, $day, $year));
    }
  }

  $rep_id        = 0;
  if (!isset($rep_type))  // We might have set it through a drag selection
  {
    $rep_type      = REP_NONE;
    $rep_end_day   = $day;
    $rep_end_month = $month;
    $rep_end_year  = $year;
  }
  $rep_day       = array(date('w', $start_time));
  $rep_interval = 1;
  $month_type = REP_MONTH_ABSOLUTE;
}

if (!isset($month_relative))
{
  $month_relative = date_byday($start_time);
}
if (!isset($month_absolute))
{
  $month_absolute = date('j', $start_time);
}
list($month_relative_ord, $month_relative_day) = byday_split($month_relative);

$start_hour  = strftime('%H', $start_time);
$start_min   = strftime('%M', $start_time);

// These next 4 if statements handle the situation where
// this page has been accessed directly and no arguments have
// been passed to it.
// If we have not been provided with a room_id
if (empty( $room_id ) )
{
  $sql = "SELECT id FROM $tbl_room WHERE disabled=0 LIMIT 1";
  $res = db()->query($sql);
  $row = $res->next_row_keyed();
  $room_id = $row['id'];
}

// Determine the area id of the room in question first
$area_id = mrbsGetRoomArea($room_id);

$enable_periods ? toPeriodString($start_min, $duration, $dur_units) : toTimeString($duration, $dur_units);

//now that we know all the data to fill the form with we start drawing it

if (!getWritable($create_by, $room_id))
{
  showAccessDenied($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);
  exit;
}

print_header($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);

// Get the details of all the enabled rooms
$rooms = array();
$sql = "SELECT R.id, R.room_name, R.area_id
          FROM $tbl_room R, $tbl_area A
         WHERE R.area_id = A.id
           AND R.disabled=0
           AND A.disabled=0
      ORDER BY R.area_id, R.sort_key";
$res = db()->query($sql);

while (false !== ($row = $res->next_row_keyed()))
{
  // Only use rooms which are visible and for which the user has write access
  if (getWritable($create_by, $row['id']) && is_visible($row['id']))
  {
    $rooms[$row['area_id']][$row['id']] = $row['room_name'];
  }
}

// Get the details of all the enabled areas
$areas = array();
$sql = "SELECT id, area_name, resolution, default_duration, default_duration_all_day,
               enable_periods, periods, timezone,
               morningstarts, morningstarts_minutes, eveningends , eveningends_minutes,
               max_duration_enabled, max_duration_secs, max_duration_periods
          FROM $tbl_area
         WHERE disabled=0
      ORDER BY sort_key";
$res = db()->query($sql);

while (false !== ($row = $res->next_row_keyed()))
{
  // We don't want areas that have no enabled rooms because it doesn't make sense
  // to try and select them for a booking.
  if (empty($rooms[$row['id']]))
  {
    continue;
  }

  // Periods are JSON encoded in the database
  $row['periods'] = json_decode($row['periods']);

  // Make sure we've got the correct resolution when using periods (it's
  // probably OK anyway, but just in case)
  if ($row['enable_periods'])
  {
    $row['resolution'] = 60;
  }
  // Generate some derived settings
  $row['max_duration_qty']     = $row['max_duration_secs'];
  toTimeString($row['max_duration_qty'], $row['max_duration_units']);
  // Get the start and end of the booking day
  if ($row['enable_periods'])
  {
    $first = 12*SECONDS_PER_HOUR;
    // If we're using periods we just go to the end of the last slot
    $last = $first + (count($row['periods']) * $row['resolution']);
  }
  else
  {
    $first = (($row['morningstarts'] * 60) + $row['morningstarts_minutes']) * 60;
    $last = ((($row['eveningends'] * 60) + $row['eveningends_minutes']) * 60) + $row['resolution'];
    // If the end of the day is the same as or before the start time, then it's really on the next day
    if ($first >= $last)
    {
      $last += SECONDS_PER_DAY;
    }
  }
  $row['first'] = $first;
  $row['last'] = $last;
  // We don't show the all day checkbox if it's going to result in bookings that
  // contravene the policy - ie if max_duration is enabled and an all day booking
  // would be longer than the maximum duration allowed.
  $row['show_all_day'] = is_book_admin() ||
                         !$row['max_duration_enabled'] ||
                         ( ($row['enable_periods'] && ($row['max_duration_periods'] >= count($row['periods']))) ||
                           (!$row['enable_periods'] && ($row['max_duration_secs'] >= ($last - $first))) );

  // Clean up the settings, getting rid of any nulls and casting boolean fields into bools
  $row = clean_area_row($row);

  // Now assign the row to the area
  $areas[$row['id']] = $row;
}


if (isset($id) && !isset($copy))
{
  if ($edit_type == "series")
  {
    $token = "editseries";
  }
  else
  {
    $token = "editentry";
  }
}
else
{
  if (isset($copy))
  {
    if ($edit_type == "series")
    {
      $token = "copyseries";
    }
    else
    {
      $token = "copyentry";
    }
  }
  else
  {
    $token = "addentry";
  }
}


$form = new Form();

$form->setAttributes(array('class'  => 'standard js_hidden',
                           'id'     => 'main',
                           'action' => multisite('edit_entry_handler.php'),
                           'method' => 'post'));

$hidden_inputs = array('returl'    => $returl,
                       'rep_id'    => $rep_id,
                       'edit_type' => $edit_type);

$form->addHiddenInputs($hidden_inputs);

// The original_room_id will only be set if this was an existing booking.
// If it is an existing booking then edit_entry_handler needs to know the
// original room id and the ical_uid and the ical_sequence, because it will
// have to keep the ical_uid and increment the ical_sequence for the room that
// contained the original booking.  If it's a new booking it will generate a new
// ical_uid and start the ical_sequence at 0.
if (isset($original_room_id))
{
  $form->addHiddenInputs(array('original_room_id' => $original_room_id,
                               'ical_uid'         => $ical_uid,
                               'ical_sequence'    => $ical_sequence,
                               'ical_recur_id'    => $ical_recur_id));
}

if(isset($id) && !isset($copy))
{
  $form->addHiddenInput('id', $id);
}

$fieldset = new ElementFieldset();
$fieldset->addLegend(get_vocab($token));

foreach ($edit_entry_field_order as $key)
{
  switch ($key)
  {
    case 'create_by':
      // Add in the create_by hidden input, unless the user is a booking admin
      // and we're allowing admins to make bookings on behalf of other users, in
      // which case we'll have an explicit form field to specify the user.
      if (!is_book_admin() || $auth['admin_can_only_book_for_self'])
      {
        $form->addHiddenInput('create_by', $create_by);
      }
      else
      {
        $fieldset->addElement(get_field_create_by($create_by));
      }
      break;

    case 'name':
      $fieldset->addElement(get_field_name($name));
      break;

    case 'description':
      $fieldset->addElement(get_field_description($description));
      break;

    case 'start_time':
      $fieldset->addElement(get_field_start_time($start_time));
      break;

    case 'end_time':
      $fieldset->addElement(get_field_end_time($end_time));
      break;

    case 'room_id':
      $fieldset->addElement(get_field_areas($area_id));
      // $selected_rooms will be populated if we've come from a drag selection
      if (empty($selected_rooms))
      {
        $selected_rooms = array($room_id);
      }
      $fieldset->addElement(get_field_rooms($selected_rooms));
      break;

    case 'type':
      $fieldset->addElement(get_field_type($type));
      break;

    case 'confirmation_status':
      $fieldset->addElement(get_field_confirmation_status($tentative));
      break;

    case 'privacy_status':
      $fieldset->addElement(get_field_privacy_status($private));
      break;

    default:
      $fieldset->addElement(get_field_custom($key));
      break;

  } // switch
} // foreach

$form->addElement($fieldset);

// Show the repeat fields if (a) it's a new booking and repeats are allowed,
// or else if it's an existing booking and it's a series.  (It's not particularly obvious but
// if edit_type is "series" then it means that either you're editing an existing
// series or else you're making a new booking.  This should be tidied up sometime!)
if (($edit_type == "series") && $repeats_allowed)
{
  $form->addElement(get_fieldset_repeat());
}

// Checkbox for no email
if (need_to_send_mail() &&
    ($mail_settings['allow_no_mail'] || (is_book_admin() && $mail_settings['allow_admins_no_mail'])))
{
  $form->addElement(get_fieldset_booking_controls());
}

$form->addElement(get_fieldset_submit_buttons());

$form->render();


print_footer();
