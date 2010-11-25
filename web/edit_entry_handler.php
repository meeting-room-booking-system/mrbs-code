<?php
// $Id$

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Get non-standard form variables
$create_by = get_form_var('create_by', 'string');
$name = get_form_var('name', 'string');
$rep_type = get_form_var('rep_type', 'int');
$description = get_form_var('description', 'string');
$start_seconds = get_form_var('start_seconds', 'int');
$end_seconds = get_form_var('end_seconds', 'int');
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
// Get the end day/month/year
$end_day = get_form_var('end_day', 'int');
$end_month = get_form_var('end_month', 'int');
$end_year = get_form_var('end_year', 'int');

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
    $var = VAR_PREFIX . $field['name'];
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

if (isset($all_day) && ($all_day == "yes"))
{
  if ($enable_periods)
  {
    $max_periods = count($periods);
    $starttime = mktime(12, 0, 0, $month, $day, $year);
    $endtime   = mktime(12, $max_periods, 0, $end_month, $end_day, $end_year);
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
                        $end_month, $end_day, $end_year,
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
  $starttime = mktime(0, 0, 0,
                      $month, $day, $year,
                      is_dst($month, $day, $year, intval($start_seconds/3600))) + $start_seconds;
  $endtime   = mktime(0, 0, 0,
                      $end_month, $end_day, $end_year,
                      is_dst($end_month, $end_day, $end_year, intval($end_seconds/3600))) + $end_seconds;
  // If we're using periods then the endtime we've been returned by the form is actually
  // the beginning of the last period in the booking (it's more intuitive for users this way)
  // so we need to add on 60 seconds (1 period)
  if ($enable_periods)
  {
    $endtime = $endtime + 60;
  }

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

  // Now adjust the duration in line with the adjustments to start and end time
  // so that the email notifications report the adjusted duration
  // (We do this before we adjust for DST so that the user sees what they expect to see)
  $duration = $endtime - $starttime;
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
}

if (isset($rep_type) && ($rep_type != REP_NONE) &&
    isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
  // Get the repeat entry settings
  $end_date = $start_seconds + mktime(0, 0, 0, $rep_end_month, $rep_end_day, $rep_end_year);
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
    $data['rep_type'] = $rep_type;
    if ($edit_type == "series")
    {
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
