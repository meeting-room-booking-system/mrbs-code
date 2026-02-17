<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


/**
 * Get the form data from the request variables and store it in the Area object.
 */
function get_form_data(Area &$area) : void
{
  global $interval_types;

  // The non-standard form variables
  $form_vars = array(
    'sort_key'                      => 'string',
    'area_name'                     => 'string',
    'area_disabled'                 => 'bool',
    'area_timezone'                 => 'string',
    'area_admin_email'              => 'string',
    'area_start_first_slot'         => 'string',
    'area_start_last_slot'          => 'string',
    'area_res_mins'                 => 'int',
    'area_def_duration_mins'        => 'int',
    'area_def_duration_all_day'     => 'bool',
    'area_min_create_ahead_enabled' => 'bool',
    'area_min_create_ahead_value'   => 'int',
    'area_min_create_ahead_units'   => 'string',
    'area_max_create_ahead_enabled' => 'bool',
    'area_max_create_ahead_value'   => 'int',
    'area_max_create_ahead_units'   => 'string',
    'area_min_delete_ahead_enabled' => 'bool',
    'area_min_delete_ahead_value'   => 'int',
    'area_min_delete_ahead_units'   => 'string',
    'area_max_delete_ahead_enabled' => 'bool',
    'area_max_delete_ahead_value'   => 'int',
    'area_max_delete_ahead_units'   => 'string',
    'area_max_duration_enabled'     => 'bool',
    'area_max_duration_periods'     => 'int',
    'area_max_duration_value'       => 'int',
    'area_max_duration_units'       => 'string',
    'area_private_enabled'          => 'bool',
    'area_private_default'          => 'bool',
    'area_private_mandatory'        => 'bool',
    'area_private_override'         => 'string',
    'area_approval_enabled'         => 'bool',
    'area_reminders_enabled'        => 'bool',
    'area_enable_periods'           => 'bool',
    'area_periods'                  => 'array',
    'area_confirmation_enabled'     => 'bool',
    'area_confirmed_default'        => 'bool',
    'area_default_type'             => 'string',
    'area_times_along_top'          => 'bool',
    'area_periods_booking_opens'    => 'string',
    'custom_html'                   => 'string',
    'period_starts'                 => 'array',
    'period_ends'                   => 'array'
  );

  // Add in the max_per_interval form variables
  foreach ($interval_types as $interval_type)
  {
    $form_vars["area_max_per_$interval_type"] =               'int';
    $form_vars["area_max_per_{$interval_type}_enabled"] =       'bool';
    $form_vars["area_max_secs_per_$interval_type"] =          'int';
    $form_vars["area_max_secs_per_{$interval_type}_units"] =    'string';
    $form_vars["area_max_secs_per_{$interval_type}_enabled"] =  'bool';
  }

  // TODO: get rid of the need for a prefix and the rather messy processing below
  $prefix = 'area_';

  // GET THE FORM DATA
  foreach($form_vars as $var => $var_type)
  {
    $value = get_form_var($var, $var_type);

    // Ignore any null values - the field might have been disabled by JavaScript
    if (is_null($value))
    {
      continue;
    }

    // Trim any strings
    if (is_string($value))
    {
      $value = trim($value);
    }

    // Strip any prefix off the beginning of the variable name to get the corresponding property,
    // except in a few special cases
    switch ($var)
    {
      case 'area_def_duration_all_day':
        $property = 'default_duration_all_day';
        break;
      case 'area_name':
      case 'area_admin_email':
        $property = $var;
        break;
      default:
        $property = preg_replace('/^' . preg_quote($prefix) . '/', '', $var);
        break;
    }

    $area->$property = $value;
  }

  if ($area->enable_periods)
  {
    // TODO: This is a kludge until we store use_period_times in the database.
    // We need to make sure that the period start times correspond to the correct periods.
    if (count($area->periods) == count($area->period_starts) + 1)
    {
      // Need to operate on a copy of the array to avoid the error "Indirect modification of overloaded property
      // MRBS\Area::$period_starts has no effect".
      // See https://stackoverflow.com/questions/10454779/php-indirect-modification-of-overloaded-property
      $period_starts = $area->period_starts;
      array_unshift($period_starts, null);
      $area->period_starts = $period_starts;
    }

    // Assemble the periods as an object.
    $periods_tmp = new Periods($area->id);
    for ($i = 0; $i < count($area->periods); $i++)
    {
      $periods_tmp->add(new Period(
        $area->periods[$i],
        $area->period_starts[$i] ?? null,
        $area->period_ends[$i] ?? null
      ));
    }
    $area->periods = $periods_tmp;
  }
  // We don't need these properties any more as they are now stored in the periods property.
  unset($area->periods_starts);
  unset($area->periods_ends);
}


/**
 * Tidy up and validate the form data
 *
 * @return array  An array of error messages, or an empty array if there are no errors.
 */
