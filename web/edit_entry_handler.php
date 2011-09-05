<?php
// $Id$

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_ical.inc";

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


// Get non-standard form variables
$formvars = array('create_by'         => 'string',
                  'name'              => 'string',
                  'rep_type'          => 'int',
                  'description'       => 'string',
                  'start_seconds'     => 'int',
                  'end_seconds'       => 'int',
                  'all_day'           => 'string',  // bool, actually
                  'type'              => 'string',
                  'rooms'             => 'array',
                  'original_room_id'  => 'int',
                  'ical_uid'          => 'string',
                  'ical_sequence'     => 'int',
                  'ical_recur_id'     => 'string',
                  'returl'            => 'string',
                  'rep_id'            => 'int',
                  'edit_type'         => 'string',
                  'id'                => 'int',
                  'rep_end_day'       => 'int',
                  'rep_end_month'     => 'int',
                  'rep_end_year'      => 'int',
                  'rep_id'            => 'int',
                  'rep_day'           => 'array',   // array of bools
                  'rep_num_weeks'     => 'int',
                  'skip'              => 'string',  // bool, actually
                  'private'           => 'string',  // bool, actually
                  'confirmed'         => 'string',
                  'start_day'         => 'int',
                  'start_month'       => 'int',
                  'start_year'        => 'int',
                  'end_day'           => 'int',
                  'end_month'         => 'int',
                  'end_year'          => 'int',
                  'back_button'       => 'string');
                  
foreach($formvars as $var => $var_type)
{
  $$var = get_form_var($var, $var_type);
}

// Get custom form variables
$custom_fields = array();

// Get the information about the fields in the entry table
$fields = sql_field_info($tbl_entry);
          
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
        $f_type = 'int';
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
      unset($custom_fields[$field['name']]);
    }
  }
}

// Get the start day/month/year and make them the current day/month/year
$day = $start_day;
$month = $start_month;
$year = $start_year;

// The id must be either an integer or NULL, so that subsequent code that tests whether
// isset($id) works.  (I suppose one could use !empty instead, but there's always the
// possibility that sites have allowed 0 in their auto-increment/serial columns.)
if (isset($id) && ($id == ''))
{
  unset($id);
}

// Truncate the name field to the maximum length as a precaution.
// Although the MAXLENGTH attribute is used in the <input> tag, this can
// sometimes be ignored by the browser, for example by Firefox when 
// autocompletion is used.  The user could also edit the HTML and remove
// the MAXLENGTH attribute.    Passing an oversize string to some
// databases (eg some versions of PostgreSQL) results in an SQL error,
// rather than silent truncation of the string.
$name = substr($name, 0, $maxlength['entry.name']);

