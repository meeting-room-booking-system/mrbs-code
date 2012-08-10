<?php
// $Id$

define('INTERVAL_DAY', 0);

/** mrbsCheckFree()
 * 
 * Check to see if the time period specified is free
 * 
 * $booking   - The booking in question - an associative array
 * $ignore    - An entry ID to ignore, 0 to ignore no entries
 * $repignore - A repeat ID to ignore everything in the series, 0 to ignore no series
 * 
 * Returns:
 *   nothing   - The area is free
 *   something - An error occured, the return value is an array of conflicts
 */
function mrbsCheckFree(&$booking, $ignore, $repignore)
{
  global $tbl_entry;
  global $enable_periods, $periods, $twentyfourhour_format;
  global $strftime_format;

  $room_id = $booking['room_id'];
  $user = getUserName();
  
  get_area_settings(get_area($room_id));

  // Select any meetings which overlap for this room:
  $sql = "SELECT id, name, start_time, create_by, status
            FROM $tbl_entry
           WHERE start_time < ${booking['end_time']}
             AND end_time > ${booking['start_time']}
             AND room_id = $room_id";

  if ($ignore > 0)
  {
    $sql .= " AND id <> $ignore";
  }
  if ($repignore > 0)
  {
    $sql .= " AND repeat_id <> $repignore";
  }
  $sql .= " ORDER BY start_time";

  $res = sql_query($sql);
  if (! $res)
  {
    // probably because the table hasn't been created properly
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }
  if (sql_count($res) == 0)
  {
    sql_free($res);
    return "";
  }
  // Get the room's area ID for linking to day, week, and month views:
  $area = mrbsGetRoomArea($room_id);

  // Build an listing all the conflicts:
  $err = array();
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $starts = getdate($row['start_time']);
    $param_ym = "area=$area&amp;year=$starts[year]&amp;month=".$starts['mon'];
    $param_ymd = $param_ym . "&amp;day=" . $starts['mday'];

    if ($enable_periods)
    {
      $p_num =$starts['minutes'];
      $startstr = utf8_strftime($strftime_format['date'] . ", ",
                                $row['start_time']) . $periods[$p_num];
    }
    else
    {
      $startstr = utf8_strftime(($twentyfourhour_format) ? $strftime_format['datetime24'] : $strftime_format['datetime12'],
                                $row['start_time']);
    }

    if (is_private_event($row['status'] & STATUS_PRIVATE) && $is_private_field['entry.name'] &&
         !getWritable($row['create_by'], $user, $room_id))
    {
       $row['name'] = get_vocab("private");
    }

    // enclose  the viewday etc. links in a span to make it easier for JavaScript to strip them out
    $err[] = "<a href=\"view_entry.php?id=".$row['id']."\">".$row['name']."</a>"
      . " (" . $startstr . ") "
      . "<span>(<a href=\"day.php?$param_ymd\">".get_vocab("viewday")."</a>"
      . " | <a href=\"week.php?room=$room_id&amp;$param_ymd\">".get_vocab("viewweek")."</a>"
      . " | <a href=\"month.php?room=$room_id&amp;$param_ym\">".get_vocab("viewmonth")."</a>)</span>";
  }

  return $err;
}


// Checks whether the proposed booking $booking would exceed the maximum number of
// bookings in the interval of type $interval_type (can be 'day', 'week', 'month' or
// 'year').   If $only_area is set then only the bookings in the same are considered.
// Returns NULL if OK, otherwise an error string.
function checkInterval(&$booking, $ignore, $repignore, $interval_type='day', $only_area=FALSE)
{
  global $max_per_interval_global, $max_per_interval_area;
  global $tbl_entry, $tbl_room;
  
  // Get the area id.   We only need to do this once as all bookings will be
  // in the same area.
  static $area_id = NULL;
  if (!isset($area_id))
  {
    $area_id = get_area($booking['room_id']);
  }
  
  // Get the location for indexing the $existing and $proposed arrays.   If it's a 
  // global check then $location = 0, otherwise use the area_id.
  $location = ($only_area) ? $area_id : 0;
  
  // Set up arrays recording the number of existing and proposed bookings for the interval,
  // indexed by the interval type and the Unix time at the start of that interval.  These 
  // are static variables because we test all the proposed bookings, which could be for
  // multiple rooms and/or for repeat bookings, before making the booking. 
  static $existing = array();
  static $proposed = array();
  
  // Loop through all the intervals in the proposed booking, counting how many bookings
  // already exist for that interval, and incrementing the number of proposed bookings
  // by one
  $start_date = getdate($booking['start_time']);
  $i = 1;
  switch ($interval_type)
  {
    case 'day':
      $interval_start = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'], $start_date['year']);
      break;
    case 'week':
      $skipback = day_of_MRBS_week($booking['start_time']);
      $interval_start = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'] - $skipback, $start_date['year']);
      break;
    case 'month':
      $interval_start = mktime(0, 0, 0, $start_date['mon'], 1, $start_date['year']);
      break;
    case 'year':
      $interval_start = mktime(0, 0, 0, 1, 1, $start_date['year']);
      break;
    case 'future':
      $interval_start = time();
      break;
  }
  while ($interval_start < $booking['end_time'])
  {
    switch ($interval_type)
    {
      case 'day':
        $interval_end = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'] + $i, $start_date['year']);
        break;
      case 'week':
        $interval_end = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'] + ($i * 7) - $skipback, $start_date['year']);
        break;
      case 'month':
        $interval_end = mktime(0, 0, 0, $start_date['mon'] + $i, 1, $start_date['year']);
        break;
      case 'year':
        $interval_end = mktime(0, 0, 0, 1, 1, $start_date['year'] + $i);
        break;
      case 'future':
        $interval_end = PHP_INT_MAX;
        break;
    }

    if (!isset($existing[$location][$interval_type][$interval_start]))
    {
      $sql = "SELECT COUNT(*)
                FROM $tbl_entry E, $tbl_room R
               WHERE E.start_time<$interval_end
                 AND E.end_time>$interval_start
                 AND E.create_by='" . sql_escape($booking['create_by']) . "'
                 AND E.room_id=R.id
                 AND R.disabled=0";
      if ($only_area)
      {
        $sql .= " AND R.area_id=$area_id";
      }
      if ($ignore > 0)
      {
        $sql .= " AND E.id <> $ignore";
      }
      if ($repignore > 0)
      {
        $sql .= " AND E.repeat_id <> $repignore";
      }
      
      $existing[$location][$interval_type][$interval_start] = sql_query1($sql);
      if ($existing[$location][$interval_type][$interval_start] < 0)
      {
        trigger_error(sql_error(), E_USER_WARNING);
        fatal_error(FALSE, get_vocab("fatal_db_error"));
      }
      $proposed[$location][$interval_type][$interval_start] = 1;
    }
    else
    {
      $proposed[$location][$interval_type][$interval_start]++;
    }

    $max_allowed = ($only_area) ? $max_per_interval_area[$interval_type] : $max_per_interval_global[$interval_type];
    if (($existing[$location][$interval_type][$interval_start] + $proposed[$location][$interval_type][$interval_start]) >
         $max_allowed)
    {
      if ($only_area)
      {
        return get_vocab("max_per_${interval_type}_area") . " $max_per_interval_area[$interval_type]";
      }
      else
      {
        return get_vocab("max_per_${interval_type}_global") . " $max_per_interval_global[$interval_type]";
      }
    }
      
    $interval_start = $interval_end;
    $i++;
  }
  
  return NULL;
}


