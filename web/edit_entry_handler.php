<?php
namespace MRBS;

require 'defaultincludes.inc';
require_once 'mrbs_sql.inc';
require_once 'functions_ical.inc';
require_once 'functions_mail.inc';

use MRBS\Form\Form;
use MRBS\Form\ElementInputSubmit;


function invalid_booking($message)
{
  global $view, $view_all, $year, $month, $day, $area, $room;

  $context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => isset($room) ? $room : null
  );

  print_header($context);
  echo "<h1>" . get_vocab('invalid_booking') . "</h1>\n";
  echo "<p>$message</p>\n";
  // Print footer and exit
  print_footer(true);
}


$is_ajax = is_ajax();

if ($is_ajax && !checkAuthorised(this_page(), true))
{
  exit;
}


// Check the CSRF token
Form::checkToken();


// (1) Check the user is authorised for this page
//  ---------------------------------------------
checkAuthorised(this_page());

$mrbs_user = session()->getCurrentUser();
$mrbs_username = (isset($mrbs_user)) ? $mrbs_user->username : null;


// (2) Get the form variables
// --------------------------

// NOTE:  the code on this page assumes that array form variables are passed
// as an array of values, rather than an array indexed by value.   This is
// particularly important for checkbox arrays whicgh should be formed like this:
//
//    <input type="checkbox" name="foo[]" value="n">
//    <input type="checkbox" name="foo[]" value="m">
//
// and not like this:
//
//    <input type="checkbox" name="foo[n]" value="1">
//    <input type="checkbox" name="foo[m]" value="1">


// This page can be called with an Ajax call.  In this case it just checks
// the validity of a proposed booking and does not make the booking.

// Get non-standard form variables
$form_vars = array(
  'create_by'               => 'string',
  'name'                    => 'string',
  'description'             => 'string',
  'start_seconds'           => 'int',
  'start_date'              => 'string',
  'end_seconds'             => 'int',
  'end_date'                => 'string',
  'all_day'                 => 'string',  // bool, actually
  'type'                    => 'string',
  'rooms'                   => 'array',
  'original_room_id'        => 'int',
  'ical_uid'                => 'string',
  'ical_sequence'           => 'int',
  'ical_recur_id'           => 'string',
  'allow_registration'      => 'string',  // bool, actually
  'enable_registrant_limit' => 'string',  // bool, actually
  'registrant_limit'        => 'int',
  'returl'                  => 'string',
  'id'                      => 'int',
  'rep_id'                  => 'int',
  'edit_type'               => 'string',
  'rep_type'                => 'int',
  'rep_end_date'            => 'string',
  'rep_day'                 => 'array',   // array of bools
  'rep_interval'            => 'int',
  'month_type'              => 'int',
  'month_absolute'          => 'int',
  'month_relative_ord'      => 'string',
  'month_relative_day'      => 'string',
  'skip'                    => 'string',  // bool, actually
  'no_mail'                 => 'string',  // bool, actually
  'private'                 => 'string',  // bool, actually
  'confirmed'               => 'string',
  'back_button'             => 'string',
  'timetohighlight'         => 'int',
  'commit'                  => 'string'
);

foreach($form_vars as $var => $var_type)
{
  $$var = get_form_var($var, $var_type);

  // Trim the strings and truncate them to the maximum field length
  if (is_string($$var))
  {
    $$var = trim($$var);
    $$var = truncate($$var, "entry.$var");
  }

}

// If they're not an admin and multi-day bookings are not allowed, then
// set the end date to the start date
if (!is_book_admin($rooms) && $auth['only_admin_can_book_multiday'])
{
  $end_date = $start_date;
}

list($start_year, $start_month, $start_day) = split_iso_date($start_date);
list($end_year, $end_month, $end_day) = split_iso_date($end_date);

if (isset($rep_end_date))
{
  list($rep_end_year, $rep_end_month, $rep_end_day) = split_iso_date($rep_end_date);
}

