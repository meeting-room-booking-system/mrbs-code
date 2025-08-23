<?php
declare(strict_types=1);
namespace MRBS;

// This file contains internal configuration settings and checking.   You should not
// need to change this file unless you are making changes to the MRBS code.


/********************************************************
 * Disused configuration variables
 ********************************************************/

// If they are still using some of the old configuration variables
// then replace them with their new equivalents and give a warning.

// Variables no longer used in versions of MRBS > 1.4.4.1
if (isset($provisional_enabled))
{
  $message = 'Please check your MRBS config file.   The variable $provisional_enabled ' .
             'is no longer used and has been replaced by $approval_enabled.';
  trigger_error($message, E_USER_NOTICE);
  $approval_enabled = (bool) $provisional_enabled;
}

// Variables no longer used in versions of MRBS > 1.4.5
if (isset($mail_settings['admin_all']))
{
  // We won't set $mail_settings['on_new'] because the default is TRUE
  // which gives the same behaviour as before, and if it's been set to FALSE
  // it means the site admin has deliberately changed it.
  $message = 'Please check your MRBS config file.   The variable $mail_settings["admin_all"] ' .
             'is no longer used and has been replaced by $mail_settings["on_new"], ' .
             '$mail_settings["on_change"] and $mail_settings["on_delete"].';
  trigger_error($message, E_USER_NOTICE);
  $mail_settings['on_change'] = (bool) $mail_settings['admin_all'];
}

if (isset($mail_settings['admin_on_delete']))
{
  $message = 'Please check your MRBS config file.   The variable $mail_settings["admin_on_delete"] ' .
             'is no longer used and has been replaced by $mail_settings["on_delete"].';
  trigger_error($message, E_USER_NOTICE);
  $mail_settings['on_delete'] = (bool) $mail_settings['admin_on_delete'];
}

if (isset($dateformat))
{
  $message = 'Please check your MRBS config file. The setting $dateformat ' .
             'is no longer used and has been replaced by $datetime_formats. ' .
             'See systemdefaults.inc.php for more details.';
  trigger_error($message, E_USER_NOTICE);
}

// Variables no longer used in versions of MRBS > 1.4.7
if (isset($highlight_method))
{
  $message = 'Please check your MRBS config file.   The variable $highlight_method ' .
             'is no longer used and is redundant.';
  trigger_error($message, E_USER_NOTICE);
}

if (isset($javascript_cursor))
{
  $message = 'Please check your MRBS config file.   The variable $javascript_cursor ' .
             'is no longer used and is redundant.';
  trigger_error($message, E_USER_NOTICE);
}

if (isset($mail_charset))
{
  $message = 'Please check your MRBS config file.   The variable $mail_charset ' .
             'is no longer used.   All emails are sent as UTF-8.';
  trigger_error($message, E_USER_NOTICE);
}

// Variables no longer used in versions of MRBS > 1.4.11
if (isset($min_book_ahead_enabled))
{
  $message = 'Please check your MRBS config file.   The variable $min_book_ahead_enabled ' .
             'is no longer used and has been replaced by $min_create_ahead_enabled ' .
             'and $min_delete_ahead_enabled.';
  trigger_error($message, E_USER_WARNING);
  $min_create_ahead_enabled = (bool) $min_book_ahead_enabled;
  $min_delete_ahead_enabled = (bool) $min_book_ahead_enabled;
}

if (isset($max_book_ahead_enabled))
{
  $message = 'Please check your MRBS config file.   The variable $max_book_ahead_enabled ' .
             'is no longer used and has been replaced by $max_create_ahead_enabled ' .
             'and $max_delete_ahead_enabled.';
  trigger_error($message, E_USER_WARNING);
  $max_create_ahead_enabled = (bool) $max_book_ahead_enabled;
  // No need to do anything about $max_delete_ahead_enabled as it didn't apply in the old system
}

if (isset($min_book_ahead_secs))
{
  $message = 'Please check your MRBS config file.   The variable $min_book_ahead_secs ' .
             'is no longer used and has been replaced by $min_create_ahead_secs ' .
             'and $min_delete_ahead_secs.';
  trigger_error($message, E_USER_WARNING);
  $min_create_ahead_secs = $min_book_ahead_secs;
  $min_delete_ahead_secs = $min_book_ahead_secs;
}