// Make sure the area corresponds to the room that is being booked
if (!empty($rooms[0]))
{
  $area = get_area($rooms[0]);
  get_area_settings($area);  // Update the area settings
}
// and that $room is in $area
if (get_area($room) != $area)
{
  $room = get_default_room($area);
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
$returl_base   = explode('?', basename($returl));
if (empty($returl) || ($returl_base[0] == "edit_entry.php") || ($returl_base[0] == "edit_entry_handler.php")
                   || ($returl_base[0] == "search.php"))
{
  switch ($default_view)
  {
    case "month":
      $returl = "month.php";
      break;
    case "week":
      $returl = "week.php";
      break;
    default:
      $returl = "day.php";
  }
}
else
{
  $returl = $returl_base[0];
}

// BACK:  we didn't really want to be here - send them to the returl
if (isset($back_button))
{
  header("Location: $returl");
  exit();
}

// If we haven't been given a sensible date then get out of here and don't trey and make a booking
if (!isset($day) || !isset($month) || !isset($year) || !checkdate($month, $day, $year))
{
  header("Location: $returl");
  exit;
}

// Now construct the new query string
$returl .= "?year=$year&month=$month&day=$day";

// If the old sticky room is one of the rooms requested for booking, then don't change the sticky room.
// Otherwise change the sticky room to be one of the new rooms.
if (!in_array($room, $rooms))
{
  $room = $rooms[0];
} 
// Find the corresponding area
$area = mrbsGetRoomArea($room);
// Complete the query string
$returl .= "&area=$area&room=$room";

// Handle private booking
// Enforce config file settings if needed
if ($private_mandatory) 
{
  $isprivate = $private_default;
}
else
{
  $isprivate = ($private) ? TRUE : FALSE;
}

// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$is_admin = (authGetUserLevel($user) >= 2);

// If they're not an admin and multi-day bookings are not allowed, then
// set the end date to the start date
if (!$is_admin && $auth['only_admin_can_book_multiday'])
{
  $end_day = $day;
  $end_month = $month;
  $end_year = $year;
}

// Check to see whether this is a repeat booking and if so, whether the user
// is allowed to make/edit repeat bookings.   (The edit_entry form should
// prevent you ever getting here, but this check is here as a safeguard in 
// case someone has spoofed the HTML)
if (isset($rep_type) && ($rep_type != REP_NONE) &&
    !$is_admin &&
    !empty($auth['only_admin_can_book_repeat']))
{
  showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
  exit;
}


// Check that the user has permission to create/edit an entry for this room.
// Get the id of the room that we are creating/editing
if (isset($id))
{
  // Editing an existing booking: get the room_id from the database (you can't
  // get it from $rooms because they are the new rooms)
  $target_room = sql_query1("SELECT room_id FROM $tbl_entry WHERE id=$id LIMIT 1");
  if ($target_room < 0)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
}
else
{
  // New booking: get the room_id from the form
  if (!isset($rooms[0]))
  {
    // $rooms[0] should always be set, because you can only get here
    // from edit_entry.php, where it will be set.   If it's not set
    // then something's gone wrong - probably somebody trying to call
    // edit_entry_handler.php directly from the browser - so get out 
    // of here and go somewhere safe.
    header("Location: index.php");
    exit;
  }
  $target_room = $rooms[0];
}
if (!getWritable($create_by, $user, $target_room))
{
  showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
  exit;
}

if ($name == '')
{
  print_header($day, $month, $year, $area, isset($room) ? $room : "");
?>
       <h1><?php echo get_vocab('invalid_booking'); ?></h1>
       <p>
         <?php echo get_vocab('must_set_description'); ?>
       </p>
<?php
  // Print footer and exit
  print_footer(TRUE);
}       


if (($rep_type == REP_N_WEEKLY) && ($rep_num_weeks < 2))
{
  print_header($day, $month, $year, $area, isset($room) ? $room : "");
?>
       <h1><?php echo get_vocab('invalid_booking'); ?></h1>
       <p>
         <?php echo get_vocab('you_have_not_entered')." ".get_vocab("useful_n-weekly_value"); ?>
       </p>
<?php
  // Print footer and exit
  print_footer(TRUE);
}

if (count($is_mandatory_field))
{
  foreach ($is_mandatory_field as $field => $value)
  {
    $field = preg_replace('/^entry\./', '', $field);
    if ($value && ($custom_fields[$field] == ''))
    {
      print_header($day, $month, $year, $area, isset($room) ? $room : "");
?>
       <h1><?php echo get_vocab('invalid_booking'); ?></h1>
       <p>
         <?php echo get_vocab('missing_mandatory_field')." \"".
           get_loc_field_name($tbl_entry, $field)."\""; ?>
       </p>
<?php
      // Print footer and exit
      print_footer(TRUE);
    }
  }
}        


if ($enable_periods)
{
  $resolution = 60;
}

// When All Day is checked, $start_seconds and $end_seconds are disabled and so won't
// get passed through by the form.   We therefore need to set them.
if (isset($all_day) && ($all_day == "yes"))
{
  if ($enable_periods)
  {
    $start_seconds = 12 * 60 * 60;
    // This is actually the start of the last period, which is what the form would
    // have returned.   It will get corrected in a moment.
    $end_seconds = $start_seconds + ((count($periods) - 1) * 60);
  }
  else
  {
    $start_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $end_seconds = (($eveningends * 60) + $eveningends_minutes) *60;
    $end_seconds += $resolution;  // We want the end of the last slot, not the beginning
  }
}

// Now work out the start and times
$starttime = mktime(intval($start_seconds/3600), intval(($start_seconds%3600)/60), 0,
                    $month, $day, $year);
$endtime   = mktime(intval($end_seconds/3600), intval(($end_seconds%3600)/60), 0,
                    $end_month, $end_day, $end_year);
// If we're using periods then the endtime we've been returned by the form is actually
// the beginning of the last period in the booking (it's more intuitive for users this way)
// so we need to add on 60 seconds (1 period)
if ($enable_periods)
{
  $endtime = $endtime + 60;
}

// Round down the starttime and round up the endtime to the nearest slot boundaries
// (This step is probably unnecesary now that MRBS always returns times aligned
// on slot boundaries, but is left in for good measure).                  
$am7 = mktime($morningstarts, $morningstarts_minutes, 0,
              $month, $day, $year, is_dst($month, $day, $year, $morningstarts));
$starttime = round_t_down($starttime, $resolution, $am7);
$endtime = round_t_up($endtime, $resolution, $am7);
  
// If they asked for 0 minutes, and even after the rounding the slot length is still
// 0 minutes, push that up to 1 resolution unit.
if ($endtime == $starttime)
{
  $endtime += $resolution;
}

// Now get the duration, which will be needed for email notifications
// (We do this before we adjust for DST so that the user sees what they expect to see)
$duration = $endtime - $starttime;
$duration_seconds = $endtime - $starttime;  // Preserve the duration in seconds - we need it later
$date = getdate($starttime);
if ($enable_periods)
{
  $period = (($date['hours'] - 12) * 60) + $date['minutes'];
  toPeriodString($period, $duration, $dur_units, FALSE);
}
else
{
  toTimeString($duration, $dur_units, FALSE);
}
  
// Adjust the endtime for DST
$endtime += cross_dst( $starttime, $endtime );


if (isset($rep_type) && ($rep_type != REP_NONE) &&
    isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
  // Get the repeat entry settings
  $end_date = mktime(intval($start_seconds/3600), intval(($start_seconds%3600)/60), 0,
                     $rep_end_month, $rep_end_day, $rep_end_year);
}
else
{
  $rep_type = REP_NONE;
  $end_date = 0;  // to avoid an undefined variable notice
}

if (!isset($rep_day))
{
  $rep_day = array();
}

$rep_opt = "";

// Processing for weekly and n-weekly repeats
if (isset($rep_type) && (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY)))
{
  // If no repeat day has been set, then set a default repeat day
  // as the day of the week of the start of the period
  if (count($rep_day) == 0)
  {
    $rep_day[] = date('w', $starttime);
  }
  
  // Build string of weekdays to repeat on:
  for ($i = 0; $i < 7; $i++)
  {
    $rep_opt .= in_array($i, $rep_day) ? "1" : "0";  // $rep_opt is a string
  }
  
  // Make sure that the starttime and endtime coincide with a repeat day.  In
  // other words make sure that the first starttime and endtime define an actual
  // entry.   We need to do this because if we are going to construct an iCalendar
  // object, RFC 5545 demands that the start and end time are the first events of
  // a series.  ["The "DTSTART" property for a "VEVENT" specifies the inclusive
  // start of the event.  For recurring events, it also specifies the very first
  // instance in the recurrence set."]
  while (!$rep_opt[date('w', $starttime)])
  {
    $start = getdate($starttime);
    $end = getdate($endtime);
    $starttime = mktime($start['hours'], $start['minutes'], $start['seconds'],
                        $start['mon'], $start['mday'] + 1, $start['year']);
    $endtime = mktime($end['hours'], $end['minutes'], $end['seconds'],
                      $end['mon'], $end['mday'] + 1, $end['year']);
  }
}