/** mrbsCheckPolicy()
 * 
 * Check to see if a proposed booking conforms to any booking policies in force.,
 * Can be used both for editing/creating an entry as well as deleting an entry
 * (it's possible that in future some policies might apply to deletion and others
 * to creation)
 * 
 * $booking   - The booking in question - an associative array
 * $delete    - TRUE: We're intending to delete an entry
 *            - FALSE:  We're intending to create or edit an entry (the default)
 * 
 * Returns:
 *            - An array of human readable errors.   If no errors the array has
 *              length 0
 */
function mrbsCheckPolicy(&$booking, $ignore, $repignore, $delete=FALSE)
{
  global $periods, $enable_periods;
  global $min_book_ahead_enabled, $min_book_ahead_secs;
  global $max_book_ahead_enabled, $max_book_ahead_secs;
  global $max_booking_date_enabled, $max_booking_date;
  global $max_duration_enabled, $max_duration_secs, $max_duration_periods;
  global $max_per_interval_global_enabled, $max_per_interval_area_enabled;
  global $interval_types, $strftime_format;

  $errors = array();
  $secs_in_day = 60*60*24;
   
  // Because MRBS has no notion of where we are in the day if we're using periods,
  // we'll just assume that we're at the beginning of the day.
  $now = ($enable_periods) ? mktime(0, 0, 0) : time();
  // We'll also round $min_book_ahead_secs and $max_book_ahead_secs down to the nearest whole day
  
  // Check min_book_ahead
  if ($min_book_ahead_enabled)
  {
    if ($enable_periods)
    {
      $min_book_ahead_secs -=  $min_book_ahead_secs%$secs_in_day;
    }
    $min_book_ahead = $min_book_ahead_secs;
    if (($booking['start_time'] - $now) < $min_book_ahead)
    {
      toTimeString($min_book_ahead, $units);
      $errors[] = get_vocab("min_time_before") . " $min_book_ahead $units";
    }
  }
  
  // Check max_book_ahead (but not if we're deleting a booking because
  // nobody's going to mind if a booking beyond the max_book_ahead date is deleted)
  //
  // For the max_book_ahead case we check the endtime of the booking rather than the starttime.
  // This prevents somebody booking the slot they want by nbooking a starttime within the period
  // and then using a very long duration.
  if ($max_book_ahead_enabled && !$delete)
  {
    if ($enable_periods)
    {
      $max_book_ahead_secs -=  $max_book_ahead_secs%$secs_in_day;
    }
    $max_book_ahead = $max_book_ahead_secs;
    if (($booking['end_time'] - $now) > $max_book_ahead)
    {
      toTimeString($max_book_ahead, $units);
      $errors[] = get_vocab("max_time_before") . " $max_book_ahead $units";
    }
  }
  
  // Check max_booking_date
  if ($max_booking_date_enabled && !$delete)
  {
    list($y, $m, $d) = explode('-', $max_booking_date);
    if (isset($y) && isset($m) && isset($d) && checkdate($m, $d, $y))
    {
      if ($booking['end_time'] > mktime(0, 0, 0, $m, $d+1, $y))
      {
        $errors[] = get_vocab("latest_booking_date") . " " . strftime($strftime_format['date'], mktime(0, 0, 0, $m, $d, $y));
      }
    }
    else
    {
      trigger_error("Invalid max_book_ahead_date", E_USER_NOTICE);
    }
  }
  
  // Check max_duration (but not if we're deleting a booking)
  if ($max_duration_enabled && !$delete)
  {
    if ($enable_periods)
    {
      // Instead of calculating the difference between the start and end times and
      // comparing that with the maximum duration, we add the maximum duration to the
      // start time and compare that with the actual end time
      $start = getdate($booking['start_time']);
      $start['minutes'] += $max_duration_periods;
      $n_periods = count($periods);
      // If we've gone over into another day, adjust the minutes and days accordingly
      while ($start['minutes'] >= $n_periods)
      {
        $start['minutes'] -= $n_periods;
        $start['mday']++;
      }
      $max_endtime = mktime($start['hours'], $start['minutes'], $start['seconds'],
                            $start['mon'], $start['mday'], $start['year']);
      if ($booking['end_time'] > $max_endtime)
      {
        $errors[] = get_vocab("max_booking_duration") . " $max_duration_periods " .
                    (($max_duration_periods > 1) ? get_vocab("periods") : get_vocab("period_lc"));
      }
    }
    elseif ($booking['end_time'] - $booking['start_time'] > $max_duration_secs)
    {
      $max_duration = $max_duration_secs;
      toTimeString($max_duration, $units);
      $errors[] = get_vocab("max_booking_duration") . " $max_duration $units";
    }
  }
  
  // Check max number of bookings allowed per interval for this user for each of
  // the interval types, both globally and for the area
  foreach ($interval_types as $interval_type)
  {
    // globally
    if (!empty($max_per_interval_global_enabled[$interval_type]) && !$delete)
    {
      $tmp = checkInterval($booking, $ignore, $repignore, $interval_type, FALSE);
      if (isset($tmp))
      {
        $errors[] = $tmp;
      }
    }
    // for the area
    if (!empty($max_per_interval_area_enabled[$interval_type]) && !$delete)
    {
      $tmp = checkInterval($booking, $ignore, $repignore, $interval_type, TRUE);
      if (isset($tmp))
      {
        $errors[] = $tmp;
      }
    }
  }
  
  return $errors;
}