function validate_form_data(Area &$area) : array
{
  global $interval_types;

  // Initialise the error array
  $errors = [];

  // Check the name hasn't been used in another area
  $tmp_area = Area::getByName($area->area_name);
  if (isset($tmp_area) && ($tmp_area->id != $area->id))
  {
    $errors[] = 'invalid_area_name';
  }

  // Clean up the address list replacing newlines by commas and removing duplicates
  $area->area_admin_email = clean_address_list($area->area_admin_email);

  // Validate email addresses
  if (!validate_email_list($area->area_admin_email))
  {
    $errors[] = 'invalid_email';
  }

  // Check that the time formats are correct (hh:mm).  They should be, because
  // the HTML5 element or polyfill will force them to be, but just in case ...
  // (for example if we are relying on a polyfill and JavaScript is disabled)
  if (!preg_match(REGEX_HHMM, $area->start_first_slot) ||
      !preg_match(REGEX_HHMM, $area->start_last_slot))
  {
    $errors[] = 'invalid_time_format';
  }
  else
  {
    // Get morningstarts and eveningends
    list($area->morningstarts, $area->morningstarts_minutes) = explode(':', $area->start_first_slot);
    list($area->eveningends, $area->eveningends_minutes) = explode(':', $area->start_last_slot);

    // Convert the book ahead times into seconds
    if (isset($area->min_create_ahead_units))
    {
      $area->min_create_ahead_secs = from_time_string(array(
        'value' => $area->min_create_ahead_value,
        'units' => $area->min_create_ahead_units
      ));
    }
    if (isset($area->max_create_ahead_units))
    {
      $area->max_create_ahead_secs = from_time_string(array(
        'value' => $area->max_create_ahead_value,
        'units' => $area->max_create_ahead_units
      ));
    }
    if (isset($area->min_delete_ahead_units))
    {
      $area->min_delete_ahead_secs = from_time_string(array(
        'value' => $area->min_delete_ahead_value,
        'units' => $area->min_delete_ahead_units
      ));
    }
    if (isset($area->max_delete_ahead_units))
    {
      $area->max_delete_ahead_secs = from_time_string(array(
        'value' => $area->max_delete_ahead_value,
        'units' => $area->max_delete_ahead_units
      ));
    }

    // Convert the max_duration into seconds
    if (isset($area->max_duration_units))
    {
      $area->max_duration_secs = from_time_string(array(
        'value' => $area->max_duration_value,
        'units' => $area->max_duration_units
      ));
    }

    // Now do the max_secs variables (limits on the total length of bookings)
    foreach ($interval_types as $interval_type)
    {
      $units_property = "max_secs_per_{$interval_type}_units";
      if (isset($area->$units_property))
      {
        $secs_property = "max_secs_per_$interval_type";
        $area->$secs_property = from_time_string(array(
          'value' => $area->$secs_property,
          'units' => $area->$units_property
        ));
      }
    }

    // Periods mode validation
    if ($area->enable_periods)
    {
      // Validate the periods, but only if we are using period times.
      if (isset($area->period_starts[0]) && (true !== ($result = $area->periods->validate())))
      {
        $errors[] = $result;
      }
      $properties = array(
        'min_create_ahead_secs',
        'max_create_ahead_secs',
        'min_delete_ahead_secs',
        'max_delete_ahead_secs'
      );

      // Round these down to the nearest whole day (anything less than a day is meaningless when using periods)
      foreach ($properties as $property)
      {
        if (isset($area->$property))
        {
          $area->$property -= $area->$property % SECONDS_PER_DAY;
        }
      }
    }

    // Times mode validation
    else
    {
      // Avoid divide by zero errors
      if ($area->res_mins == 0)
      {
        $errors[] = 'invalid_resolution';
      }
      else
      {
        // Get the resolution
        $area->resolution = $area->res_mins * 60;

        // Check morningstarts, eveningends, and resolution for consistency
        $start_first_slot = ($area->morningstarts*60) + $area->morningstarts_minutes;   // minutes
        $start_last_slot  = ($area->eveningends*60) + $area->eveningends_minutes;       // minutes

        // If eveningends is before morningstarts then it's really on the next day
        if (hm_before(array('hours' => $area->eveningends, 'minutes' => $area->eveningends_minutes),
                      array('hours' => $area->morningstarts, 'minutes' => $area->morningstarts_minutes)))
        {
          $start_last_slot += MINUTES_PER_DAY;
        }

        $start_difference = ($start_last_slot - $start_first_slot);         // minutes

        if ($start_difference%$area->res_mins != 0)
        {
          $errors[] = 'invalid_resolution';
        }

        if (!$area->default_duration_all_day)
        {
          // If the default duration is all day, then this value will have
          // been disabled on the form, so don't change it.
          $area->default_duration = $area->def_duration_mins * 60;
        }
      }
    }
  }

  return $errors;
}


// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

if (empty($area))
{
  throw new \Exception('$area is empty');
}

// Lock the table while we update the area
if (!db()->mutex_lock(_tbl(Area::TABLE_NAME)))
{
  fatal_error(get_vocab('failed_to_acquire'));
}

// Get the existing area
$area_object = Area::getById($area);
if (!isset($area_object))
{
  throw new \Exception("The area with id $area no longer exists");
}

get_form_data($area_object);
$errors = validate_form_data($area_object);

if (empty($errors))
{
  // Everything is OK, update the database and go back to the admin page.
  $area_object->save();
  $location = "admin.php?day=$day&month=$month&year=$year&area=$area";
}
else
{
  // Errors in the form data - go back to the form
  $query_string = "area=$area";
  foreach ($errors as $error)
  {
    $query_string .= "&errors[]=$error";
  }
  $location = "edit_area.php?$query_string";
}

// Unlock the table
db()->mutex_unlock(_tbl(Area::TABLE_NAME));

// Go back to wherever.
location_header($location);