// Expand a series into a list of start times:
if ($rep_type != REP_NONE)
{
  $reps = mrbsGetRepeatEntryList($starttime,
                                 isset($end_date) ? $end_date : 0,
                                 $rep_type, $rep_opt, $max_rep_entrys,
                                 $rep_num_weeks);
}

// When checking for overlaps, for Edit (not New), ignore this entry and series:
$repeat_id = 0;
if (isset($id))
{
  $ignore_id = $id;
  $repeat_id = sql_query1("SELECT repeat_id FROM $tbl_entry WHERE id=$id LIMIT 1");
  if ($repeat_id < 0)
  {
    $repeat_id = 0;
  }
}
else
{
  $ignore_id = 0;
}

// Acquire mutex to lock out others trying to book the same slot(s).
if (!sql_mutex_lock("$tbl_entry"))
{
  fatal_error(1, get_vocab("failed_to_acquire"));
}

// Validate the booking for (a) conflicting bookings and (b) conformance to rules
$valid_booking = TRUE;
$conflicts = "";          // Holds a list of all the conflicts (ideally this would be an array)
$rules_broken = array();  // Holds an array of the rules that have been broken
$skip_lists = array();    // Holds a 2D array of bookings to skip past.  Indexed
                          // by room id and start time
                          
// Check for any schedule conflicts in each room we're going to try and
// book in;  also check that the booking conforms to the policy
foreach ( $rooms as $room_id )
{
  $skip_lists[$room_id] = array();
  if ($rep_type != REP_NONE && !empty($reps))
  {
    if(count($reps) < $max_rep_entrys)
    {
      for ($i = 0; $i < count($reps); $i++)
      {
        // calculate diff each time and correct where events
        // cross DST
        $diff = $duration_seconds;
        $diff += cross_dst($reps[$i], $reps[$i] + $diff);

        $tmp = mrbsCheckFree($room_id,
                             $reps[$i],
                             $reps[$i] + $diff,
                             $ignore_id,
                             $repeat_id);

        if (!empty($tmp))
        {
          // If we've been told to skip past existing bookings, then add
          // this start time to the list of start times to skip past.
          // Otherwise it's an invalid booking
          if ($skip)
          {
            $skip_lists[$room_id][] = $reps[$i];
          }
          else
          {
            $valid_booking = FALSE;
          }
          // In both cases remember the conflict data.   (We don't at the
          // moment do anything with the data if we're skipping, but we might
          // in the future want to display a list of bookings we've skipped past)
          $conflicts .= $tmp;
        }
        // if we're not an admin for this room, check that the booking
        // conforms to the booking policy
        if (!auth_book_admin($user, $room_id))
        {
          $errors = mrbsCheckPolicy($reps[$i], $duration_seconds);
          if (count($errors) > 0)
          {
            $valid_booking = FALSE;
            $rules_broken = $rules_broken + $errors;  // array union
          }
        }
      } // for
    }
    else
    {
      $valid_booking = FALSE;
      $rules_broken[] = get_vocab("too_may_entrys");
    }
  }
  else
  {
    $tmp = mrbsCheckFree($room_id, $starttime, $endtime-1, $ignore_id, 0);
    if (!empty($tmp))
      {
        $valid_booking = FALSE;
        $conflicts .= $tmp;
      }
      // if we're not an admin for this room, check that the booking
      // conforms to the booking policy
      if (!auth_book_admin($user, $room_id))
      {
        $errors = mrbsCheckPolicy($starttime, $duration_seconds);
        if (count($errors) > 0)
        {
          $valid_booking = FALSE;
          $rules_broken = $rules_broken + $errors;  // Array union
        }
      }
  }

} // end foreach rooms

