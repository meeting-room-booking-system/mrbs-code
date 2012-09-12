<?php

// $Id$

// This file contains internal configuration settings and checking.   You should not
// need to change this file unless you are making changes to the MRBS code.


/********************************************************
 * Deprecated configuration variables
 ********************************************************/

// If they are still using some of the old configuration variables
// then replace them with their new equivalents.   (Should maybe warn
// the site admin somehow if they are being used?   Perhaps when a 
// database upgrade is performed?)

// Variables deprecated in versions of MRBS > 1.4.4.1
if (isset($provisional_enabled))
{
  $approval_enabled = ($provisional_enabled) ? TRUE : FALSE;
}

// Variables deprecated in versions of MRBS > 1.4.5

if (isset($mail_settings['admin_all']))
{
  // We won't set $mail_settings['on_new'] because the default is TRUE
  // which gives the same behaviour as before, and if it's been set to FALSE
  // it means the site admin has deliberately changed it.
  $mail_settings['on_change'] = ($mail_settings['admin_all']) ? TRUE : FALSE;
}
if (isset($mail_settings['admin_on_delete']))
{
  $mail_settings['on_delete'] = ($mail_settings['admin_on_delete']) ? TRUE : FALSE;
}
if (!empty($dateformat))
{
  $strftime_format['daymonth']     = "%d %b";
}

// Variables no longer used in versions of MRBS > 1.4.7

// $highlight_method
// $javascript_cursor
// $mail_charset


/********************************************************
 * Checking
 ********************************************************/
// Do some consistency checking of user settings from config.inc.php
if ($enable_periods)
{
  if (count($periods) > 60)
  {
    die('Configuration error: too many periods defined');
  }
}
else
{
  if (!isset($resolution))
  {
    die('Configuration error: $resolution has not been set.');
  }
  if ($resolution <= 0)
  {
    die('Configuration error: $resolution is less than or equal to zero.');
  }
  if ($resolution%60 != 0)
  {
    die('Configuration error: $resolution is not an integral number of minutes.');
  }
  $start_first_slot = ($morningstarts*60) + $morningstarts_minutes;   // minutes
  $start_last_slot  = ($eveningends*60) + $eveningends_minutes;       // minutes
  $start_difference = ($start_last_slot - $start_first_slot) * 60;    // seconds
  if ($start_difference%$resolution != 0)
  {
    die('Configuration error: make sure that the length of the booking day is an integral multiple of $resolution.');
  }
}

/***************************************
 * DOCTYPE - internal use, do not change
 ***************************************/

 define('DOCTYPE', '<!DOCTYPE html>');
 
 // Records which DOCTYPE is being used.    Do not change - it will not change the DOCTYPE
 // that is used;  it is merely used when the code needs to know the DOCTYPE, for example
 // in calls to nl2br.   TRUE means XHTML, FALSE means HTML.
 define('IS_XHTML', FALSE);

 
/*************************************************
 * REPORT constants - internal use, do not change
 *************************************************/
 
// Constant definitions for the value of the output parameter. 
define('REPORT',       0);
define('SUMMARY',      1);

// Constants defining the ouput format.
define('OUTPUT_HTML',  0);
define('OUTPUT_CSV',   1);
define('OUTPUT_ICAL',  2);

// Constants for booking privacy matching
define('PRIVATE_NO',   0);
define('PRIVATE_YES',  1);
define('PRIVATE_BOTH', 2);  // Can be anything other than 0 or 1

// Constants for booking confirmation matching
define('CONFIRMED_NO',   0);
define('CONFIRMED_YES',  1);
define('CONFIRMED_BOTH', 2);  // Can be anything other than 0 or 1

// Constants for booking approval matching
define('APPROVED_NO',   0);
define('APPROVED_YES',  1);
define('APPROVED_BOTH', 2);  // Can be anything other than 0 or 1

// Constants for mode
define('MODE_TIMES',   1);
define('MODE_PERIODS', 2);

// Formats for sprintf
define('FORMAT_TIMES',   "%.2f");
define('FORMAT_PERIODS', "%d");


 /*************************************************
 * USED IN EDIT_ENTRY - internal use, do not change
 *************************************************/
 
// Regular expressions used to define mandatory text fields, eg the 'name' field.   The first
// is a positive version used in the HTML5 pattern attribute.   The second is a negative version
// used by JavaScript for client side validation if the browser does not support pattern validation.
define('REGEX_TEXT_POS', '\s*\S+.*');        // At least one non-whitespace character (we will trim in the handler)
define('REGEX_TEXT_NEG', '/(^$)|(^\s+$)/');  // Cannot be blank or all whitespaces

// Minimum useful value for rep_num_weeks
define('REP_NUM_WEEKS_MIN',  2);  // In theory 1 would be OK, but then you'd use Weekly


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
 * REPEAT TYPE CODES - internal use, do not change
 *************************************************/
 
define('REP_NONE',            0);
define('REP_DAILY',           1);
define('REP_WEEKLY',          2);
define('REP_MONTHLY',         3);
define('REP_YEARLY',          4);
define('REP_MONTHLY_SAMEDAY', 5);
define('REP_N_WEEKLY',        6);


/*************************************************
 * DIRECTORIES - internal use, do not change
 *************************************************/
 
define('TZDIR',           'tzurl/zoneinfo');          // Directory containing TZURL definitions
define('TZDIR_OUTLOOK',   'tzurl/zoneinfo-outlook');  // Outlook compatible TZURL definitions