if (isset($max_book_ahead_secs))
{
  $message = 'Please check your MRBS config file.   The variable $max_book_ahead_secs ' .
             'is no longer used and has been replaced by $max_create_ahead_secs ' .
             'and $max_delete_ahead_secs.';
  trigger_error($message, E_USER_WARNING);
  $max_create_ahead_secs = $max_book_ahead_secs;
  $max_delete_ahead_secs = $max_book_ahead_secs;
}

if (isset($maxlength))
{
  $message = 'Please check your MRBS config file.   The variable $maxlength ' .
             'is no longer used and maximum field lengths are now calculated automatically.';
  trigger_error($message, E_USER_NOTICE);
}

// Variables no longer used in versions of MRBS > 1.5.0
if (isset($db_nopersist))
{
  $db_persist = !$db_nopersist;
  $message = 'Please check your MRBS config file.  The $db_nopersist config variable ' .
             'has been replaced by $db_persist';
  trigger_error($message, E_USER_NOTICE);
}

// Variables no longer used in versions of MRBS > 1.7.1
if (false !== ($key = array_search('start_date', $edit_entry_field_order)))
{
  $edit_entry_field_order[$key] = 'start_time';
  $message = 'Please check your MRBS config file.  The value \'start_date\' in the variable ' .
             '$edit_entry_field_order has been replaced by \'start_time\'.';
  trigger_error($message, E_USER_NOTICE);
}

if (false !== ($key = array_search('end_date', $edit_entry_field_order)))
{
  $edit_entry_field_order[$key] = 'end_time';
  $message = 'Please check your MRBS config file.  The value \'end_date\' in the variable ' .
             '$edit_entry_field_order has been replaced by \'end_time\'.';
  trigger_error($message, E_USER_NOTICE);
}

if (false !== ($key = array_search('areas', $edit_entry_field_order)))
{
  $edit_entry_field_order[$key] = 'room_id';
  $message = 'Please check your MRBS config file.  The value \'areas\' in the variable ' .
             '$edit_entry_field_order has been replaced by \'room_id\'.';
  trigger_error($message, E_USER_NOTICE);
}

if (false !== ($key = array_search('rooms', $edit_entry_field_order)))
{
  if (in_array('room_id', $edit_entry_field_order))
  {
    array_splice($edit_entry_field_order, $key, 1);
  }
  else
  {
    $edit_entry_field_order[$key] = 'room_id';
  }
  $message = 'Please check your MRBS config file.  The value \'rooms\' in the variable ' .
             '$edit_entry_field_order has been replaced by \'room_id\'.';
  trigger_error($message, E_USER_NOTICE);
}

// Variables no longer used in versions of MRBS > 1.11.0
if (isset($twentyfourhour_format))
{
  $message = 'Please check your MRBS config file. The setting $twentyfourhour_format ' .
             'is no longer used and whether a 12 or 24-hour clock is used is determined ' .
             'by the locale. See systemdefaults.inc.php for more details.';
  trigger_error($message, E_USER_NOTICE);
}

if (isset($strftime_format))
{
  $message = 'Please check your MRBS config file. The setting $strftime_format ' .
             'is no longer used and has been replaced by $datetime_formats. ' .
             'See systemdefaults.inc.php for more details.';
  trigger_error($message, E_USER_NOTICE);
}


/********************************************************
 * Checking
 ********************************************************/

// Check that $timezone has been set
if (!isset($timezone))
{
  die('MRBS configuration error: $timezone has not been set.');
}

// Do some consistency checking of user settings from config.inc.php
if ($enable_periods)
{
  if (isset($periods) && (count($periods) > 60))
  {
    die('MRBS configuration error: too many periods defined');
  }
}
else
{
  if (!isset($resolution))
  {
    die('MRBS configuration error: $resolution has not been set.');
  }
  if ($resolution <= 0)
  {
    die('MRBS configuration error: $resolution is less than or equal to zero.');
  }
  if ($resolution%60 != 0)
  {
    die('MRBS configuration error: $resolution is not an integral number of minutes.');
  }
  // Not safe to call get_start_first_slot() etc. here as the timezone won't necessarily have
  // been set yet(although quite often it will have been by php.ini using date.timezone)
  $start_first_slot = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $start_last_slot = (($eveningends * 60) + $eveningends_minutes) * 60;
  if ($start_last_slot < $start_first_slot)
  {
    $start_last_slot += 60*60*24;
  }
  $start_difference = $start_last_slot - $start_first_slot;    // seconds
  if ($start_difference%$resolution != 0)
  {
    die('MRBS configuration error: make sure that the length of the booking day is an integral multiple of $resolution.');
  }
}


