<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementDiv;
use MRBS\Form\ElementInputCheckbox;
use MRBS\Form\ElementInputNumber;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementLegend;
use MRBS\Form\ElementP;
use MRBS\Form\ElementSelect;
use MRBS\Form\ElementSpan;
use MRBS\Form\FieldButton;
use MRBS\Form\FieldDiv;
use MRBS\Form\FieldInputCheckbox;
use MRBS\Form\FieldInputCheckboxGroup;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputEmail;
use MRBS\Form\FieldInputNumber;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputTime;
use MRBS\Form\FieldSelect;
use MRBS\Form\FieldSpan;
use MRBS\Form\FieldTextarea;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";


// $max_unit can be set to 'seconds', 'minutes', 'hours', etc. and
// can be used to specify the maximum unit to return.
function get_time_unit_options($max_unit=null)
{
  $options = array();
  $units = array('seconds', 'minutes', 'hours', 'days', 'weeks');

  foreach ($units as $unit)
  {
    $options[$unit] = get_vocab($unit);
    if (isset($max_unit) && ($max_unit == $unit))
    {
      break;
    }
  }

  return $options;
}


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


function get_fieldset_errors(array $errors)
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


function get_fieldset_general(array $data)
{
  global $timezone, $auth;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('general_settings'));

  // Area name
  $field = new FieldInputText();
  $field->setLabel(get_vocab('name'))
        ->setControlAttributes(array('id'        => 'area_name',
                                     'name'      => 'area_name',
                                     'required'  => true,
                                     'maxlength' => maxlength('area.area_name'),
                                     'value'     => $data['area_name']));
  $fieldset->addElement($field);

  // Sort key
  $field = new FieldInputText();
  $field->setLabel(get_vocab('sort_key'))
        ->setLabelAttributes(array('title' => get_vocab('sort_key_note')))
        ->setControlAttributes(array('id'    => 'sort_key',
                                     'name'  => 'sort_key',
                                     'value' => $data['sort_key'],
                                     'maxlength' => maxlength('area.sort_key')));
  $fieldset->addElement($field);

  // Area admin email
  $field = new FieldInputEmail();
  $field->setLabel(get_vocab('area_admin_email'))
    ->setLabelAttribute('title', get_vocab('email_list_note'))
    ->setControlAttributes(array('id'       => 'area_admin_email',
      'name'     => 'area_admin_email',
      'value'    => $data['area_admin_email'],
      'multiple' => true));
  $fieldset->addElement($field);

  // The custom HTML
  if ($auth['allow_custom_html'])
  {
    $field = new FieldTextarea();
    $field->setLabel(get_vocab('custom_html'))
      ->setLabelAttribute('title', get_vocab('custom_html_note'))
      ->setControlAttribute('name', 'custom_html')
      ->setControlText($data['custom_html']);
    $fieldset->addElement($field);
  }

  // Timezone
  $field = new FieldSelect();
  $field->setLabel(get_vocab('timezone'))
        ->setControlAttributes(array('id'   => 'area_timezone',
                                     'name' => 'area_timezone'))
        ->addSelectOptions(get_timezone_options(), $timezone, true);
  $fieldset->addElement($field);

  // Default type
  $field = new FieldSelect();
  $field->setLabel(get_vocab('default_type'))
        ->setControlAttribute('name', 'area_default_type')
        ->addSelectOptions(get_type_options(), $data['default_type'], true);
  $fieldset->addElement($field);

  // Status - Enabled or Disabled
  $options = array('0' => get_vocab('enabled'),
    '1' => get_vocab('disabled'));
  $value = ($data['disabled']) ? '1' : '0';
  $field = new FieldInputRadioGroup();
  $field->setAttribute('id', 'status')
    ->setLabel(get_vocab('status'))
    ->setLabelAttributes(array('title' => get_vocab('disabled_area_note')))
    ->addRadioOptions($options, 'area_disabled', $value, true);
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

  // Times along the top
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('times_along_top'))
        ->setControlAttribute('name', 'area_times_along_top')
        ->setControlChecked($data['times_along_top']);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_times()
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
        ->setControlAttributes(array('id'       => 'area_start_first_slot',
                                     'name'     => 'area_start_first_slot',
                                     'value'    => $value,
                                     'required' => true));
  $fieldset->addElement($field);

  // Resolution
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('area_res_mins'))
        ->setControlAttributes(array('id'       => 'area_res_mins',
                                     'name'     => 'area_res_mins',
                                     'min'      => '1',
                                     'value'    => (int) $resolution/60,
                                     'required' => true));
  $fieldset->addElement($field);

  // Duration
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('area_def_duration_mins'))
        ->setControlAttributes(array('id'       => 'area_def_duration_mins',
                                     'name'     => 'area_def_duration_mins',
                                     'min'      => '1',
                                     'value'    => (int) $default_duration/60,
                                     'required' => true));
  $options = array('1' => get_vocab('all_day'));
  $checked = ($default_duration_all_day) ? '1' : null;
  $checkbox_group = new FieldInputCheckboxGroup();
  $checkbox_group->addCheckboxOptions($options, 'area_def_duration_all_day', $checked, true);
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
        ->setControlAttributes(array('id'       => 'area_start_last_slot',
                                     'name'     => 'area_start_last_slot',
                                     'value'    => $value,
                                     'required' => true));
  $fieldset->addElement($field);


  return $fieldset;
}


