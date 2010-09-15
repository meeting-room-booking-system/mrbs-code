<?php
// $Id$

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Get non-standard form variables
$create_by = get_form_var('create_by', 'string');
$name = get_form_var('name', 'string');
$rep_type = get_form_var('rep_type', 'int');
$description = get_form_var('description', 'string');
$hour = get_form_var('hour', 'int');
$ampm = get_form_var('ampm', 'string');
$minute = get_form_var('minute', 'int');
$period = get_form_var('period', 'int');
$duration = get_form_var('duration', 'string');
$dur_units = get_form_var('dur_units', 'string');
$all_day = get_form_var('all_day', 'string'); // bool, actually
$type = get_form_var('type', 'string');
$rooms = get_form_var('rooms', 'array');
$returl = get_form_var('returl', 'string');
$rep_id = get_form_var('rep_id', 'int');
$edit_type = get_form_var('edit_type', 'string');
$id = get_form_var('id', 'int');
$rep_end_day = get_form_var('rep_end_day', 'int');
$rep_end_month = get_form_var('rep_end_month', 'int');
$rep_end_year = get_form_var('rep_end_year', 'int');
$rep_id = get_form_var('rep_id', 'int');
$rep_day = get_form_var('rep_day', 'array'); // array of bools
$rep_num_weeks = get_form_var('rep_num_weeks', 'int');
$private = get_form_var('private', 'string'); // bool, actually
$confirmed = get_form_var('confirmed', 'string');
// Get the start day/month/year and make them the current day/month/year
$day = get_form_var('start_day', 'int');
$month = get_form_var('start_month', 'int');
$year = get_form_var('start_year', 'int');

// Get the information about the fields in the entry table
$fields = sql_field_info($tbl_entry);

// Get custom form variables
$custom_fields = array();
                        
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
    $var = "f_" . $field['name'];
    $custom_fields[$field['name']] = get_form_var($var, $f_type);
    if (($f_type == 'int') && ($custom_fields[$field['name']] === ''))
    {
      unset($custom_fields[$field['name']]);
    }
  }
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

// When $all_day is set, the hour and minute (or $period) fields are set to disabled, which means 
// that they are not passed through by the form.   We need to set them because they are needed below  
// in various places. (We could change the JavaScript in edit_entry.php to set the fields to readonly
// instead of disabled, but browsers do not generally grey out readonly fields and this would mean
// that it's not so obvious to the user what is happening.   Also doing it here is safer, in case 
// JavaScript is disabled and for some strange reason the user changes the values in the form to be
// before start of day)
if (isset($all_day) && ($all_day == "yes"))
{ 
  if ($enable_periods)
  {
    $period = 0;
  }
  else
  {
    $hour = $morningstarts;
    $minute = $morningstarts_minutes;
  }
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
  $isprivate = ((isset($private) && ($private == "yes")));
}

// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$is_admin = (authGetUserLevel($user) >= 2);

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
    fatal_error(0, sql_error());
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

// Support locales where ',' is used as the decimal point
$duration = preg_replace('/,/', '.', $duration);

if ($enable_periods)
{
  $resolution = 60;
  $hour = 12;
  $minute = $period;
}

if (isset($all_day) && ($all_day == "yes"))
{
  if ($enable_periods)
  {
    $max_periods = count($periods);
    $starttime = mktime(12, 0, 0, $month, $day, $year);
    $endtime   = mktime(12, $max_periods, 0, $month, $day, $year);
    // We need to set the duration and units because they are needed for email notifications
    $duration = $max_periods;
    $dur_units = "periods";
    // No need to convert into something sensible, because they already are
  }
  else
  {
    $starttime = mktime($morningstarts, $morningstarts_minutes, 0,
                        $month, $day, $year,
                        is_dst($month, $day, $year, $morningstarts));
    $endtime   = mktime($eveningends, $eveningends_minutes, 0,
                        $month, $day, $year,
                        is_dst($month, $day, $year, $eveningends));
    $endtime += $resolution;                // add on the duration (in seconds) of the last slot as
                                            // $eveningends and $eveningends_minutes specify the 
                                            // beginning of the last slot
    // We need to set the duration and units because they are needed for email notifications
    $duration = $endtime - $starttime;
    $dur_units = "seconds";
    // Convert them into something sensible (but don't translate because
    // that's done later)
    toTimeString($duration, $dur_units, FALSE);
  }
}
else
{
  // Get the duration in seconds
  $dur_seconds = $duration;
  $enable_periods ? fromPeriodString($period, $dur_seconds, $dur_units) : fromTimeString($dur_seconds, $dur_units);
  if (!$twentyfourhour_format)
  {
    if (isset($ampm) && ($ampm == "pm") && ($hour<12))
    {
      $hour += 12;
    }
    if (isset($ampm) && ($ampm == "am") && ($hour>11))
    {
      $hour -= 12;
    }
  }

  $starttime = mktime($hour, $minute, 0,
                      $month, $day, $year,
                      is_dst($month, $day, $year, $hour));
  $endtime   = mktime($hour, $minute, 0,
                      $month, $day, $year,
                      is_dst($month, $day, $year, $hour)) + $dur_seconds;

  // Round down the starttime and round up the endtime to the nearest slot boundaries                   
  $am7=mktime($morningstarts,$morningstarts_minutes,0,
              $month,$day,$year,is_dst($month,$day,$year,$morningstarts));
  $starttime = round_t_down($starttime, $resolution, $am7);
  $endtime = round_t_up($endtime, $resolution, $am7);
  
  // If they asked for 0 minutes, and even after the rounding the slot length is still
  // 0 minutes, push that up to 1 resolution unit.
  if ($endtime == $starttime)
  {
    $endtime += $resolution;
  }
 
  // Adjust the endtime for DST
  $endtime += cross_dst( $starttime, $endtime );
}

