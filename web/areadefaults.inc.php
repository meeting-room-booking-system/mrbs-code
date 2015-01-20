<?php

// $Id$

/**************************************************************************
 *   MRBS area defaults file (default settings for NEW areas)
 *
 * DO _NOT_ MODIFY THIS FILE YOURSELF. IT IS FOR _INTERNAL_ USE ONLY.
 *
 * TO CONFIGURE MRBS FOR YOUR SYSTEM ADD CONFIGURATION PARAMETERS FROM
 * THIS FILE INTO config.inc.php, DO _NOT_ EDIT THIS FILE.
 *
 * This file contains the default settings for configuration parameters that
 * can be set on a per-area basis by using a web browser and following the
 * "Rooms" link in MRBS.    The settings in this file just determine the
 * default values that are used when new areas are created.   They are kept
 * in a separate file to system defaults to draw attention to the fact that
 * they are merely the default settings for new areas:  it can be a little
 * frustrating sometimes to edit these values and find they have no effect
 * on existing areas
 **************************************************************************/

 

/*******************
 * Calendar settings
 *******************/

// This setting controls whether to use "clock" or "times" based intervals
// (FALSE and the default) or user defined periods (TRUE).   

// $enable_periods is settable on a per-area basis.

$enable_periods = FALSE;  // Default value for new areas

 
// TIMES SETTINGS
// --------------

// These settings are all set per area through MRBS.   These are the default
// settings that are used when a new area is created.

// The "Times" settings are ignored if $enable_periods is TRUE.

// Note: Be careful to avoid specifying options that display blocks overlapping
// the next day, since it is not properly handled.

// Resolution - what blocks can be booked, in seconds.
// Default is half an hour: 1800 seconds.
$resolution = (30 * 60);  // DEFAULT VALUE FOR NEW AREAS

// If the following variable is set to TRUE, the resolution of bookings
// is forced to be the value of $resolution, rather than the resolution set
// for the area in the database.
$force_resolution = FALSE;

// Default duration - default length (in seconds) of a booking.
// Defaults to (60 * 60) seconds, i.e. an hour
$default_duration = (60 * 60);  // DEFAULT VALUE FOR NEW AREAS
// Whether the "All Day" checkbox should be checked by default.  (Note
// that even if this is set to true, $default_duration should still
// be set as that is the duration that will be used when the All Day
// checkbox is unchecked)
$default_duration_all_day = FALSE;  // DEFAULT VALUE FOR NEW AREAS

// Start and end of day.
// NOTE:  The time between the beginning of the last and first
// slots of the day must be an integral multiple of the resolution.
// If the last slot is before the first slot, then the booking day is
// assumed to span midnight and the last slot is on the day after the
// first slot.


// The default settings below (along with the 30 minute resolution above)
// give you 24 half-hourly slots starting at 07:00, with the last slot
// being 18:30 -> 19:00

// The beginning of the first slot of the day (DEFAULT VALUES FOR NEW AREAS)
$morningstarts         = 7;   // must be integer in range 0-23
$morningstarts_minutes = 0;   // must be integer in range 0-59

// The beginning of the last slot of the day (DEFAULT VALUES FOR NEW AREAS)
$eveningends           = 18;  // must be integer in range 0-23
$eveningends_minutes   = 30;   // must be integer in range 0-59

// Example 1.
// If resolution=3600 (1 hour), morningstarts = 8 and morningstarts_minutes = 30 
// then for the last period to start at say 4:30pm you would need to set eveningends = 16
// and eveningends_minutes = 30

// Example 2.
// To get a full 24 hour display with 15-minute steps, set morningstarts=0; eveningends=23;
// eveningends_minutes=45; and resolution=900.
//
// Example 3.
// To get a booking day running from 6.00 pm to 2.00 am with 30 minute steps, set
// morningstarts=18, morningstarts_minutes = 0, eveningends = 1, eveningends_minutes = 30
// and resolution = 1800.



/******************
 * Booking policies
 ******************/

// It is possible to set policies that restrict how far in advance ordinary users can make
// bookings.   Both minimum and maximum values can be set.  It is also possible to distinguish
// between creating new bookings and deleting existing bookings.  Editing an existing booking
// involves deleting the existing booking and creating a new booking at the (possibly) new time.
// So if for example you want to stop people editing existing bookings, but allow the creation
// of new bookings, then you will need to prevent deletion but allow creation.

// If the variables below are set to TRUE, MRBS will force a minimum and/or maximum advance
// booking time on ordinary users (admins can make bookings for whenever they like).   The
// minimum advance booking time allows you to set a policy saying that users must book
// at least so far in advance.  The maximum allows you to set a policy saying that they cannot
// book more than so far in advance.  How the times are determined depends on whether Periods
// or Times are being used.

// DEFAULT VALUES FOR NEW AREAS

// Creating new bookings
$min_create_ahead_enabled = FALSE;    // set to TRUE to enforce a minimum advance booking time
$max_create_ahead_enabled = FALSE;    // set to TRUE to enforce a maximum advance booking time

// Deleting existing bookings
$min_delete_ahead_enabled = FALSE;    // set to TRUE to enforce a minimum advance booking time
$max_delete_ahead_enabled = FALSE;    // set to TRUE to enforce a maximum advance booking time