// If the rooms were free, go ahead an process the bookings
if ($valid_booking)
{
  foreach ($rooms as $room_id)
  { 
    // Set the various bits in the status field as appropriate
    $status = 0;
    // Privacy status
    if ($isprivate)
    {
      $status |= STATUS_PRIVATE;  // Set the private bit
    }
    // If we are using booking approvals then we need to work out whether the
    // status of this booking is approved.   If the user is allowed to approve
    // bookings for this room, then the status will be approved, since they are
    // in effect immediately approving their own booking.  Otherwise the booking
    // will need to approved.
    if ($approval_enabled && !auth_book_admin($user, $room_id))
    {
      $status |= STATUS_AWAITING_APPROVAL;
    }
    // Confirmation status
    if ($confirmation_enabled && !$confirmed)
    {
      $status |= STATUS_TENTATIVE;
    }
    
    // Assemble the data in an array
    $data = array();
   
    // We need to work out whether this is the original booking being modified,
    // because, if it is, we keep the ical_uid and increment the ical_sequence.
    // We consider this to be the original booking if there was an original
    // booking in the first place (in which case the original room id will be set) and
    //      (a) this is the same room as the original booking
    //   or (b) there is only one room in the new set of bookings, in which case
    //          what has happened is that the booking has been changed to be in
    //          a new room
    //   or (c) the new set of rooms does not include the original room, in which
    //          case we will make the arbitrary assumption that the original booking
    //          has been moved to the first room in the list and the bookings in the
    //          other rooms are clones and will be treated as new bookings.
    
    if (isset($original_room_id) && 
        (($original_room_id == $room_id) ||
         (count($rooms) == 1) ||
         (($rooms[0] == $room_id) && !in_array($original_room_id, $rooms))))
    {
      // This is an existing booking which has been changed.   Keep the
      // original ical_uid and increment the sequence number.
      $data['ical_uid'] = $ical_uid;
      $data['ical_sequence'] = $ical_sequence + 1;
    }
    else
    {
      // This is a new booking.   We generate a new ical_uid and start
      // the sequence at 0.
      $data['ical_uid'] = generate_global_uid($name);
      $data['ical_sequence'] = 0;
    }
    $data['start_time'] = $starttime;
    $data['end_time'] = $endtime;
    $data['room_id'] = $room_id;
    $data['create_by'] = $create_by;
    $data['name'] = $name;
    $data['type'] = $type;
    $data['description'] = $description;
    $data['status'] = $status;
    foreach ($custom_fields as $key => $value)
    {
      $data[$key] = $value;
    }
    $data['rep_type'] = $rep_type;
    if ($edit_type == "series")
    {
      $data['end_date'] = $end_date;
      $data['rep_opt'] = $rep_opt;
      $data['rep_num_weeks'] = (isset($rep_num_weeks)) ? $rep_num_weeks : 0;
    }
    else
    {
      if ($repeat_id > 0)
      {
        // Mark changed entry in a series with entry_type:
        $data['entry_type'] = ENTRY_RPT_CHANGED;
        // Keep the same recurrence id (this never changes once an entry has been made)
        $data['ical_recur_id'] = $ical_recur_id;
      }
      else
      {
        $data['entry_type'] = ENTRY_SINGLE;
      }
      $data['entry_type'] = ($repeat_id > 0) ? ENTRY_RPT_CHANGED : ENTRY_SINGLE;
      $data['repeat_id'] = $repeat_id;
    }
    // Add in the list of bookings to skip
    if (!empty($skip_lists) && !empty($skip_lists[$room_id]))
    {
      $data['skip_list'] = $skip_lists[$room_id];
    }
    // The following elements are needed for email notifications
    $data['duration'] = $duration;
    $data['dur_units'] = $dur_units;

    if ($edit_type == "series")
    {
      $booking = mrbsCreateRepeatingEntrys($data);
      $new_id = $booking['id'];
      $is_repeat_table = $booking['series'];
      $data['id'] = $new_id;  // Add in the id now we know it
    }
    else
    {
      // Create the entry:
      $new_id = mrbsCreateSingleEntry($data);
      $is_repeat_table = FALSE;
      $data['id'] = $new_id;  // Add in the id now we know it
    }
    
    // Send an email if neccessary, provided that the entry creation was successful
    if ($need_to_send_mail && !empty($new_id))
    {
      // Only send an email if (a) this is a changed entry and we have to send emails
      // on change or (b) it's a new entry and we have to send emails for new entries
      if ((isset($id) && $mail_settings['on_change']) || 
          (!isset($id) && $mail_settings['on_new']))
      {
        require_once "functions_mail.inc";
        // Get room name and area name for email notifications.
        // Would be better to avoid a database access just for that.
        // Ran only if we need details
        if ($mail_settings['details'])
        {
          $sql = "SELECT R.room_name, A.area_name
                    FROM $tbl_room R, $tbl_area A
                   WHERE R.id=$room_id AND R.area_id = A.id
                   LIMIT 1";
          $res = sql_query($sql);
          $row = sql_row_keyed($res, 0);
          $data['room_name'] = $row['room_name'];
          $data['area_name'] = $row['area_name'];
        }
        // If this is a modified entry then get the previous entry data
        // so that we can highlight the changes
        if (isset($id))
        {
          if ($edit_type == "series")
          {
            $mail_previous = mrbsGetBookingInfo($repeat_id, TRUE);
          }
          else
          {
            $mail_previous = mrbsGetBookingInfo($id, FALSE);
          }
        }
        else
        {
          $mail_previous = array();
        }
        // Send the email
        $result = notifyAdminOnBooking($data, $mail_previous, !isset($id), $is_repeat_table);
      }
    }   
  } // end foreach $rooms

  // Delete the original entry
  if (isset($id))
  {
    mrbsDelEntry($user, $id, ($edit_type == "series"), 1);
  }

  sql_mutex_unlock("$tbl_entry");
    
  // Now it's all done go back to the previous view
  header("Location: $returl");
  exit;
}