/** mrbsDelEntry()
 * 
 * Delete an entry, or optionally all entries.
 * 
 * $user   - Who's making the request
 * $id     - The entry to delete
 * $series - If set, delete the series, except user modified entries
 * $all    - If set, include user modified entries in the series delete
 *
 * Returns:
 *   0        - An error occured
 *   non-zero - The entry was deleted
 */
function mrbsDelEntry($user, $id, $series, $all)
{
  global $tbl_entry, $tbl_repeat;

  // Get the repeat_id and room_id for this entry
  $res = sql_query("SELECT repeat_id, room_id FROM $tbl_entry WHERE id=$id LIMIT 1");
  if (($res === FALSE) || (sql_count($res) <= 0))
  {
    return 0;
  }
  $row = sql_row_keyed($res, 0);
  $repeat_id = $row['repeat_id'];
  $room_id = $row['room_id'];
  
  $is_book_admin = auth_book_admin($user, $room_id);

  $sql = "SELECT start_time, end_time, create_by, id, entry_type FROM $tbl_entry WHERE ";
   
  if ($series)
  {
    $sql .= "repeat_id=$repeat_id";
  }
  else
  {
    $sql .= "id=$id";
  }

  $res = sql_query($sql);

  $removed = 0;

  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    if(!getWritable($row['create_by'], $user, $room_id))
    {
      continue;
    }
   
    if ($series && $row['entry_type'] == ENTRY_RPT_CHANGED && !$all)
    {
      continue;
    }
    
    // check that the booking policies allow us to delete this entry
    if (!$is_book_admin)
    {
      $tmp = mrbsCheckPolicy($row, 0, 0, TRUE);
      if (!empty($tmp))
      {
        continue;
      }
    }
   
    if (sql_command("DELETE FROM $tbl_entry WHERE id=" . $row['id']) > 0)
    {
      $removed++;
    }
  }

  if ($repeat_id > 0 &&
      sql_query1("SELECT count(*) FROM $tbl_entry WHERE repeat_id=$repeat_id") == 0)
  {
    sql_command("DELETE FROM $tbl_repeat WHERE id=$repeat_id");
  }

  return $removed > 0;
}

/** mrbsCreateEntry()
 * 
 * Create an entry in the database
 * 
 * $table         - The table in which to create the entry
 * $data          - An array containing the row data for the entry
 * 
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateEntry($table, $data)
{
  global $standard_fields, $db_tbl_prefix;
  
  $sql_col = array();
  $sql_val = array();
  $table_no_prefix = substr($table, strlen($db_tbl_prefix));  // strip the prefix off the table name
    
  $fields = sql_field_info($table);

  foreach ($fields as $field)
  {
    $key = $field['name'];
    if (array_key_exists($key, $data))
    {
      switch ($key)
      {
        // integers
        case 'start_time':
        case 'end_time':
        case 'entry_type':
        case 'repeat_id':
        case 'rep_type':
        case 'end_date':
        case 'room_id':
        case 'status':
        case 'ical_sequence':
          $sql_col[] = $key;
          $sql_val[] = $data[$key];
          break;
        
        // strings  
        case 'create_by':
        case 'name':
        case 'type':
        case 'description':
        case 'ical_uid':
        case 'ical_recur_id':
          $sql_col[] = $key;
          $sql_val[] = "'" . sql_escape($data[$key]) . "'";
          break;
      
        // special case - rep_opt
        case 'rep_opt':
          // pgsql doesn't like empty strings
          $sql_col[] = $key;
          $sql_val[] = (empty($data[$key])) ? "'0'" : "'" . sql_escape($data[$key]) . "'";
          break;
          
        // special case - rep_num_weeks
        case 'rep_num_weeks':
          if (!empty($data[$key]))
          {
            $sql_col[] = $key;
            $sql_val[] = $data[$key];
          }
          break;
        
        default:
          // custom fields
          if (!in_array($key, $standard_fields[$table_no_prefix]))
          {
            $sql_col[] = $key;
            
            // Depending on the nature of the custom field the treatment will vary
            switch ($field['nature'])
            {
              case 'integer':
                if (!isset($data[$key]) || ($data[$key] === ''))
                {
                  // Try and set it to NULL when we can because there will be cases when we
                  // want to distinguish between NULL and 0 - especially when the field
                  // is a genuine integer.
                  $value = ($field['is_nullable']) ? 'NULL' : 0;
                }
                else
                {
                  $value = $data[$key];
                }
                break;
              default:
                if (!isset($data[$key]))
                {
                  $value = '';
                }
                else
                {
                  $value = "'" . sql_escape($data[$key]) . "'";
                }
                break;
            } // switch ($field_natures[$key])
            
            $sql_val[] = $value;
          }
          // do nothing for fields that aren't custom or otherwise listed above
          break;
          
      } // switch ($key)
    } // if
  } // foreach
  
  // Note that we don't have to escape or quote the fieldname
  // thanks to the restriction on custom field names
  $sql = "INSERT INTO $table (" . implode(', ',$sql_col) . ") VALUES (" . implode(', ',$sql_val) . ")";

  if (sql_command($sql) < 0)
  {
    // probably because the table hasn't been created properly
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }

  return sql_insert_id($table, "id");
}

/** mrbsCreateSingleEntry()
 * 
 * Create a single (non-repeating) entry in the database
 * 
 * $data      - An array containing the entry details
 * 
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateSingleEntry($data)
{
  global $tbl_entry;
  
  // make sure that any entry is of a positive duration
  // this is to trap potential negative duration created when DST comes
  // into effect
  if ($data['end_time'] > $data['start_time'])
  {
    // If we're about to create an individual member of a series for the first time
    // then give it a recurrence-id equivalent to the start time.  It should always
    // keep this value, even if the start time is subsequently changed.
    if ($data['entry_type'] == ENTRY_RPT_ORIGINAL)
    {
      $data['ical_recur_id'] = gmdate(RFC5545_FORMAT . '\Z', $data['start_time']);
    }
    $result = mrbsCreateEntry($tbl_entry, $data);
    return $result;
  }
  else
  {
    return 0;
  }
}

/** mrbsCreateRepeatEntry()
 * 
 * Creates a repeat entry in the data base
 * 
 * $data      - An array containing the entry details
 *
 * Returns:
 *   0        - An error occured while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateRepeatEntry($data)
{
  global $tbl_repeat;
  
  $result = mrbsCreateEntry($tbl_repeat, $data);
  return $result;
}


/** same_day_next_month()
* Find the same day of the week in next month, same week number.
*
* Return the number of days to step forward for a "monthly repeat,
* corresponding day" serie - same week number and day of week next month.
* This function always returns either 28 or 35.
* For dates in the 5th week of a month, the resulting day will be in the 4th
* week of the next month if no 5th week corresponding day exist.
* :TODO: thierry_bo 030510: repeat 5th week entries only if 5th week exist.
* If we want a 5th week repeat type, only 5th weeks have to be booked. We need
* also a new "monthly repeat, corresponding day, last week of the month" type.
*
* @param    integer     $time           timestamp of the day from which we want to find
*                                       the same day of the week in next month, same
*                                       week number
* @return   integer     $days_jump      number of days to step forward to find the next occurence (28 or 35)
* @var      integer     $days_in_month  number of days in month
* @var      integer     $day            day of the month (01 to 31)
* @var      integer     $weeknumber     week number for each occurence ($time)
* @var      boolean     $temp1          first step to compute $days_jump
* @var      integer     $next_month     intermediate next month number (1 to 12)
* @global   integer     $_initial_weeknumber    used only for 5th weeks repeat type
 */