function get_fieldset_periods()
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
                                       'required' => true),
                                 false)
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


function get_fieldset_create_ahead()
{
  global $min_create_ahead_secs, $max_create_ahead_secs,
         $min_create_ahead_enabled, $max_create_ahead_enabled;

  $min_create_ahead_value = $min_create_ahead_secs;
  toTimeString($min_create_ahead_value, $min_create_ahead_units);
  $max_create_ahead_value = $max_create_ahead_secs;
  toTimeString($max_create_ahead_value, $max_create_ahead_units);

  $options = get_time_unit_options();

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('booking_creation'));


  // Minimum create ahead
  $field = new FieldDiv();

  $checkbox = new ElementInputCheckbox();
  $checkbox->setAttributes(array('name'  => 'area_min_create_ahead_enabled',
                                 'class' => 'enabler'))
           ->setChecked($min_create_ahead_enabled);

  $input = new ElementInputNumber();
  $input->setAttributes(array('name'  => 'area_min_create_ahead_value',
                              'value' => $min_create_ahead_value));

  $select = new ElementSelect();
  $select->setAttribute('name', 'area_min_create_ahead_units')
         ->addSelectOptions($options, array_search($min_create_ahead_units, $options), true);

  $field->setLabel(get_vocab('min_book_ahead'))
        ->addControlElement($checkbox)
        ->addControlElement($input)
        ->addControlElement($select);

  $fieldset->addElement($field);


  // Maximum create ahead
  $field = new FieldDiv();

  $checkbox = new ElementInputCheckbox();
  $checkbox->setAttributes(array('name'  => 'area_max_create_ahead_enabled',
                                 'class' => 'enabler'))
           ->setChecked($max_create_ahead_enabled);

  $input = new ElementInputNumber();
  $input->setAttributes(array('name'  => 'area_max_create_ahead_value',
                              'value' => $max_create_ahead_value));

  $select = new ElementSelect();
  $select->setAttribute('name', 'area_max_create_ahead_units')
         ->addSelectOptions($options, array_search($max_create_ahead_units, $options), true);

  $field->setLabel(get_vocab('max_book_ahead'))
        ->addControlElement($checkbox)
        ->addControlElement($input)
        ->addControlElement($select);

  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_delete_ahead()
{
  global $min_delete_ahead_secs, $max_delete_ahead_secs,
         $min_delete_ahead_enabled, $max_delete_ahead_enabled;

  $min_delete_ahead_value = $min_delete_ahead_secs;
  toTimeString($min_delete_ahead_value, $min_delete_ahead_units);
  $max_delete_ahead_value = $max_delete_ahead_secs;
  toTimeString($max_delete_ahead_value, $max_delete_ahead_units);

  $options = get_time_unit_options();

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('booking_deletion'));

  // Minimum delete ahead
  $field = new FieldDiv();

  $checkbox = new ElementInputCheckbox();
  $checkbox->setAttributes(array('name'  => 'area_min_delete_ahead_enabled',
                                 'class' => 'enabler'))
           ->setChecked($min_delete_ahead_enabled);

  $input = new ElementInputNumber();
  $input->setAttributes(array('name'  => 'area_min_delete_ahead_value',
                              'value' => $min_delete_ahead_value));

  $select = new ElementSelect();
  $select->setAttribute('name', 'area_min_delete_ahead_units')
         ->addSelectOptions($options, array_search($min_delete_ahead_units, $options), true);

  $field->setLabel(get_vocab('min_book_ahead'))
        ->addControlElement($checkbox)
        ->addControlElement($input)
        ->addControlElement($select);

  $fieldset->addElement($field);


  // Maximum delete ahead
  $field = new FieldDiv();

  $checkbox = new ElementInputCheckbox();
  $checkbox->setAttributes(array('name'  => 'area_max_delete_ahead_enabled',
                                 'class' => 'enabler'))
           ->setChecked($max_delete_ahead_enabled);

  $input = new ElementInputNumber();
  $input->setAttributes(array('name'  => 'area_max_delete_ahead_value',
                              'value' => $max_delete_ahead_value));

  $select = new ElementSelect();
  $select->setAttribute('name', 'area_max_delete_ahead_units')
         ->addSelectOptions($options, array_search($max_delete_ahead_units, $options), true);

  $field->setLabel(get_vocab('max_book_ahead'))
        ->addControlElement($checkbox)
        ->addControlElement($input)
        ->addControlElement($select);

  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_max_number()
{
  global $interval_types,
         $max_per_interval_area_enabled, $max_per_interval_global_enabled,
         $max_per_interval_area, $max_per_interval_global;

  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('class', 'max_limits')
           ->addLegend(get_vocab('booking_limits'));

  // Add the column headings
  $field = new FieldDiv;

  $span_area = new ElementSpan();
  $span_area->setText(get_vocab('this_area'));

  $span_global = new ElementSpan();
  $span_global->setAttribute('title', get_vocab('whole_system_note'))
              ->setText(get_vocab('whole_system'));

  $field->addControlElement($span_area)
        ->addControlElement($span_global);

  $fieldset->addElement($field);

  // Then do the individual settings
  foreach ($interval_types as $interval_type)
  {
    $field = new FieldDiv;

    $checkbox_area = new ElementInputCheckbox();
    $checkbox_area->setAttributes(array('name'  => "area_max_per_${interval_type}_enabled",
                                        'id'    => "area_max_per_${interval_type}_enabled",
                                        'class' => 'enabler'))
                  ->setChecked($max_per_interval_area_enabled[$interval_type]);

    $number_area = new ElementInputNumber();
    $number_area->setAttributes(array('min'   => '0',
                                      'name'  => "area_max_per_${interval_type}",
                                      'value' => $max_per_interval_area[$interval_type]));

    // Wrap the area and global controls in <div>s.  It'll make the CSS easier.
    $div_area = new ElementDiv();
    $div_area->addElement($checkbox_area)
             ->addElement($number_area);

    // The global settings can't be changed here: they are just shown for information.  The global
    // settings have to be changed in the config file.
    $checkbox_global = new ElementInputCheckbox();
    $checkbox_global->setAttributes(array('disabled' => true))
                    ->setChecked($max_per_interval_global_enabled[$interval_type]);

    $number_global = new ElementInputNumber();
    $number_global->setAttributes(array('value' => $max_per_interval_global[$interval_type],
                                        'disabled' => true));

    $div_global = new ElementDiv();
    $div_global->addElement($checkbox_global)
               ->addElement($number_global);

    $field->setLabel(get_vocab("max_per_${interval_type}"))
          ->addControlElement($div_area)
          ->addControlElement($div_global);

    $fieldset->addElement($field);
  }

  return $fieldset;
}