// BACK:  we didn't really want to be here - send them to the returl
if (!empty($back_button))
{
  if (empty($returl))
  {
    $returl = "index.php";
  }
  location_header($returl);
}

// Get custom form variables
$custom_fields = array();

// Get the information about the fields in the entry table
$fields = db()->field_info(_tbl('entry'));

foreach($fields as $field)
{
  if (!in_array($field['name'], $standard_fields['entry']))
  {
    switch($field['nature'])
    {
      case 'character':
        $f_type = 'string';
        break;
      case 'integer':
        // Smallints and tinyints are considered to be booleans
        $f_type = (isset($field['length']) && ($field['length'] <= 2)) ? 'string' : 'int';
        break;
      case 'decimal':
        $f_type = 'decimal';
        break;
      // We can only really deal with the types above at the moment
      default:
        $f_type = 'string';
        break;
    }

    $var = VAR_PREFIX . $field['name'];
    $custom_fields[$field['name']] = get_form_var($var, $f_type);

    if (($f_type == 'int') && ($custom_fields[$field['name']] === ''))
    {
      $custom_fields[$field['name']] = null;
    }
    // Turn checkboxes into booleans
    if (($field['nature'] == 'integer') &&
        isset($field['length']) &&
        ($field['length'] <= 2))
    {
      $custom_fields[$field['name']] = ($custom_fields[$field['name']]) ? true : false;
    }

    // Trim any strings and truncate them to the maximum field length
    if (is_string($custom_fields[$field['name']]) && ($field['nature'] != 'decimal'))
    {
      $custom_fields[$field['name']] = trim($custom_fields[$field['name']]);
      $custom_fields[$field['name']] = truncate($custom_fields[$field['name']], 'entry.' . $field['name']);
    }

  }
}


// (3) Clean up the form variables
// -------------------------------

// Form validation checks.   Normally checked for client side.

// The id must be either an integer or NULL, so that subsequent code that tests whether
// isset($id) works.  (I suppose one could use !empty instead, but there's always the
// possibility that sites have allowed 0 in their auto-increment/serial columns.)
if (isset($id) && ($id == ''))
{
  unset($id);
}

// Validate the create_by variable, checking that it's the current user, unless the
// user is an admin and the booking is being edited or it's a new booking and we allow
// admins to make bookings on behalf of others.
//
// Only carry out this check if it's not an Ajax request.  If it is an Ajax request then
// $create_by isn't set yet, but a getWritable check will be done later,
if (!$is_ajax)
{
  if (!is_book_admin($rooms) || (!isset($id) && $auth['admin_can_only_book_for_self']))
  {
    if ($create_by !== $mrbs_username)
    {
      $message = "Attempt made by user '$mrbs_username' to make a booking in the name of '$create_by'";
      trigger_error($message, E_USER_NOTICE);
      $create_by = $mrbs_username;
    }
  }
}

if (empty($rooms))
{
  if (!$is_ajax)
  {
    invalid_booking(get_vocab('no_rooms_selected'));
  }
  if ($commit)
  {
    throw new \Exception('No rooms specified');
  }
  // If this is an Ajax request and we're not committing the booking, ie we are just
  // checking for conflicts, then it's perfectly possible to get here without any rooms
  // being selected on the form (just deselect the room on the form with Ctrl Click).  So
  // in this case just return a null response.
  http_headers(array("Content-Type: application/json"));
  echo json_encode(null);
  exit;
}

// Don't bother with these checks if this is an Ajax request.
if (!$is_ajax)
{
  if (!isset($name) || ($name === ''))
  {
    invalid_booking(get_vocab('must_set_description'));
  }

  if (($rep_type != REP_NONE) && ($rep_interval < 1))
  {
    invalid_booking(get_vocab('invalid_rep_interval'));
  }

  if (count($is_mandatory_field))
  {
    foreach ($is_mandatory_field as $field => $value)
    {
      $field = preg_replace('/^entry\./', '', $field);
      if ($value)
      {
        if ((in_array($field, $standard_fields['entry']) && ($$field === '')) ||
            (array_key_exists($field, $custom_fields) && ($custom_fields[$field] === '')))
        {
          invalid_booking(get_vocab('missing_mandatory_field') . ' "' .
                          get_loc_field_name(_tbl('entry'), $field) . '"');
        }
      }
    }
  }
}