/***************************************
 * DOCTYPE - internal use, do not change
 ***************************************/

 define('DOCTYPE', '<!DOCTYPE html>');


/*************************************************
 * General constants - internal use, do not change
 *************************************************/

define('DAYS_PER_WEEK',       7);
define('MONTHS_PER_YEAR',     12);
define('MINUTES_PER_DAY',     24*60);
define('SECONDS_PER_DAY',     MINUTES_PER_DAY * 60);
define('SECONDS_PER_HOUR',    3600);
define('SECONDS_PER_MINUTE',  60);

define('MRBS_HEAVY_CHECK_MARK', '&#10004;');

/*************************************************
 * REPORT constants - internal use, do not change
 *************************************************/

// Constant definitions for the value of the output parameter.
define('REPORT',       0);
define('SUMMARY',      1);

// Constants defining the output format.
define('OUTPUT_HTML',  0);
define('OUTPUT_CSV',   1);
define('OUTPUT_ICAL',  2);

// Fallback values
define('FALLBACK_SORTBY', 'r');
define('FALLBACK_SUMBY',  'c');

// Constants for matching boolean fields
define('BOOLEAN_MATCH_FALSE', 0);
define('BOOLEAN_MATCH_TRUE',  1);
define('BOOLEAN_MATCH_BOTH',  2);

// Constants for mode
define('MODE_TIMES',   1);
define('MODE_PERIODS', 2);

// Formats for sprintf
define('FORMAT_TIMES',   "%.2f");
define('FORMAT_PERIODS', "%d");


 /*************************************************
 * USED IN FORMS - internal use, do not change
 *************************************************/

// Regular expressions used to define mandatory text fields, eg the 'name' field.   The first
// is a positive version used in the HTML5 pattern attribute.   The second is a negative version
// used by JavaScript for client side validation if the browser does not support pattern validation.
define('REGEX_TEXT_POS', '\s*\S+.*');        // At least one non-whitespace character (we will trim in the handler)
define('REGEX_TEXT_NEG', '/(^$)|(^\s+$)/');  // Cannot be blank or all whitespaces
define('REGEX_HHMM', '/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/');  // A time string in the form 'hh:mm'


 /*************************************************
 * ENTRY TYPES - internal use, do not change
 *************************************************/

 // The entry_type field in the entry table records the type of
 // booking as follows:

 define('ENTRY_SINGLE',       0);  // A single entry that is not part of a series
 define('ENTRY_RPT_ORIGINAL', 1);  // An entry that is part of a series and has not been modified
 define('ENTRY_RPT_CHANGED',  2);  // An entry that is part of a series and has been modified


/*************************************************
 * ENTRY STATUS CODES - internal use, do not change
 *************************************************/

// The status code field for an entry is a tinyint (smallint on PostgreSQL)
// with individual bits set to record the various possible boolean properties
// of a booking:
//
// Bit 0:  Privacy status (set = private)
// Bit 1:  Approval status (set = not yet approved)
// Bit 2:  Confirmation status (set = not yet confirmed)
//
// A "standard" booking has status 0x00;


define('STATUS_PRIVATE',           0x01);
define('STATUS_AWAITING_APPROVAL', 0x02);
define('STATUS_TENTATIVE',         0x04);


/*************************************************
 * DIRECTORIES - internal use, do not change
 *************************************************/

define('TZDIR',         'tzurl/zoneinfo');          // Directory containing TZURL definitions
define('TZDIR_OUTLOOK', 'tzurl/zoneinfo-outlook');  // Outlook compatible TZURL definitions


/****************************************************************
 * DATABASE TABLES  - internal use, do not change
 ****************************************************************/

// CUSTOM FIELDS
// Prefix for custom field variable names
define('VAR_PREFIX', 'f_');  // must begin with a letter;

// STANDARD FIELDS
// These are the standard fields in the database tables.   If you add more
// standard (not user defined, custom) fields, then you need to change these

$standard_fields['entry'] = array(
    'id',
    'start_time',
    'end_time',
    'entry_type',
    'repeat_id',
    'room_id',
    'timestamp',
    'create_by',
    'modified_by',
    'name',
    'type',
    'description',
    'status',
    'reminded',
    'info_time',
    'info_user',
    'info_text',
    'ical_uid',
    'ical_sequence',
    'ical_recur_id',
    'allow_registration',
    'registrant_limit',
    'registrant_limit_enabled',
    'registration_opens',
    'registration_opens_enabled',
    'registration_closes',
    'registration_closes_enabled'
  );