function get_fieldset_max_secs()
{
  global $interval_types,
         $max_secs_per_interval_area_enabled, $max_secs_per_interval_global_enabled,
         $max_secs_per_interval_area, $max_secs_per_interval_global;

  // Limit the units to 'hours' because 'days' can confuse the user.  That's because
  // the policy check only checks for time used during the booking 'day', ie between
  // the start of the first slot and the end of the last slot, which is normally less
  // than 24 hours.  So a 'day' would be 24 hours, not a booking 'day'.
  $max_unit = 'hours';

  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('class', 'max_limits')
           ->addLegend(get_vocab('booking_limits_secs'));

  // Add the column headings
  $field = new FieldDiv;

  $span_area = new ElementSpan();
  $span_area->setText(get_vocab('this_area'));

  $span_global = new ElementSpan();
  $span_global->setAttribute('title', get_vocab('whole_system_note'))
              ->setText(get_vocab('whole_system'));

  $field->addControlElement($span_area)
        ->addControlElement($span_global);

  $fieldset->addElement($field);

  // Then do the individual settings
  foreach ($interval_types as $interval_type)
  {
    $field = new FieldDiv;

    $checkbox_area = new ElementInputCheckbox();
    $checkbox_area->setAttributes(array('name'  => "area_max_secs_per_${interval_type}_enabled",
                                        'id'    => "area_max_secs_per_${interval_type}_enabled",
                                        'class' => 'enabler'))
                  ->setChecked($max_secs_per_interval_area_enabled[$interval_type]);

    $max = $max_secs_per_interval_area[$interval_type];
    toTimeString($max, $units, true, $max_unit);
    $options = get_time_unit_options($max_unit);

    $select = new ElementSelect();
    $select->setAttribute('name', "area_max_secs_per_${interval_type}_units")
           ->addSelectOptions($options, array_search($units, $options), true);

    $time_area = new ElementInputNumber();
    $time_area->setAttributes(array('min'   => '0',
                                    'name'  => "area_max_secs_per_${interval_type}",
                                    'value' => $max));

    // Wrap the area and global controls in <div>s.  It'll make the CSS easier.
    $div_area = new ElementDiv();
    $div_area->addElement($checkbox_area)
             ->addElement($time_area)
             ->addElement($select);

    // The global settings can't be changed here: they are just shown for information.  The global
    // settings have to be changed in the config file.
    $checkbox_global = new ElementInputCheckbox();
    $checkbox_global->setAttributes(array('disabled' => true))
                    ->setChecked($max_secs_per_interval_global_enabled[$interval_type]);

    $max = $max_secs_per_interval_global[$interval_type];
    toTimeString($max, $units, true, $max_unit);

    $time_global = new ElementInputNumber();
    $time_global->setAttributes(array('value' => $max,
                                      'disabled' => true));

    $select = new ElementSelect();
    $select->setAttribute('disabled', true)
           ->addSelectOptions($options, array_search($units, $options), true);

    $div_global = new ElementDiv();
    $div_global->addElement($checkbox_global)
               ->addElement($time_global)
               ->addElement($select);

    $field->setLabel(get_vocab("max_secs_per_${interval_type}"))
          ->addControlElement($div_area)
          ->addControlElement($div_global);

    $fieldset->addElement($field);
  }

  return $fieldset;
}


