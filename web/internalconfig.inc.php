<?php

// $Id$

// This file contains internal configuration settings.   You should not need 
// to change them unless you are making changes to the MRBS code.


/***************************************
 * DOCTYPE - internal use, do not change
 ***************************************/

 define('DOCTYPE', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">');
 
 // Records which DOCTYPE is being used.    Do not change - it will not change the DOCTYPE
 // that is used;  it is merely used when the code needs to know the DOCTYPE, for example
 // in calls to nl2br.   TRUE means XHTML, FALSE means HTML.
 define('IS_XHTML', FALSE);

/*************************************************
 * ENTRY STATUS CODES - internal use, do not change
 *************************************************/

// The booking status codes that are used in the status column in the
// entry table.   Although there are only two codes at the moment, the
// codes can be added to later if additional status types are required.
// The default code in the database table is 1, ie a confirmed booking.

define('STATUS_PROVISIONAL', 0);
define('STATUS_CONFIRMED',   1);

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
                                  'private',
                                  'status',
                                  'reminded',
                                  'info_time',
                                  'info_user',
                                  'info_text');
                                  
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
                                   'private',
                                   'reminded',
                                   'info_time',
                                   'info_user',
                                   'info_text');

// Boolean fields.    These are fields which are treated as booleans                                
$boolean_fields['area'] = array('private_enabled',
                                'private_default',
                                'private_mandatory',
                                'min_book_ahead_enabled',
                                'max_book_ahead_enabled',
                                'provisional_enabled',
                                'reminders_enabled',
                                'enable_periods');
                                   
/********************************************************
 * Miscellaneous
 ********************************************************/
// Save some of the default per-area settings for later use.   We
// do this because they will get overwritten by the values for
// the current area in a moment - in standard_vars.inc by a call to 
// get_area_settings().   [This isn't a very elegant way of handling
// per-area settings and perhaps ought to be revisited at some stage]

$area_defaults = array();
$area_defaults['resolution']             = $resolution;
$area_defaults['default_duration']       = $default_duration;
$area_defaults['morningstarts']          = $morningstarts;
$area_defaults['morningstarts_minutes']  = $morningstarts_minutes;
$area_defaults['eveningends']            = $eveningends;
$area_defaults['eveningends_minutes']    = $eveningends_minutes;
$area_defaults['private_enabled']        = $private_enabled;
$area_defaults['private_default']        = $private_default;
$area_defaults['private_mandatory']      = $private_mandatory;
$area_defaults['private_override']       = $private_override;
$area_defaults['min_book_ahead_enabled'] = $min_book_ahead_enabled;
$area_defaults['max_book_ahead_enabled'] = $max_book_ahead_enabled;
$area_defaults['min_book_ahead_secs']    = $min_book_ahead_secs;
$area_defaults['max_book_ahead_secs']    = $max_book_ahead_secs;
$area_defaults['provisional_enabled']    = $provisional_enabled;
$area_defaults['reminders_enabled']      = $reminders_enabled;
$area_defaults['enable_periods']         = $enable_periods;
 
                               
/********************************************************
 * PHP System Configuration - internal use, do not change
 ********************************************************/

// Disable magic quoting on database returns:
set_magic_quotes_runtime(0);

// Make sure notice errors are not reported, they can break mrbs code:
$error_level = E_ALL ^ E_NOTICE;
if (defined("E_DEPRECATED"))
{
  $error_level = $error_level ^ E_DEPRECATED;
}
error_reporting ($error_level);

?>