/*************************************************
 * ICALENDAR CONSTANTS - internal use, do not change
 *************************************************/
 
define ('RFC5545_FORMAT', 'Ymd\THis');  // Format for expressing iCalendar dates
define ('ICAL_EOL', "\r\n");            // Lines must be terminated by CRLF


/****************************************************************
 * DATABASE TABLES  - internal use, do not change
 ****************************************************************/

// CUSTOM FIELDS
// Prefix for custom field variable names
define('VAR_PREFIX', 'f_');  // must begin with a letter;

// STANDARD FIELDS
// These are the standard fields in the database tables.   If you add more
// standard (not user defined, custom) fields, then you need to change these

$standard_fields['entry'] = array('id',
                                  'start_time',
                                  'end_time',
                                  'entry_type',
                                  'repeat_id',
                                  'room_id',
                                  'timestamp',
                                  'create_by',
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
                                  'ical_recur_id');
                                  
$standard_fields['repeat'] = array('id',
                                   'start_time',
                                   'end_time',
                                   'rep_type',
                                   'end_date',
                                   'rep_opt',
                                   'room_id',
                                   'timestamp',
                                   'create_by',
                                   'name',
                                   'type',
                                   'description',
                                   'rep_num_weeks',
                                   'status',
                                   'reminded',
                                   'info_time',
                                   'info_user',
                                   'info_text',
                                   'ical_uid',
                                   'ical_sequence');

$standard_fields['room'] = array('id',
                                 'disabled',
                                 'area_id',
                                 'room_name',
                                 'sort_key',
                                 'description',
                                 'capacity',
                                 'room_admin_email',
                                 'custom_html');

// Boolean fields.    These are fields which are treated as booleans                                
$boolean_fields['area'] = array('default_duration_all_day',
                                'private_enabled',
                                'private_default',
                                'private_mandatory',
                                'min_book_ahead_enabled',
                                'max_book_ahead_enabled',
                                'max_per_day_enabled',
                                'max_per_week_enabled',
                                'max_per_month_enabled',
                                'max_per_year_enabled',
                                'max_per_future_enabled',
                                'max_duration_enabled',  // not yet a per-area setting, but will be sometime
                                'approval_enabled',
                                'reminders_enabled',
                                'enable_periods',
                                'confirmation_enabled',
                                'confirmed_default');
                                
// Permitted values for 'private_override'
$private_override_options = array('none', 'public', 'private');
                                   
/********************************************************
 * Miscellaneous
 ********************************************************/
// Save some of the default per-area settings for later use.   We
// do this because they will get overwritten by the values for
// the current area in a moment - in standard_vars.inc by a call to 
// get_area_settings().   [This isn't a very elegant way of handling
// per-area settings and perhaps ought to be revisited at some stage]

$area_defaults = array();
$area_defaults['timezone']                 = $timezone;
$area_defaults['resolution']               = $resolution;
$area_defaults['default_duration']         = $default_duration;
$area_defaults['default_duration_all_day'] = $default_duration_all_day;
$area_defaults['morningstarts']            = $morningstarts;
$area_defaults['morningstarts_minutes']    = $morningstarts_minutes;
$area_defaults['eveningends']              = $eveningends;
$area_defaults['eveningends_minutes']      = $eveningends_minutes;
$area_defaults['private_enabled']          = $private_enabled;
$area_defaults['private_default']          = $private_default;
$area_defaults['private_mandatory']        = $private_mandatory;
$area_defaults['private_override']         = $private_override;
$area_defaults['min_book_ahead_enabled']   = $min_book_ahead_enabled;
$area_defaults['max_book_ahead_enabled']   = $max_book_ahead_enabled;
$area_defaults['min_book_ahead_secs']      = $min_book_ahead_secs;
$area_defaults['max_book_ahead_secs']      = $max_book_ahead_secs;
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
$area_defaults['approval_enabled']         = $approval_enabled;
$area_defaults['reminders_enabled']        = $reminders_enabled;
$area_defaults['enable_periods']           = $enable_periods;
$area_defaults['confirmation_enabled']     = $confirmation_enabled;
$area_defaults['confirmed_default']        = $confirmed_default;

// We send Ajax requests to del_entry_ajax.php with data as an array of ids.
// In order to stop the POST request getting too large and triggering a 406
// error, we split the requests into batches with a maximum number of ids
// in the array defined below.
define('DEL_ENTRY_AJAX_BATCH_SIZE', 100);

// Interval types used in booking policies
$interval_types = array('day', 'week', 'month', 'year', 'future');


/********************************************************
 * JavaScript - internal use, do not change
 ********************************************************/

// Setting $use_strict = TRUE will put the MRBS JavaScript into strict mode.  Useful
// for debugging.
$use_strict = FALSE;
       

/********************************************************
 * PHP System Configuration - internal use, do not change
 ********************************************************/

// Disable magic quoting on database returns:
if (get_magic_quotes_runtime())
{
  if (version_compare(PHP_VERSION, '5.3.0') >= 0)
  {
    ini_set('magic_quotes_runtime', 0);
  }
  else
  {
    set_magic_quotes_runtime(false);
  }
}

// Make sure notice errors are not reported, they can break mrbs code:
$error_level = E_ALL ^ E_NOTICE;
if (defined("E_DEPRECATED"))
{
  $error_level = $error_level ^ E_DEPRECATED;
}
error_reporting ($error_level);

?>