// The room was not free.
sql_mutex_unlock("$tbl_entry");

if (!$valid_booking)
{
  print_header($day, $month, $year, $area, isset($room) ? $room : "");
    
  echo "<h2>" . get_vocab("sched_conflict") . "</h2>\n";
  if (!empty($rules_broken))
  {
    echo "<p>\n";
    echo get_vocab("rules_broken") . ":\n";
    echo "</p>\n";
    echo "<ul>\n";
    // get rid of duplicate messages
    $rules_broken = array_unique($rules_broken);
    foreach ($rules_broken as $rule)
    {
      echo "<li>$rule</li>\n";
    }
    echo "</ul>\n";
  }
  if (!empty($conflicts))
  {
    echo "<p>\n";
    echo get_vocab("conflict").":\n";
    echo "</p>\n";
    echo "<ul>\n";
    echo $conflicts;
    echo "</ul>\n";
  }
}

echo "<div id=\"submit_buttons\">\n";

// Back button
echo "<form method=\"post\" action=\"" . htmlspecialchars($returl) . "\">\n";
echo "<fieldset><legend></legend>\n";
echo "<input type=\"submit\" value=\"" . get_vocab("back") . "\">\n";
echo "</fieldset>\n";
echo "</form>\n";


// Skip and Book button (to book the entries that don't conflict)
// Only show this button if there were no policies broken and it's a series
if (empty($rules_broken)  &&
    isset($rep_type) && ($rep_type != REP_NONE))
{
  echo "<form method=\"post\" action=\"" . htmlspecialchars(basename($PHP_SELF)) . "\">\n";
  echo "<fieldset><legend></legend>\n";
  // Put the booking data in as hidden inputs
  $skip = 1;  // Force a skip next time round
  // First the ordinary fields
  foreach ($formvars as $var => $var_type)
  {
    if ($var_type == 'array')
    {
      // See the comment at the top of the page about array formats
      foreach ($$var as $value)
      {
        echo "<input type=\"hidden\" name=\"${var}[]\" value=\"" . htmlspecialchars($value) . "\">\n";
      }
    }
    else
    {
      echo "<input type=\"hidden\" name=\"$var\" value=\"" . htmlspecialchars($$var) . "\">\n";
    }
  }
  // Then the custom fields
  foreach($fields as $field)
  {
    if (array_key_exists($field['name'], $custom_fields))
    {
      echo "<input type=\"hidden\"" .
                  "name=\"" . VAR_PREFIX . $field['name'] . "\"" .
                  "value=\"" . htmlspecialchars($custom_fields[$field['name']]) . "\">\n";
    }
  }
  // Submit button
  echo "<input type=\"submit\"" .
              "value=\"" . get_vocab("skip_and_book") . "\"" .
              "title=\"" . get_vocab("skip_and_book_note") . "\">\n";
  echo "</fieldset>\n";
  echo "</form>\n";
}

echo "</div>\n";

require_once "trailer.inc";
?>