if (isset($rep_type) && ($rep_type != REP_NONE) &&
    isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
  // Get the repeat entry settings
  $end_date = mktime($hour, $minute, 0,
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

// If there's a weekly or n-weekly repeat and no repeat day has
// been set, then set a default repeat day as the day of
// the week of the start of the period
if (isset($rep_type) && (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY)))
{
  if (count($rep_day) == 0)
  {
    $start_day = date('w', $starttime);
    $rep_day[$start_day] = TRUE;
  }
}

// For weekly and n-weekly repeats, build string of weekdays to repeat on:
$rep_opt = "";
if (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY))
{
  for ($i = 0; $i < 7; $i++)
  {
    $rep_opt .= empty($rep_day[$i]) ? "0" : "1";
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
 
// Check for any schedule conflicts in each room we're going to try and
// book in;  also check that the booking conforms to the policy
foreach ( $rooms as $room_id )
{
  if ($rep_type != REP_NONE && !empty($reps))
  {
    if(count($reps) < $max_rep_entrys)
    {
      for ($i = 0; $i < count($reps); $i++)
      {
        // calculate diff each time and correct where events
        // cross DST
        $diff = $endtime - $starttime;
        $diff += cross_dst($reps[$i], $reps[$i] + $diff);

        $tmp = mrbsCheckFree($room_id,
                             $reps[$i],
                             $reps[$i] + $diff,
                             $ignore_id,
                             $repeat_id);

        if (!empty($tmp))
        {
          $valid_booking = FALSE;
          $conflicts .= $tmp;
        }
        // if we're not an admin for this room, check that the booking
        // conforms to the booking policy
        if (!auth_book_admin($user, $room_id))
        {
          $tmp = mrbsCheckPolicy($reps[$i]);
          if (!empty($tmp))
          {
            $valid_booking = FALSE;
            $rules_broken[] = $tmp;
          }
        }
      }
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
        $tmp = mrbsCheckPolicy($starttime);
        if (!empty($tmp))
        {
          $valid_booking = FALSE;
          $rules_broken[] = $tmp;
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
    $data['start_time'] = $starttime;
    $data['end_time'] = $endtime;
    $data['room_id'] = $room_id;
    $data['create_by'] = $create_by;
    $data['name'] = $name;
    $data['type'] = $type;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['custom_fields'] = $custom_fields;
    if ($edit_type == "series")
    {
      $data['rep_type'] = $rep_type;
      $data['end_date'] = $end_date;
      $data['rep_opt'] = $rep_opt;
      $data['rep_num_weeks'] = (isset($rep_num_weeks)) ? $rep_num_weeks : 0;
    }
    else
    {
      // Mark changed entry in a series with entry_type 2:
      $data['entry_type'] = ($repeat_id > 0) ? 2 : 0;
      $data['repeat_id'] = $repeat_id;
    }
    // The following elements are needed for email notifications
    $data['duration'] = $duration;
    $data['dur_units'] = $dur_units;

    if ($edit_type == "series")
    {
      $booking = mrbsCreateRepeatingEntrys($data);
      $new_id = $booking['id'];
      $is_repeat_table = $booking['series'];
    }
    else
    {
      // Create the entry:
      $new_id = mrbsCreateSingleEntry($data);
      $is_repeat_table = FALSE;
    }
    
    // Send a mail to the Administrator
    if ($need_to_send_mail)
    {
      // Send a mail only if this a new entry, or if this is an
      // edited entry but we have to send mail on every change,
      // and if the entry creation is successful
      if ( ( (isset($id) && $mail_settings['admin_all']) or !isset($id) ) &&
           (0 != $new_id) )
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
        // If this is a modified entry then call
        // getPreviousEntryData to prepare entry comparison.
        if (isset($id))
        {
          if ($edit_type == "series")
          {
            $mail_previous = getPreviousEntryData($repeat_id, TRUE);
          }
          else
          {
            $mail_previous = getPreviousEntryData($id, FALSE);
          }
        }
        // Send the email
        $result = notifyAdminOnBooking(!isset($id), $new_id, $is_repeat_table);
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

echo "<p>\n";
echo "<a href=\"" . htmlspecialchars($returl) . "\">" . get_vocab("returncal") . "</a>\n";
echo "</p>\n";

require_once "trailer.inc";
?>