function same_day_next_month($time)
{
  global $_initial_weeknumber;

  $days_in_month = date("t", $time);
  $day = date("d", $time);
  $weeknumber = (int)(($day - 1) / 7) + 1;
  $temp1 = ($day + 7 * (5 - $weeknumber) <= $days_in_month);

  // keep month number > 12 for the test purpose in line beginning with "days_jump = 28 +..."
  $next_month = date("n", mktime(11, 0 ,0, date("n", $time), $day +35, date("Y", $time))) + (date("n", mktime(11, 0 ,0, date("n", $time), $day +35, date("Y", $time))) < date("n", $time)) * 12;

  // prevent 2 months jumps if $time is in 5th week
  $days_jump = 28 + (($temp1 && !($next_month - date("n", $time) - 1)) * 7);

  /* if initial week number is 5 and the new occurence month number ($time + $days_jump)
   * is not changed if we add 7 days, then we can add 7 days to $days_jump to come
   * back to the 5th week (yuh!) */
  $days_jump += 7 * (($_initial_weeknumber == 5) && (date("n", mktime(11, 0 ,0, date("n", $time), $day + $days_jump, date("Y", $time))) == date("n", mktime(11, 0 ,0, date("n", $time), $day + $days_jump + 7, date("Y", $time)))));

  return $days_jump;
}

/** mrbsGetRepeatEntryList
 * 
 * Returns a list of the repeating entrys
 * 
 * $time     - The start time
 * $enddate  - When the repeat ends
 * $rep_type - What type of repeat is it
 * $rep_opt  - The repeat entrys (if the repeat type is weekly or n-weekly
 *             then at least one repeat day must be set)
 * $max_ittr - After going through this many entrys assume an error has occured
 * $_initial_weeknumber - Save initial week number for use in 'monthly repeat same week number' case
 * 
 * Returns:
 *   empty     - The entry does not repeat
 *   an array  - This is a list of start times of each of the repeat entrys
 */
function mrbsGetRepeatEntryList($time, $enddate, $rep_type, $rep_opt, $rep_num_weeks,
                                $max_ittr)
{
  $sec   = date("s", $time);
  $min   = date("i", $time);
  $hour  = date("G", $time);
  $day   = date("d", $time);
  $month = date("m", $time);
  $year  = date("Y", $time);

  global $_initial_weeknumber;
  $_initial_weeknumber = (int)(($day - 1) / 7) + 1;
  $week_num = 0;
  $start_day = date('w', $time);
  $start_dom = $day;  // the starting day of the month

  $entrys = array();
  for ($i = 0; $i < $max_ittr; $i++)
  {
    $time = mktime($hour, $min, $sec, $month, $day, $year);
    if ($time > $enddate)
    {
      break;
    }

    $entrys[$i] = $time;

    switch($rep_type)
    {
      case REP_DAILY:
        $day++;
        break;
      
      case REP_WEEKLY:
        $rep_num_weeks = 1;
      case REP_N_WEEKLY:
        $j = $cur_day = date("w", $entrys[$i]);
        // Skip over days of the week which are not enabled:
        do
        {
          $day++;
          $j = ($j + 1) % 7;
          // If we've got back to the beginning of the week, then skip
          // over the weeks we've got to miss out (eg miss out one week
          // if we're repeating every two weeks)
          if ($j == $start_day)
          {
            $day += 7 * ($rep_num_weeks - 1);
          }
        }
        while (($j != $cur_day) && !$rep_opt[$j]);
        break;
      
      case REP_MONTHLY:
      case REP_YEARLY:
        if ($rep_type == REP_MONTHLY)
        {
          $month++;
        }
        else
        {
          $year++;
        }
        // Make sure the month and year are valid, so
        // that we can check the days with checkdate()
        if ($month > 12)
        {
          $year++;
          $month -= 12;
        }
        // Get the day of the month back to where it should be (in case we
        // decremented it to make it a valid date last time round)
        $day = $start_dom;
        // Make the date valid if day is more than number of days in month:
        while (!checkdate($month, $day, $year) && ($day > 1))
        {
          $day--;
        }
        break;
   
      case REP_MONTHLY_SAMEDAY:
        $day += same_day_next_month($time);
        break;

      // Unknown repeat option
      default:
        return;
    }
  }
  
  // For weekly and n-weekly repeats, the first entry that we've
  // got in the array is not valid if the day of the week of the
  // start of the period is not one of the repeat days.  (We are
  // allowed to assume that at least one repeat day is set in this
  // function)
  if (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY))
  {
    if (!$rep_opt[$start_day])
    {
      array_shift($entrys);  // remove the first entry
    }
  }

  return $entrys;
}