if (!isset($type))
{
  $type = $default_type;
}

// Check that the type is allowed
if (!is_book_admin($rooms) && isset($auth['admin_only_types']) && in_array($type, $auth['admin_only_types']))
{
  invalid_booking(get_vocab('type_reserved_for_admins', get_type_vocab($type)));
}

if (isset($month_relative_ord) && isset($month_relative_day))
{
  $month_relative = $month_relative_ord . $month_relative_day;
}

// Handle private booking
// Enforce config file settings if needed
if ($private_mandatory)
{
  $isprivate = $private_default;
}
else
{
  $isprivate = ($private) ? true : false;
}

// Make sure the area corresponds to the room that is being booked
$area = get_area($rooms[0]);
get_area_settings($area);  // Update the area settings

// and that $room is in $area
if (get_area($room) != $area)
{
  $room = get_default_room($area);
}

// Check that they really are allowed to set $no_mail;
if ($no_mail)
{
  if (!$mail_settings['allow_no_mail'] &&
      (!is_book_admin($rooms) || !$mail_settings['allow_admins_no_mail']))
  {
    $no_mail = false;
  }
}

// If this is an Ajax request and we're being asked to commit the booking, then
// we'll only have been supplied with parameters that need to be changed.  Fill in
// the rest from the existing boking information.
// Note: we assume that
// (1) this is not a series (we can't cope with them yet)
// (2) we always get passed start_seconds and end_seconds in the Ajax data
if ($is_ajax && $commit)
{
  $old_booking = get_booking_info($id, false);
  foreach ($form_vars as $var => $var_type)
  {
    if (!isset($$var) || (($var_type == 'array') && empty($$var)))
    {
      switch ($var)
      {
        case 'rooms':
          $rooms = array($old_booking['room_id']);
          break;
        case 'original_room_id':
          $$var = $old_booking['room_id'];
          break;
        case 'private':
          $$var = $old_booking['private'];
          break;
        case 'confirmed':
          $$var = !$old_booking['tentative'];
          break;
        // In the calculation of $start_seconds and $end_seconds below we need to take
        // care of the case when 0000 on the day in question is across a DST boundary
        // from the current time, ie the days on which DST starts and ends.
        case 'start_seconds';
          $date = getdate($old_booking['start_time']);
          $start_year = $date['year'];
          $start_month = $date['mon'];
          $start_day = $date['mday'];
          $start_daystart = mktime(0, 0, 0, $start_month, $start_day, $start_year);
          $old_start = $old_booking['start_time'];
          $start_seconds = $old_start - $start_daystart;
          $start_seconds -= cross_dst($start_daystart, $old_start);
          break;
        case 'end_seconds';
          $date = getdate($old_booking['end_time']);
          $end_year = $date['year'];
          $end_month = $date['mon'];
          $end_day = $date['mday'];
          $end_daystart = mktime(0, 0, 0, $end_month, $end_day, $end_year);
          $old_end = $old_booking['end_time'];
          $end_seconds = $old_end - $end_daystart;
          $end_seconds -= cross_dst($end_daystart, $old_end);
          // When using periods end_seconds is actually the start of the last period
          if ($enable_periods)
          {
            $end_seconds -= 60;
          }
          break;
        default:
          if (array_key_exists($var, $old_booking))
          {
            $$var = $old_booking[$var];
          }
          break;
      }
    }
  }

  // Now the custom fields
  $custom_fields = array();
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $standard_fields['entry']))
    {
      $custom_fields[$field['name']] = $old_booking[$field['name']];
    }
  }
}