// The advance booking limits are measured in seconds and are set by the two variables below.
// The relevant time for determining whether a booking is allowed is the start time of the
// booking.  Values may be negative: for example setting $min_delete_ahead_secs = -300 means
// that users cannot delete (and this will include editing) a booking more than 5 minutes in
// the past.


// DEFAULT VALUES FOR NEW AREAS

// Creating new bookings
$min_create_ahead_secs = 0;           // (seconds) cannot book in the past
$max_create_ahead_secs = 60*60*24*7;  // (seconds) no more than one week ahead

// Deleting existing bookings
$min_delete_ahead_secs = 0;           // (seconds) cannot book in the past
$max_delete_ahead_secs = 60*60*24*7;  // (seconds) no more than one week ahead

// NOTE:  If you are using periods, MRBS has no notion of when the periods occur during the
// day, and so cannot impose policies of the kind "users must book at least one period
// in advance".    However it can impose policies such as "users must book at least
// one day in advance".   The two values above are rounded down to the nearest whole 
// number of days when using periods.   For example 86401 will be rounded down to 86400
// (one day) and 1 will be rounded down to 0.
//
// As MRBS does not know when the periods occur in the day, there is no way of specifying, for example,
// that bookings must be made at least 24 hours in advance.    Setting $min_create_ahead_secs=86400
// will allow somebody to make a booking at 11:59 pm for the first period the next day, which
// which may occur at 8.00 am.


// Set a maximum duration for bookings
$max_duration_enabled = FALSE; // Set to TRUE if you want to enforce a maximum duration
$max_duration_secs = 60*60*2;  // (seconds) - when using "times"
$max_duration_periods = 2;     // (periods) - when using "periods"



// DEFAULT VALUES FOR NEW AREAS
// Set the maximum number of bookings that can be made in an area by any one user, in an
// interval, which can be a day, week, month or year, or else in the future.  (A week is
// defined by the $weekstarts setting).   These are per-area settings but you can use them
// in conjunction with the global settings.   This would allow you to set policies such as
// allowing a maximum of 10 bookings per month in total with a maximum of 1 per day in Area A.
$max_per_interval_area_enabled['day']    = FALSE;
$max_per_interval_area['day'] = 1;      // max 1 bookings per day in an area

$max_per_interval_area_enabled['week']   = FALSE;
$max_per_interval_area['week'] = 5;     // max 5 bookings per week in an area

$max_per_interval_area_enabled['month']  = FALSE;
$max_per_interval_area['month'] = 10;   // max 10 bookings per month in an area

$max_per_interval_area_enabled['year']   = FALSE;
$max_per_interval_area['year'] = 50;    // max 50 bookings per year in an area

$max_per_interval_area_enabled['future'] = FALSE;
$max_per_interval_area['future'] = 100; // max 100 bookings in the future in an area

/************************
 * Miscellaneous settings
 ************************/
 
// PRIVATE BOOKINGS SETTINGS

// These settings are all set per area through MRBS.   These are the default
// settings that are used when a new area is created.

// Only administrators or the person who booked a private event can see
// details of the event.  Everyone else just sees that the time/period
// is booked on the schedule.

$private_enabled = FALSE;  // DEFAULT VALUE FOR NEW AREAS
           // Display checkbox in entry page to make
           // the booking private.

$private_mandatory = FALSE;  // DEFAULT VALUE FOR NEW AREAS
           // If TRUE all new/edited entries will 
           // use the value from $private_default when saved.
           // If checkbox is displayed it will be disabled.
           
$private_default = FALSE;  // DEFAULT VALUE FOR NEW AREAS
           // Set default value for "Private" flag on new/edited entries.
           // Used if the $private_enabled checkbox is displayed
           // or if $private_mandatory is set.

$private_override = "none";  // DEFAULT VALUE FOR NEW AREAS
           // Override default privacy behavior. 
           // "none" - Private flag on entry is used
           // "private" - ALL entries are treated as private regardless
           //             of private flag on the entry.
           // "public" - NO entry is treated as private, regardless of
           //            private flag on the entry.
           // Overrides $private_default and $private_mandatory
           // Consider your users' expectations of privacy before
           // changing to "public" or from "private" to "none"

 
// SETTINGS FOR APPROVING BOOKINGS - PER-AREA

// These settings control whether bookings made by ordinary users need to be
// approved by an admin.   The settings here are the default settings for new
// areas.  The settings for individual areas can be changed from within MRBS.

$approval_enabled = FALSE;  // Set to TRUE to enable booking approval

// Set to FALSE if you don't want users to be able to send reminders
// to admins when bookings are still awaiting approval.
$reminders_enabled = TRUE;


// SETTINGS FOR BOOKING CONFIRMATION

// Allows bookings to be marked as "tentative", ie not yet 100% certain,
// and confirmed later.   Useful if you want to reserve a slot but at the same
// time let other people know that there's a possibility it may not be needed.
$confirmation_enabled = TRUE;

// The default confirmation status for new bookings.  (TRUE: confirmed, FALSE: tentative)
// Only used if $confirmation_enabled is TRUE.   If $confirmation_enabled is 
// FALSE, then all new bookings are confirmed automatically.
$confirmed_default = TRUE;