/** mrbsCreateRepeatingEntrys()
 * 
 * Creates a repeat entry in the data base + all the repeating entrys
 * 
 * $data      - An array containing the entry details
 * 
 * Returns:
 *   an array
 *   ['id']      - 0 if an error occurred or if no bookings could be
 *                 made, otherwise an id
 *   ['series']  - boolean: TRUE if the id refers to the repeat table
 *                          FALSE if the id refers to the entry table
 *
 */
function mrbsCreateRepeatingEntrys($data)
{
  global $max_rep_entrys;
  
  $result = array('id' => 0, 'series' => FALSE);

  if (!isset($data['skip_list']))
  {
    $data['skip_list'] = array();
  }

  $reps = mrbsGetRepeatEntryList($data['start_time'], $data['end_date'],
                                 $data['rep_type'], $data['rep_opt'],
                                 $data['rep_num_weeks'], $max_rep_entrys);

  // Don't make any bookings if we've been asked to book up
  // more entries than we are allowed in a single repeat.
  if (count($reps) > $max_rep_entrys)
  {
    $result['id'] = 0;
    return $result;;
  }
  
  // If $reps is empty, then this is a single booking, so treat
  // it differently
  if (empty($reps))
  {
    $data['entry_type'] = ENTRY_SINGLE;
    $data['repeat_id'] = 0;
    $id = mrbsCreateSingleEntry($data);
    $result['id'] = $id;
    $result['series'] = FALSE;
    return $result;
  }
  
  // This is a repeat booking.   If we've got to skip past all
  // the entries, then don't make a booking!
  if (count($reps) == count($data['skip_list']))
  {
    $result['id'] = 0;
    return $result;;
  }
  
  // Maybe one should also consider adjusting the start_time for
  // the repeat if the first (or more) entries of the series are
  // to be skipped.    However I haven't done so here and it gives the
  // maybe slightly strange result that the start date of the series won't
  // have an entry on that date.   But then this is no different from 
  // the way MRBS works at present if you create a series and then
  // delete the first entry.
  //
  // Note also that RFC 5545 allows this behaviour in 3.8.5.1:
  //   'The "EXDATE" property can be used to exclude the value specified
  //    in "DTSTART".  However, in such cases, the original "DTSTART" date
  //    MUST still be maintained by the calendaring and scheduling system
  //    because the original "DTSTART" value has inherent usage
  //    dependencies by other properties such as the "RECURRENCE-ID".'
  
  $id = mrbsCreateRepeatEntry($data);
    
  if ($id)
  {
    $data['entry_type'] = ENTRY_RPT_ORIGINAL;
    $data['repeat_id'] = $id;
    $starttime = $data['start_time'];
    $endtime = $data['end_time'];
    for ($i = 0; $i < count($reps); $i++)
    {
      // Provided this isn't one of the entries to skip, go ahead
      // and make the booking
      if (!in_array($reps[$i], $data['skip_list']))
      {
        // calculate diff each time and correct where events
        // cross DST
        $diff = $endtime - $starttime;
        $diff += cross_dst($reps[$i], $reps[$i] + $diff);  
        $data['start_time'] = $reps[$i];
        $data['end_time'] = $reps[$i] + $diff;

        $ent_id = mrbsCreateSingleEntry($data);
      }
    }
  }
  $result['id'] = $id;
  $result['series'] = TRUE;
  return $result;
}

// Update the time of last reminding.
// If the entry is part of a repeating series, then also increment
// the last reminder time in the repeat table and all the individual 
// entries.  (Although strictly speaking the reminder time should apply
// either to a series or an individual entry, we update everything to
// prevent users bombarding admins with reminder emails)
//
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function mrbsUpdateLastReminded($id, $series)
{
  global $tbl_entry, $tbl_repeat;
  
  $now = time();
  if ($series)
  {
    $sql = "UPDATE $tbl_repeat
               SET reminded=$now,
                   ical_sequence=ical_sequence+1
             WHERE id=$id";
    if (sql_command($sql) >= 0)
    {
      $sql = "UPDATE $tbl_entry
                 SET reminded=$now,
                     ical_sequence=ical_sequence+1
               WHERE repeat_id=$id";
      return sql_command($sql);
    }
  }
  else
  {
    $sql = "UPDATE $tbl_entry
               SET reminded=$now,
                   ical_sequence=ical_sequence+1
             WHERE id=$id";
    if (sql_command($sql) > 0)
    {
      $repeat_id = sql_query1("SELECT repeat_id FROM $tbl_entry WHERE id=$id LIMIT 1");
      if ($repeat_id >= 0)
      {
        $sql = "UPDATE $tbl_repeat
                   SET reminded=$now,
                       ical_sequence=ical_sequence+1
                 WHERE id=$repeat_id";
        return sql_command($sql);
      }
    }
  }
  return -1;
}

// Update the entry/repeat tables with details about the last More Info
// request (time, user, email text)
//
// If $series is TRUE then $id is the id of an entry in the repeat table
// which is updated.   Otherwise $id is the id of an entry in the
// entry table, which is updated.
//
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function mrbsUpdateMoreInfo($id, $series, $user, $note)
{
  global $tbl_entry, $tbl_repeat;
  
  $table = ($series) ? $tbl_repeat : $tbl_entry;
  $now = time();
  $sql = "UPDATE $table SET";
  $sql .= " info_time=$now";
  $sql .= ", info_user='" . sql_escape($user) . "'";
  $sql .= ", info_text='" . sql_escape($note) . "'";
  $sql .= " WHERE id=$id";
  return sql_command($sql);
}