function get_fieldset_max_duration()
{
  global $max_duration_enabled, $max_duration_secs, $max_duration_periods;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('booking_durations'));

  // Enable checkbox
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('max_duration'))
        ->setControlAttributes(array('name'  => 'area_max_duration_enabled',
                                     'class' => 'enabler'))
        ->setChecked($max_duration_enabled);
  $fieldset->addElement($field);

  // Periods
  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('mode_periods'))
        ->setControlAttributes(array('name'  => 'area_max_duration_periods',
                                     'value' => $max_duration_periods,
                                     'min'   => '0'));
  $fieldset->addElement($field);

  // Times
  $max_duration_value = $max_duration_secs;
  toTimeString($max_duration_value, $max_duration_units);
  $options = get_time_unit_options();

  $select = new ElementSelect();
  $select->setAttribute('name', 'area_max_duration_units')
         ->addSelectOptions($options, array_search($max_duration_units, $options), true);

  $field = new FieldInputNumber();
  $field->setLabel(get_vocab('mode_times'))
        ->setControlAttributes(array('name'  => 'area_max_duration_value',
                                     'value' => $max_duration_value,
                                     'min'   => '0'))
        ->addElement($select);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_booking_policies()
{
  global $enable_periods;

  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'booking_policies')
           ->addLegend(get_vocab('booking_policies'));

  // Note when using periods
  $field = new FieldSpan();
  if (!$enable_periods)
  {
    $field->setAttribute('class', 'js_none');
  }
  $field->setAttribute('id', 'book_ahead_periods_note')
        ->setControlText(get_vocab('book_ahead_note_periods'));

  $fieldset->addElement($field)
           ->addElement(get_fieldset_create_ahead())
           ->addElement(get_fieldset_delete_ahead())
           ->addElement(get_fieldset_max_number())
           ->addElement(get_fieldset_max_secs())
           ->addElement(get_fieldset_max_duration());

  return $fieldset;
}