// When All Day is checked, $start_seconds and $end_seconds are disabled and so won't
// get passed through by the form.   We therefore need to set them.
if (!empty($all_day))
{
  if ($enable_periods)
  {
    $start_seconds = 12 * SECONDS_PER_HOUR;
    // This is actually the start of the last period, which is what the form would
    // have returned.   It will get corrected in a moment.
    $end_seconds = $start_seconds + ((count($periods) - 1) * 60);
  }
  else
  {
    $start_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $end_seconds = (($eveningends * 60) + $eveningends_minutes) *60;
    $end_seconds += $resolution;  // We want the end of the last slot, not the beginning
    if ($end_seconds <= $start_seconds)
    {
      $end_seconds += SECONDS_PER_DAY;
    }
  }
}

// If we're operating on a booking day that stretches past midnight, it's more convenient
// for the sections past midnight to be shown as being on the day before.  That way the
// $returl will end up taking us back to the day we started on
if (day_past_midnight())
{
  $end_last = (((($eveningends * 60) + $eveningends_minutes) *60) + $resolution) % SECONDS_PER_DAY;
  if ($start_seconds < $end_last)
  {
    $start_seconds += SECONDS_PER_DAY;
    $day_before = getdate(mktime(0, 0, 0, $start_month, $start_day-1, $start_year));
    $start_day = $day_before['mday'];
    $start_month = $day_before['mon'];
    $start_year = $day_before['year'];
  }
}

$target_rooms = $rooms;

// Check that the user has permission to create/edit an entry for this room.
// Get the id of the room that we are creating/editing
if (isset($id))
{
  // Editing an existing booking: get the room_id from the database (you can't
  // get it from $rooms because they are the new rooms)
  $sql = "SELECT room_id
            FROM " . _tbl('entry') . "
           WHERE id=?
           LIMIT 1";
  $existing_room = db()->query1($sql, array($id));
  if ($existing_room < 0)
  {
    // Ideally we should give more feedback to the user when this happens, or
    // even lock the entry once a user starts to edit it.
    $message = "Tried to edit an entry that no longer exists - probably because " .
               "somebody else has deleted it in the meantime.";
    trigger_error($message, E_USER_NOTICE);
    location_header($returl);
  }
  $target_rooms[] = $existing_room;
  $target_rooms = array_unique($target_rooms);
}

// Must have write access to at least one of the rooms
if (!getWritable($create_by, $target_rooms, false))
{
  showAccessDenied($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);
  exit;
}


if ($enable_periods)
{
  $resolution = 60;
}

// Now work out the start and times
$start_time = mktime(0, 0, $start_seconds, $start_month, $start_day, $start_year);
$end_time   = mktime(0, 0, $end_seconds, $end_month, $end_day, $end_year);

// If we're using periods then the endtime we've been returned by the form is actually
// the beginning of the last period in the booking (it's more intuitive for users this way)
// so we need to add on 60 seconds (1 period)
if ($enable_periods)
{
  $end_time = $end_time + 60;
}

// Round down the starttime and round up the endtime to the nearest slot boundaries
// (This step is probably unnecessary now that MRBS always returns times aligned
// on slot boundaries, but is left in for good measure).
$am7 = get_start_first_slot($start_month, $start_day, $start_year);
$start_time = round_t_down($start_time, $resolution, $am7);
$end_time = round_t_up($end_time, $resolution, $am7);

// If they asked for 0 minutes, and even after the rounding the slot length is still
// 0 minutes, push that up to 1 resolution unit.
if ($end_time == $start_time)
{
  $end_time += $resolution;
}

if (isset($rep_type) && ($rep_type != REP_NONE) &&
    isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
  // Get the repeat entry settings
  $rep_end_time = mktime(intval($start_seconds/SECONDS_PER_HOUR),
                         intval(($start_seconds%SECONDS_PER_HOUR)/60),
                         0,
                         $rep_end_month, $rep_end_day, $rep_end_year);
}
else
{
  $rep_type = REP_NONE;
  $rep_end_time = 0;  // to avoid an undefined variable notice
}