// mrbsApproveEntry($id, $series)
//
// Approve an entry with id $id.   If series is set to TRUE
// then the id is the id in the repeat table and we must approve
// all the individual entries.
// We also update the ical_sequence number so that any emails that
// are generated will be treated by calendar clients as referring 
// to the same meeting, rather than a new meeting.
// Returns FALSE on failure, TRUE on success
function mrbsApproveEntry($id, $series)
{
  global $tbl_entry, $tbl_repeat;
  
  if ($series)
  {
    // First update the repeat table
    $sql = "UPDATE $tbl_repeat 
               SET status=status&(~" . STATUS_AWAITING_APPROVAL . "),
                   ical_sequence=ical_sequence+1
             WHERE id=$id";
    if (sql_command($sql) > 0)  // We expect there to be one row affected
    {
      // Then update the entry table
      $sql = "UPDATE $tbl_entry 
                 SET status=status&(~" . STATUS_AWAITING_APPROVAL . "),
                     ical_sequence=ical_sequence+1
               WHERE repeat_id=$id";
      // It's possible that there will be no rows affected if all the
      // entries have previously been individually approved
      return (sql_command($sql) >= 0);
    }
    else
    {
      return FALSE;
    }
  }
  else
  {
    $sql = "UPDATE $tbl_entry 
               SET status=status&(~" . STATUS_AWAITING_APPROVAL . "),
                   ical_sequence=ical_sequence+1
             WHERE id=$id";
    return (sql_command($sql) > 0);  // We expect there to be one row affected
  }
}


// mrbsGetBookingInfo($id, $series)
//
// Gets all the details for a booking with $id, which is in the
// repeat table if $series is set, otherwise in the entry table.

// Returns the results in an array with keys the same as the table
// field names.  In the event of an error stops with a fatal error,
// unless $silent is TRUE, when it returns FALSE.
function mrbsGetBookingInfo($id, $series, $silent=FALSE)
{
  global $tbl_entry, $tbl_repeat, $tbl_room, $tbl_area;
  global $approval_enabled, $enable_periods;

  // Check that we've got an id
  if (!isset($id))
  {
    trigger_error("id not set", E_USER_WARNING);
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(TRUE, ($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id")));
    }
  }

  $table = ($series) ? $tbl_repeat : $tbl_entry;
  $table_fields = sql_field_info($table);

  // Build an array of the field names in the repeat table so that
  // we'll be able to do some sanity checking later
  $repeat_fields = sql_field_info($tbl_repeat);
  $rep_fields = array();
  foreach ($repeat_fields as $field)
  {
    $rep_fields[$field['name']] = 1;
  }

  $terms = array("M.room_name",
                 "M.room_admin_email",
                 "M.area_id",
                 "A.area_name",
                 "A.area_admin_email",
                 "M.disabled AS room_disabled",
                 "A.disabled AS area_disabled",
                 "(end_time - start_time) AS duration");
                 
  foreach ($table_fields as $field)
  {
    switch ($field['name'])
    {
      // these fields only exist in the entry table
      case 'entry_type':
      case 'repeat_id':
      case 'ical_recur_id':
        array_push($terms, $field['name']);
        break;

      case 'timestamp':
        array_push($terms, sql_syntax_timestamp_to_unix("timestamp") . "AS last_updated");
        break;

      case 'info_time':
      case 'info_user':
      case 'info_text':
        if ($series)
        {
          array_push($terms, $field['name'] . " AS repeat_".$field['name']);
        }
        else
        {
          array_push($terms, $field['name'] . " AS entry_".$field['name']);
        }
        break;

      default:
        // These are (a) all the standard fields which are common to the entry
        // and repeat tables and (b) all the custom fields, which should be
        // common to the two tables (we will do a check to make sure)
        if (!$series && !array_key_exists($field['name'], $rep_fields))
        {
          // If this is the entry table then check that the custom field also
          // exists in the rpeat table
          fatal_error(0, "Custom fields problem, '".$field['name']."' exists in entry table but not in repeat table");
        }
        
        array_push($terms, "T." . $field['name']);
        break;
    }
  }
  
  $sql = "SELECT " . implode(", ",$terms)."
            FROM $table T, $tbl_room M, $tbl_area A
           WHERE T.room_id = M.id
             AND M.area_id = A.id
             AND T.id=$id";

  $res = sql_query($sql);
  if (! $res)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(FALSE, get_vocab("fatal_db_error"));
    }
  }

  if (sql_count($res) < 1)
  {
    // It's quite possible that the id will have disappeared, eg if somebody
    // else has deleted or edited the entry
    trigger_error("No rows found matching id=$id", E_USER_NOTICE);
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(TRUE, ($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id")));
    }
  }

  $row = sql_row_keyed($res, 0);
  sql_free($res);
  
  // Now get the duration.
  // Don't translate the units at this stage.   We'll translate them later.
  $d = get_duration($row['start_time'], $row['end_time'], $enable_periods, FALSE);
  $row['duration'] = $d['duration'];
  $row['dur_units'] = $d['dur_units'];
    
  // Get some extra information
  if ($series)
  {
    $row['entry_info_time'] = '';
    $row['entry_info_user'] = '';
    $row['entry_info_text'] = '';
  }
  else
  {
    // Get the repeat information
    if (empty($row['repeat_id']))
    {
      $row['rep_type'] = REP_NONE;   // just as a precaution
      $row['repeat_info_time'] = '';
      $row['repeat_info_user'] = '';
      $row['repeat_info_text'] = '';
    }
    else
    {
      $res = sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks,
                        info_time AS repeat_info_time, info_user AS repeat_info_user, info_text AS repeat_info_text
                        FROM $tbl_repeat WHERE id=${row['repeat_id']} LIMIT 1");
      if (!$res || (!$extra_row = sql_row_keyed($res, 0)))
      {
        if (!$res)
        {
          trigger_error(sql_error(), E_USER_WARNING);
        }
        if ($silent)
        {
          return FALSE;
        }
        else
        {
          fatal_error(TRUE, get_vocab("invalid_series_id"));
        }
      }
      $row['rep_type']         = $extra_row['rep_type'];
      $row['end_date']         = $extra_row['end_date'];
      $row['rep_opt']          = $extra_row['rep_opt'];
      $row['rep_num_weeks']    = $extra_row['rep_num_weeks'];
      $row['repeat_info_time'] = $extra_row['repeat_info_time'];
      $row['repeat_info_user'] = $extra_row['repeat_info_user'];
      $row['repeat_info_text'] = $extra_row['repeat_info_text'];
      sql_free($res);
    }
  }
  
  return $row;
}