function get_fieldset_confirmation_settings()
{
  global $confirmation_enabled, $confirmed_default;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('confirmation_settings'));

  // Confirmation enabled
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('allow_confirmation'))
        ->setControlAttribute('name', 'area_confirmation_enabled')
        ->setChecked($confirmation_enabled);
  $fieldset->addElement($field);

  // Default settings
  $options = array('1' => get_vocab('default_confirmed'),
                   '0' => get_vocab('default_tentative'));
  $value = ($confirmed_default) ? '1' : '0';
  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('default_settings_conf'))
        ->addRadioOptions($options, 'area_confirmed_default', $value, true);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_approval_settings()
{
  global $approval_enabled, $reminders_enabled;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('approval_settings'));

  // Approval enabled
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('enable_approval'))
        ->setControlAttribute('name', 'area_approval_enabled')
        ->setChecked($approval_enabled);
  $fieldset->addElement($field);

  // Reminders enabled
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('enable_reminders'))
        ->setControlAttribute('name', 'area_reminders_enabled')
        ->setChecked($reminders_enabled);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_privacy_settings()
{
  global $private_enabled, $private_mandatory, $private_default;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('private_settings'));

  // Private enabled
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('allow_private'))
        ->setControlAttribute('name', 'area_private_enabled')
        ->setChecked($private_enabled);
  $fieldset->addElement($field);

  // Private mandatory
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('force_private'))
        ->setControlAttribute('name', 'area_private_mandatory')
        ->setChecked($private_mandatory);
  $fieldset->addElement($field);

  // Default settings
  $options = array('1' => get_vocab('default_private'),
                   '0' => get_vocab('default_public'));
  $value = ($private_default) ? '1' : '0';
  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('default_settings'))
        ->addRadioOptions($options, 'area_private_default', $value, true);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_privacy_display()
{
  global $private_override;

  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('private_display'));

  $options = array('none'    => get_vocab('treat_respect'),
                   'private' => get_vocab('treat_private'),
                   'public'  => get_vocab('treat_public'));
  $field = new FieldInputRadioGroup();
  $field->setLabel(get_vocab('private_display_label'))
        ->addLabelClass('no_suffix')
        ->setLabelAttribute('title', get_vocab('private_display_caution'))
        ->setAttribute('class', 'multiline')
        ->addControlClass('long')
        ->addRadioOptions($options, 'area_private_override', $private_override, true);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_submit_buttons()
{
  $fieldset = new ElementFieldset();

  // The back and submit buttons
  $field = new FieldInputSubmit();

  $back = new ElementInputSubmit();
  $back->setAttributes(array('value'      => get_vocab('backadmin'),
                             'formaction' => multisite('admin.php')));
  $field->addLabelClass('no_suffix')
        ->addLabelElement($back)
        ->setControlAttribute('value', get_vocab('change'));
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
  'area'      => isset($area) ? $area : null,
  'room'      => isset($room) ? $room : null
);

print_header($context);

// Get the details for this area
if (!isset($area) || is_null($data = get_area_details($area)))
{
  fatal_error(get_vocab('invalid_area'));
}

$errors = get_form_var('errors', 'array');

// Generate the form
$form = new Form();

$attributes = array('id'     => 'edit_area',
                    'class'  => 'standard',
                    'action' => multisite('edit_area_handler.php'),
                    'method' => 'post');

$form->setAttributes($attributes)
     ->addHiddenInput('area', $area);

$outer_fieldset = new ElementFieldset();

$outer_fieldset->addLegend(get_vocab('editarea'))
               ->addElement(get_fieldset_errors($errors))
               ->addElement(get_fieldset_general($data))
               ->addElement(get_fieldset_times())
               ->addElement(get_fieldset_periods())
               ->addElement(get_fieldset_booking_policies())
               ->addElement(get_fieldset_confirmation_settings())
               ->addElement(get_fieldset_approval_settings())
               ->addElement(get_fieldset_privacy_settings())
               ->addElement(get_fieldset_privacy_display())
               ->addElement(get_fieldset_submit_buttons());

$form->addElement($outer_fieldset);

$form->render();


print_footer();