if (!isset($rep_day))
{
  $rep_day = array();
}

$rep_opt = "";


// Get the repeat details
if (isset($rep_type) && ($rep_type != REP_NONE))
{
  if ($rep_type == REP_WEEKLY)
  {
    // If no repeat day has been set, then set a default repeat day
    // as the day of the week of the start of the period
    if (count($rep_day) == 0)
    {
      $rep_day[] = date('w', $start_time);
    }
    // Build string of weekdays to repeat on:
    for ($i = 0; $i < DAYS_PER_WEEK; $i++)
    {
      $rep_opt .= in_array($i, $rep_day) ? "1" : "0";  // $rep_opt is a string
    }
  }

  // Make sure that the starttime coincides with a repeat day.  In
  // other words make sure that the first starttime defines an actual
  // entry.   We need to do this because if we are going to construct an iCalendar
  // object, RFC 5545 demands that the start time is the first event of
  // a series.  ["The "DTSTART" property for a "VEVENT" specifies the inclusive
  // start of the event.  For recurring events, it also specifies the very first
  // instance in the recurrence set."]

  $rep_details = array('rep_type'       => $rep_type,
                       'rep_opt'        => $rep_opt,
                       'rep_interval'   => $rep_interval);

  if (isset($month_type))
  {
    if ($month_type == REP_MONTH_ABSOLUTE)
    {
      $rep_details['month_absolute'] = $month_absolute;
    }
    else
    {
      $rep_details['month_relative'] = $month_relative;
    }
  }

  // Get the first entry in the series and make that the start time
  $reps = mrbsGetRepeatEntryList($start_time, $rep_end_time, $rep_details, 1);

  if (count($reps) > 0)
  {
    $duration = $end_time - $start_time;
    $duration -= cross_dst($start_time, $end_time);
    $start_time = $reps[0];
    $end_time = $start_time + $duration;
    $start_day = date('j', $start_time);
    $start_month = date('n', $start_time);
    $start_year = date('Y', $start_time);
  }
}

// If we're committing this booking, get the start day/month/year and
// make them the current day/month/year
if (!$is_ajax || $commit)
{
  $day = $start_day;
  $month = $start_month;
  $year = $start_year;
}

// Set up the return URL.    As the user has tried to book a particular room and a particular
// day, we must consider these to be the new "sticky room" and "sticky day", so modify the
// return URL accordingly.

// First get the return URL basename, having stripped off the old query string
//   (1) It's possible that $returl could be empty, for example if edit_entry.php had been called
//       direct, perhaps if the user has it set as a bookmark
//   (2) Avoid an endless loop.   It shouldn't happen, but just in case ...
//   (3) If you've come from search, you probably don't want to go back there (and if you did we'd
//       have to preserve the search parameter in the query string)
if (isset($returl) && ($returl !== ''))
{
  $returl = parse_url($returl);
  if ($returl !== false)
  {
    if (isset($returl['query']))
    {
      parse_str($returl['query'], $query_vars);
    }
    $view = (isset($query_vars['view'])) ? $query_vars['view'] : $default_view;
    $returl = explode('/', $returl['path']);
    $returl = end($returl);
  }
}

if (empty($returl) ||
    in_array($returl, array('edit_entry.php',
                            'edit_entry_handler.php',
                            'search.php')))
{
  $returl = 'index.php';
}

// If we haven't been given a sensible date then get out of here and don't try and make a booking
if (!isset($start_day) || !isset($start_month) || !isset($start_year) || !checkdate($start_month, $start_day, $start_year))
{
  location_header($returl);
}

// If the old sticky room is one of the rooms requested for booking, then don't change the sticky room.
// Otherwise change the sticky room to be one of the new rooms.
if (!in_array($room, $rooms))
{
  $room = $rooms[0];
}
// Find the corresponding area
$area = mrbsGetRoomArea($room);