function mrbsGetRoomArea($id)
{
  global $tbl_room;

  $id = sql_query1("SELECT area_id FROM $tbl_room WHERE id=$id LIMIT 1");
  if ($id <= 0)
  {
    $id = 0;
  }

  return $id;
}


// Adds an area, returning the new id, or FALSE on failure with the
// error in $error
function mrbsAddArea($name, &$error)
{
  global $maxlength, $tbl_area, $area_defaults, $boolean_fields;
  
  // First of all check that we've got a name
  if (!isset($name) || ($name === ''))
  {
    $error = "empty_name";
    return FALSE;
  }
  
  // Truncate the name field to the maximum length as a precaution.
  $name = substr($name, 0, $maxlength['area.area_name']);
  $area_name_q = sql_escape($name);
  // Acquire a mutex to lock out others who might be editing the area
  if (!sql_mutex_lock("$tbl_area"))
  {
    fatal_error(TRUE, get_vocab("failed_to_acquire"));
  }
  // Check that the area name is unique
  if (sql_query1("SELECT COUNT(*) FROM $tbl_area WHERE area_name='$area_name_q' LIMIT 1") > 0)
  {
    sql_mutex_unlock("$tbl_area");
    $error = "invalid_area_name";
    return FALSE;
  }
  // If so, insert the area into the database.   We insert the area name that
  // we have been given, together with the default values for the per-area settings

  // Build arrays of data to be inserted into the table
  $sql_col = array();
  $sql_val = array();
  // Get the information about the fields in the room table
  $fields = sql_field_info($tbl_area);
  // Loop through the fields and build up the arrays
  foreach ($fields as $field)
  {
    $key = $field['name'];
    switch ($key)
    {
    case 'area_name':
      $sql_col[] = $key;
      $sql_val[] = "'$area_name_q'";
      break;
    default:
      if (array_key_exists($key, $area_defaults))
      {
        $sql_col[] = $key;
        if (in_array($key, $boolean_fields['area']))
        {
          $sql_val[] = ($area_defaults[$key]) ? 1 : 0;
        }
        elseif ($field['nature'] == 'integer')
        {
          $sql_val[] = $area_defaults[$key];
        }
        else
        {
          $sql_val[] = "'" . sql_escape($area_defaults[$key]) . "'";
        }
      }
      break;
    }
  }
  $sql = "INSERT INTO $tbl_area (" . implode(', ',$sql_col) . ") VALUES (" . implode(', ',$sql_val) . ")";
  if (sql_command($sql) < 0)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }
  $area = sql_insert_id($tbl_area, 'id');

  // Release the mutex
  sql_mutex_unlock("$tbl_area");
  return $area;
}


// Adds a room, returning the new id, or FALSE on failute with the
// error in $error
function mrbsAddRoom($name, $area, &$error, $description='', $capacity='')
{
  global $maxlength, $tbl_room;
  
  // First of all check that we've got a name
  if (!isset($name) || ($name === ''))
  {
    $error = "empty_name";
    return FALSE;
  }
  
  // Truncate the name and description fields to the maximum length as a precaution.
  $name = substr($name, 0, $maxlength['room.room_name']);
  $description = substr($description, 0, $maxlength['room.description']);
  // Add SQL escaping
  $room_name_q = sql_escape($name);
  $description_q = sql_escape($description);
  if (empty($capacity))
  {
    $capacity = 0;
  }
  // Acquire a mutex to lock out others who might be editing rooms
  if (!sql_mutex_lock("$tbl_room"))
  {
    fatal_error(TRUE, get_vocab("failed_to_acquire"));
  }
  // Check that the room name is unique within the area
  if (sql_query1("SELECT COUNT(*) FROM $tbl_room WHERE room_name='$room_name_q' AND area_id=$area LIMIT 1") > 0)
  {
    sql_mutex_unlock("$tbl_room");
    $error = "invalid_room_name";
    return FALSE;
  }
  // If so, insert the room into the database
  $sql = "INSERT INTO $tbl_room (room_name, sort_key, area_id, description, capacity)
          VALUES ('$room_name_q', '$room_name_q', $area, '$description_q',$capacity)";
  if (sql_command($sql) < 0)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }
  $room = sql_insert_id($tbl_room, 'id');
  // Release the mutex
  sql_mutex_unlock("$tbl_room");
  return $room;
}