$standard_fields['repeat'] = array(
    'id',
    'start_time',
    'end_time',
    'rep_type',
    'end_date',
    'rep_opt',
    'room_id',
    'timestamp',
    'create_by',
    'modified_by',
    'name',
    'type',
    'description',
    'rep_interval',
    'month_absolute',
    'month_relative',
    'status',
    'reminded',
    'info_time',
    'info_user',
    'info_text',
    'ical_uid',
    'ical_sequence'
  );

$standard_fields['room'] = array('id',
                                 'disabled',
                                 'area_id',
                                 'room_name',
                                 'sort_key',
                                 'description',
                                 'capacity',
                                 'room_admin_email',
                                 'invalid_types',
                                 'custom_html');

// Permitted values for 'private_override'
$private_override_options = array('none', 'public', 'private');

/********************************************************
 * Miscellaneous
 ********************************************************/
// Save some of the default per-area settings for later use.   We
// do this because they will get overwritten by the values for
// the current area in a moment - in standard_vars.inc by a call to
// get_area_settings().   [This isn't a very elegant way of handling
// per-area settings and ought to be revisited at some stage]

$area_defaults_keys = array('timezone',
                            'resolution',
                            'default_duration',
                            'default_duration_all_day',
                            'morningstarts',
                            'morningstarts_minutes',
                            'eveningends',
                            'eveningends_minutes',
                            'private_enabled',
                            'private_default',
                            'private_mandatory',
                            'private_override',
                            'min_create_ahead_enabled',
                            'max_create_ahead_enabled',
                            'min_create_ahead_secs',
                            'max_create_ahead_secs',
                            'min_delete_ahead_enabled',
                            'max_delete_ahead_enabled',
                            'min_delete_ahead_secs',
                            'max_delete_ahead_secs',
                            'max_duration_enabled',
                            'max_duration_secs',
                            'max_duration_periods',
                            'approval_enabled',
                            'reminders_enabled',
                            'enable_periods',
                            'periods',
                            'confirmation_enabled',
                            'confirmed_default',
                            'times_along_top',
                            'default_type',
                            'periods_booking_opens');

$area_defaults = array();

foreach ($area_defaults_keys as $key)
{
  $area_defaults[$key] = (isset($$key)) ? $$key : null;
}

$area_defaults['max_per_day_enabled']      = $max_per_interval_area_enabled['day'];
$area_defaults['max_per_day']              = $max_per_interval_area['day'];
$area_defaults['max_per_week_enabled']     = $max_per_interval_area_enabled['week'];
$area_defaults['max_per_week']             = $max_per_interval_area['week'];
$area_defaults['max_per_month_enabled']    = $max_per_interval_area_enabled['month'];
$area_defaults['max_per_month']            = $max_per_interval_area['month'];
$area_defaults['max_per_year_enabled']     = $max_per_interval_area_enabled['year'];
$area_defaults['max_per_year']             = $max_per_interval_area['year'];
$area_defaults['max_per_future_enabled']   = $max_per_interval_area_enabled['future'];
$area_defaults['max_per_future']           = $max_per_interval_area['future'];

$area_defaults['max_secs_per_day_enabled']      = $max_secs_per_interval_area_enabled['day'];
$area_defaults['max_secs_per_day']              = $max_secs_per_interval_area['day'];
$area_defaults['max_secs_per_week_enabled']     = $max_secs_per_interval_area_enabled['week'];
$area_defaults['max_secs_per_week']             = $max_secs_per_interval_area['week'];
$area_defaults['max_secs_per_month_enabled']    = $max_secs_per_interval_area_enabled['month'];
$area_defaults['max_secs_per_month']            = $max_secs_per_interval_area['month'];
$area_defaults['max_secs_per_year_enabled']     = $max_secs_per_interval_area_enabled['year'];
$area_defaults['max_secs_per_year']             = $max_secs_per_interval_area['year'];
$area_defaults['max_secs_per_future_enabled']   = $max_secs_per_interval_area_enabled['future'];
$area_defaults['max_secs_per_future']           = $max_secs_per_interval_area['future'];

// Interval types used in booking policies
$interval_types = array('day', 'week', 'month', 'year', 'future');