// Now construct the new query string
$vars = array('view'  => (isset($view)) ? $view : $default_view,
              'year'  => $year,
              'month' => $month,
              'day'   => $day,
              'area'  => $area,
              'room'  => $room);

$returl .= '?' . http_build_query($vars, '', '&');


// Check to see whether this is a repeat booking and if so, whether the user
// is allowed to make/edit repeat bookings.   (The edit_entry form should
// prevent you ever getting here, but this check is here as a safeguard in
// case someone has spoofed the HTML)
if (isset($rep_type) && ($rep_type != REP_NONE) &&
    !is_book_admin($rooms) &&
    !empty($auth['only_admin_can_book_repeat']))
{
  showAccessDenied($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);
  exit;
}


// (4) Assemble the booking data
// -----------------------------

// Assemble an array of bookings, one for each room
$bookings = array();
foreach ($rooms as $room_id)
{
  // Ignore rooms for which the user doesn't have write access
  if (!getWritable($create_by, $room_id))
  {
    continue;
  }

  $booking = array();
  $booking['create_by'] = $create_by;
  $booking['modified_by'] = (isset($id)) ? $mrbs_username : '';
  $booking['name'] = $name;
  $booking['type'] = $type;
  $booking['description'] = $description;
  $booking['room_id'] = $room_id;
  $booking['start_time'] = $start_time;
  $booking['end_time'] = $end_time;
  $booking['rep_type'] = $rep_type;
  $booking['rep_opt'] = $rep_opt;
  $booking['rep_interval'] = $rep_interval;
  $booking['end_date'] = $rep_end_time;
  $booking['ical_uid'] = $ical_uid;
  $booking['ical_sequence'] = $ical_sequence;
  $booking['ical_recur_id'] = $ical_recur_id;
  $booking['allow_registration'] = $allow_registration;
  $booking['enable_registrant_limit'] = $enable_registrant_limit;
  $booking['registrant_limit'] = $registrant_limit;

  if ($booking['rep_type'] == REP_MONTHLY)
  {
    if ($month_type == REP_MONTH_ABSOLUTE)
    {
      $booking['month_absolute'] = $month_absolute;
    }
    else
    {
      $booking['month_relative'] = $month_relative;
    }
  }

  // Do the custom fields
  foreach ($custom_fields as $key => $value)
  {
    $booking[$key] = $value;
  }

  // Set the various statuses as appropriate
  // (Note: the statuses fields are the only ones that can differ by room)

  // Privacy status
  $booking['private'] = (bool) $isprivate;

  // If we are using booking approvals then we need to work out whether the
  // status of this booking is approved.   If the user is allowed to approve
  // bookings for this room, then the status will be approved, since they are
  // in effect immediately approving their own booking.  Otherwise the booking
  // will need to approved.
  $booking['awaiting_approval'] = ($approval_enabled && !is_book_admin($room_id));

  // Confirmation status
  $booking['tentative'] = ($confirmation_enabled && !$confirmed);

  $bookings[] = $booking;
}

$just_check = $is_ajax && !$commit;
$this_id = (isset($id)) ? $id : null;
$send_mail = ($no_mail) ? FALSE : need_to_send_mail();

// Wrap the editing process in a transaction, because if deleting the old booking should fail for
// some reason then we'll potentially be left with two overlapping bookings.  A deletion could fail
// if, for example, the database user hasn't been granted DELETE rights.

// Acquire mutex to lock out others trying to book the same slot(s).
if (!db()->mutex_lock(_tbl('entry')))
{
  fatal_error(get_vocab("failed_to_acquire"));
}

db()->begin();

$transaction_ok = true;

$result = mrbsMakeBookings($bookings, $this_id, $just_check, $skip, $original_room_id, $send_mail, $edit_type);

// If we weren't just checking and this was a successful booking and
// we were editing an existing booking, then delete the old booking
if (!$just_check && $result['valid_booking'] && isset($id))
{
  $transaction_ok = mrbsDelEntry($id, ($edit_type == "series"), 1);
}