// Makes bookings
//    $bookings     an array of bookings
//    $id           the id of the current booking when editing an existing entry
function mrbsMakeBookings($bookings, $id=NULL, $just_check=FALSE, $skip=FALSE, $original_room_id=NULL, $send_mail=FALSE, $edit_type='')
{
  global $max_rep_entrys, $enable_periods, $resolution, $mail_settings;
  global $tbl_entry, $tbl_room, $tbl_area;
  
  $user = getUserName();
  // All the data, except for the status and room id, will be common
  // across the bookings
  $common = $bookings[0];
  // Work out the duration in seconds, but adjust it for DST changes so that
  // the user will still see, for example, "24 hours" when a booking goes from
  // 1200 one day to 1200 the next, crosing a DST boundary.
  $duration_seconds = $common['end_time'] - $common['start_time'];
  $duration_seconds -= cross_dst($common['start_time'], $common['end_time']);
  // Now get the duration, which will be needed for email notifications
  // (We do this before we adjust for DST so that the user sees what they expect to see)
  $duration = $duration_seconds;
  $date = getdate($common['start_time']);
  if ($enable_periods)
  {
    $period = (($date['hours'] - 12) * 60) + $date['minutes'];
    toPeriodString($period, $duration, $dur_units, FALSE);
  }
  else
  {
    toTimeString($duration, $dur_units, FALSE);
  }
  // Expand a series into a list of start times:
  if ($bookings[0]['rep_type'] != REP_NONE)
  {
    $reps = mrbsGetRepeatEntryList($common['start_time'],
                                   isset($common['end_date']) ? $common['end_date'] : 0,
                                   $common['rep_type'],
                                   $common['rep_opt'],
                                   $common['rep_num_weeks'],
                                   $max_rep_entrys);
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
  $conflicts = array();     // Holds a list of all the conflicts
  $rules_broken = array();  // Holds an array of the rules that have been broken
  $skip_lists = array();    // Holds a 2D array of bookings to skip past.  Indexed
                            // by room id and start time
                          
  // Check for any schedule conflicts in each room we're going to try and
  // book in;  also check that the booking conforms to the policy
  foreach ($bookings as $booking)
  {
    $skip_lists[$booking['room_id']] = array();
    if ($booking['rep_type'] != REP_NONE && !empty($reps))
    {
      if(count($reps) < $max_rep_entrys)
      {
        for ($i = 0; $i < count($reps); $i++)
        {
          // calculate diff each time and correct where events
          // cross DST
          $diff = $duration_seconds;
          $diff += cross_dst($reps[$i], $reps[$i] + $diff);
          
          $this_booking = $booking;
          $this_booking['start_time'] = $reps[$i];
          $this_booking['end_time'] = $reps[$i] + $diff;

          $tmp = mrbsCheckFree($this_booking, $ignore_id, $repeat_id);

          $skip_this_booking = FALSE;
          if (!empty($tmp))
          {
            // If we've been told to skip past existing bookings, then add
            // this start time to the list of start times to skip past.
            // Otherwise it's an invalid booking
            if ($skip)
            {
              $skip_lists[$this_booking['room_id']][] = $this_booking['start_time'];
              $skip_this_booking = TRUE;
            }
            else
            {
              $valid_booking = FALSE;
            }
            // In both cases remember the conflict data.   (We don't at the
            // moment do anything with the data if we're skipping, but we might
            // in the future want to display a list of bookings we've skipped past)
            $conflicts = array_merge($conflicts, $tmp);
          }
          // If we're not going to skip past this booking and we're not an admin for
          // this room, check that the booking conforms to the booking policy.
          // (If we're going to skip past this booking then it doesn't matter whether
          // it conforms to the policy or not because we're never going to make it)
          if (!$skip_this_booking &&
              !auth_book_admin($user, $this_booking['room_id']))
          {
            $errors = mrbsCheckPolicy($this_booking, $ignore_id, $repeat_id);
            if (count($errors) > 0)
            {
              $valid_booking = FALSE;
              $rules_broken = array_merge($rules_broken, $errors);
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
      $tmp = mrbsCheckFree($booking, $ignore_id, 0);
      if (!empty($tmp))
        {
          $valid_booking = FALSE;
          $conflicts = array_merge($conflicts, $tmp);
        }
        // if we're not an admin for this room, check that the booking
        // conforms to the booking policy
        if (!auth_book_admin($user, $booking['room_id']))
        {
          $errors = mrbsCheckPolicy($booking, $ignore_id, 0);
          if (count($errors) > 0)
          {
            $valid_booking = FALSE;
            $rules_broken = array_merge($rules_broken, $errors);
          }
        }
    }

  } // end foreach bookings

  // Tidy up the lists of conflicts and rules broken, getting rid of duplicates
  $conflicts = array_values(array_unique($conflicts));
  $rules_broken = array_values(array_unique($rules_broken));
  
  $result = array();
  $result['valid_booking'] = $valid_booking;
  $result['rules_broken'] = $rules_broken;
  $result['conflicts'] = $conflicts;
  
  // If we've just been asked to check the bookings, or if it wasn't a valid
  // booking, then stop here and return the results
  if ($just_check || !$valid_booking)
  {
    sql_mutex_unlock("$tbl_entry");
    return $result;
  }
    
  
  // Otherwise we go on to commit the booking
  
  $new_details = array(); // We will pass this array in the Ajax result
  $rooms = array();
  foreach ($bookings as $booking)
  {
    $rooms[] = $booking['room_id'];
  }
  foreach ($bookings as $booking)
  { 
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
        (($original_room_id == $booking['room_id']) ||
         (count($rooms) == 1) ||
         (($rooms[0] == $booking['room_id']) && !in_array($original_room_id, $rooms))))
    {
      // This is an existing booking which has been changed.   Keep the
      // original ical_uid and increment the sequence number.
      $booking['ical_sequence']++;
    }
    else
    {
      // This is a new booking.   We generate a new ical_uid and start
      // the sequence at 0 - unless there already are uid and sequence
      // numbers, for example when importing an iCalendar file
      if (empty($booking['ical_uid']))
      {
        $booking['ical_uid'] = generate_global_uid($booking['name']);
      }
      if (empty($booking['ical_sequence']))
      {
        $booking['ical_sequence'] = 0;
      }
    }

    if ($booking['rep_type'] == REP_NONE)
    {
      $booking['entry_type'] = ($repeat_id > 0) ? ENTRY_RPT_CHANGED : ENTRY_SINGLE;
      $booking['repeat_id'] = $repeat_id;
    }
    // Add in the list of bookings to skip
    if (!empty($skip_lists) && !empty($skip_lists[$booking['room_id']]))
    {
      $booking['skip_list'] = $skip_lists[$booking['room_id']];
    }
    // The following elements are needed for email notifications
    $booking['duration'] = $duration;
    $booking['dur_units'] = $dur_units;

    if ($booking['rep_type'] != REP_NONE)
    {
      $details = mrbsCreateRepeatingEntrys($booking);
      $new_id = $details['id'];
      $is_repeat_table = $details['series'];
    }
    else
    {
      // Create the entry:
      $new_id = mrbsCreateSingleEntry($booking);
      $is_repeat_table = FALSE;
    }
    $new_details[] = array('id' => $new_id, 'room_id' => $booking['room_id']);
    $booking['id'] = $new_id;  // Add in the id now we know it
    
    // Send an email if neccessary, provided that the entry creation was successful
    if ($send_mail && !empty($new_id))
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
                   WHERE R.id=${booking['room_id']} AND R.area_id = A.id
                   LIMIT 1";
          $res = sql_query($sql);
          $row = sql_row_keyed($res, 0);
          $booking['room_name'] = $row['room_name'];
          $booking['area_name'] = $row['area_name'];
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
        notifyAdminOnBooking($booking, $mail_previous, !isset($id), $is_repeat_table);
      }
    }   
  } // end foreach $bookings

  sql_mutex_unlock("$tbl_entry");
      
  $result['new_details'] = $new_details;
  $result['slots'] = intval(($common['end_time'] - $common['start_time'])/$resolution);

  return $result;
}

?>