if ($transaction_ok)
{
  db()->commit();
}
else
{
  db()->rollback();
  trigger_error('Edit failed.', E_USER_WARNING);
}

db()->mutex_unlock(_tbl('entry'));


// If this is an Ajax request, output the result and finish
if ($is_ajax)
{
  // Generate the new HTML
  if ($commit)
  {
    // Generate the new HTML
    require_once "functions_table.inc";

    switch ($view)
    {
      case 'day':
        $result['table_innerhtml'] = day_table_innerhtml($view, $year, $month, $day, $area, $room, $timetohighlight);
        break;
      case 'week':
        $result['table_innerhtml'] = week_table_innerhtml($view, $view_all, $year, $month, $day, $area, $room, $timetohighlight);
        break;
      default:
        throw new \Exception("Unsupported view '$view'");
        break;
    }
  }
  http_headers(array("Content-Type: application/json"));
  echo json_encode($result);
  exit;
}

// Everything was OK.   Go back to where we came from
if ($result['valid_booking'])
{
  location_header($returl);
}

else
{
  $context = array(
      'view'      => $view,
      'view_all'  => $view_all,
      'year'      => $year,
      'month'     => $month,
      'day'       => $day,
      'area'      => $area,
      'room'      => isset($room) ? $room : null
    );

  print_header($context);

  echo "<h2>" . get_vocab("sched_conflict") . "</h2>\n";
  if (!empty($result['violations']['errors']))
  {
    echo "<p>\n";
    echo get_vocab("rules_broken") . ":\n";
    echo "</p>\n";
    echo "<ul>\n";
    foreach ($result['violations']['errors'] as $rule)
    {
      echo "<li>$rule</li>\n";
    }
    echo "</ul>\n";
  }
  if (!empty($result['conflicts']))
  {
    echo "<p>\n";
    echo get_vocab("conflict").":\n";
    echo "</p>\n";
    echo "<ul>\n";
    foreach ($result['conflicts'] as $conflict)
    {
      echo "<li>$conflict</li>\n";
    }
    echo "</ul>\n";
  }
}

echo "<div id=\"submit_buttons\">\n";

// Back button
$form = new Form();

$form->setAttributes(array('method' => 'post',
                           'action' => multisite($returl)));

$submit = new ElementInputSubmit();
$submit->setAttribute('value', get_vocab('back'));
$form->addElement($submit);

$form->render();


// Skip and Book button (to book the entries that don't conflict)
// Only show this button if there were no policies broken and it's a series
if (empty($result['violations']['errors'])  &&
    isset($rep_type) && ($rep_type != REP_NONE))
{
  $form = new Form();

  $form->setAttributes(array('method' => 'post',
                             'action' => multisite(this_page())));

  // Put the booking data in as hidden inputs
  $skip = 1;  // Force a skip next time round
  // First the ordinary fields
  foreach ($form_vars as $var => $var_type)
  {
    if ($var_type == 'array')
    {
      // See the comment at the top of the page about array formats
      foreach ($$var as $value)
      {
        if (isset($value))
        {
          $form->addHiddenInput("${var}[]", $value);
        }
      }
    }
    elseif (isset($$var))
    {
      $form->addHiddenInput($var, $$var);
    }
  }
  // Then the custom fields
  foreach($fields as $field)
  {
    if (array_key_exists($field['name'], $custom_fields) && isset($custom_fields[$field['name']]))
    {
      $form->addHiddenInput(VAR_PREFIX . $field['name'], $custom_fields[$field['name']]);
    }
  }
  // Submit button
  $submit = new ElementInputSubmit();
  $submit->setAttributes(array('value' => get_vocab('skip_and_book'),
                               'title' => get_vocab('skip_and_book_note')));

  $form->addElement($submit);

  $form->render();
}

echo "</div>\n";

print_footer();
