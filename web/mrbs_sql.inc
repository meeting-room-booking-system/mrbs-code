<?php
// $Id$

/** mrbsCheckFree()
 * 
 * Check to see if the time period specified is free
 * 
 * $room_id   - Which room are we checking
 * $starttime - The start of period
 * $endtime   - The end of the period
 * $ignore    - An entry ID to ignore, 0 to ignore no entries
 * $repignore - A repeat ID to ignore everything in the series, 0 to ignore no series
 * 
 * Returns:
 *   nothing   - The area is free
 *   something - An error occured, the return value is human readable
 */
function mrbsCheckFree($room_id, $starttime, $endtime, $ignore, $repignore)
{
  global $tbl_entry;
  global $enable_periods;
  global $periods;

  $user = getUserName();
  // Select any meetings which overlap ($starttime,$endtime) for this room:
  $sql = "SELECT id, name, start_time, create_by, status
            FROM $tbl_entry
           WHERE start_time < $endtime
             AND end_time > $starttime
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
    fatal_error(TRUE, "Fatal error: " . sql_error());  // probably because the table hasn't been created properly
  }
  if (sql_count($res) == 0)
  {
    sql_free($res);
    return "";
  }
  // Get the room's area ID for linking to day, week, and month views:
  $area = mrbsGetRoomArea($room_id);

  // Build a string listing all the conflicts:
  $err = "";
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $starts = getdate($row['start_time']);
    $param_ym = "area=$area&amp;year=$starts[year]&amp;month=".$starts['mon'];
    $param_ymd = $param_ym . "&amp;day=" . $starts['mday'];

    if ($enable_periods)
    {
      $p_num =$starts['minutes'];
      $startstr = utf8_strftime('%A %d %B %Y, ',
                                $row['start_time']) . $periods[$p_num];
    }
    else
    {
      $startstr = utf8_strftime('%A %d %B %Y %H:%M:%S',
                                $row['start_time']);
    }

    if (is_private_event($row['status'] & STATUS_PRIVATE) && $is_private_field['entry.name'] &&
         !getWritable($row['create_by'], $user, $room_id))
    {
       $row['name'] = get_vocab("private");
    }

    $err .= "<li><a href=\"view_entry.php?id=".$row['id']."\">".$row['name']."</a>"
      . " ( " . $startstr . ") "
      . "(<a href=\"day.php?$param_ymd\">".get_vocab("viewday")."</a>"
      . " | <a href=\"week.php?room=$room_id&amp;$param_ymd\">".get_vocab("viewweek")."</a>"
      . " | <a href=\"month.php?room=$room_id&amp;$param_ym\">".get_vocab("viewmonth")."</a>)</li>\n";
  }

  return $err;
}

/** mrbsCheckPolicy()
 * 
 * Check to see if a proposed booking conforms to any booking policies in force.,
 * Can be used both for editing/creating an entry as well as deleting an entry
 * (it's possible that in future some policies might apply to deletion and others
 * to creation)
 * 
 * $starttime - The start of period
 * $delete    - TRUE: We're intending to delete an entry
 *            - FALSE:  We're intending to create or edit an entry (the default)
 * 
 * Returns:
 *   nothing   - The booking is OK
 *   something - An error occured, the return value is human readable
 */
function mrbsCheckPolicy($starttime, $delete=FALSE)
{
  global $min_book_ahead_enabled, $max_book_ahead_enabled, $enable_periods;
  global $min_book_ahead_secs, $max_book_ahead_secs, $min_book_ahead_days, $max_book_ahead_days;

  $error = "";
  
  // Because MRBS has no notion of where we are in the day if we're using periods,
  // we'll just assume that we're at the beginning of the day.
  $now = ($enable_periods) ? mktime(0, 0, 0) : time();
  
  if ($min_book_ahead_enabled)
  {
    $min_book_ahead = ($enable_periods) ? ($min_book_ahead_days * 60*60*24) : $min_book_ahead_secs;
    if (($starttime - $now) < $min_book_ahead)
    {
      toTimeString($min_book_ahead, $units);
      $error = get_vocab("min_time_before"). " $min_book_ahead $units";
      return $error;
    }
  }
  
  if ($max_book_ahead_enabled)
  {
    $max_book_ahead = ($enable_periods) ? ($max_book_ahead_days * 60*60*24) : $max_book_ahead_secs;
    if (($starttime - $now) > $max_book_ahead)
    {
      toTimeString($max_book_ahead, $units);
      $error = get_vocab("max_time_before"). " $max_book_ahead $units";
      return $error;
    }
  }
  return $error;
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

  $sql = "SELECT start_time, create_by, id, entry_type FROM $tbl_entry WHERE ";
   
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
   
    if ($series && $row['entry_type'] == 2 && !$all)
    {
      continue;
    }
    
    // check that the booking policies allow us to delete this entry
    if (!$is_book_admin)
    {
      $tmp = mrbsCheckPolicy($row['start_time'], TRUE);
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
        $sql_col[] = $key;
        $sql_val[] = $data[$key];
        break;
        
      // strings  
      case 'create_by':
      case 'name':
      case 'type':
      case 'description':
        $sql_col[] = $key;
        $sql_val[] = "'" . addslashes($data[$key]) . "'";
        break;
      
      // special case - rep_opt
      case 'rep_opt':
        // pgsql doesn't like empty strings
        $sql_col[] = $key;
        $sql_val[] = (empty($data[$key])) ? "'0'" : "'" . addslashes($data[$key]) . "'";
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
              if (!isset($data['custom_fields'][$key]) || ($data['custom_fields'][$key] === ''))
              {
                // Try and set it to NULL when we can because there will be cases when we
                // want to distinguish between NULL and 0 - especially when the field
                // is a genuine integer.
                $value = ($field['is_nullable']) ? 'NULL' : 0;
              }
              else
              {
                $value = $data['custom_fields'][$key];
              }
              break;
            default:
              if (!isset($data['custom_fields'][$key]))
              {
                $value = '';
              }
              else
              {
                $value = "'" . addslashes($data['custom_fields'][$key]) . "'";
              }
              break;
          } // switch ($field_natures[$key])
            
          $sql_val[] = $value;
        }
        // do nothing for fields that aren't custom or otherwise listed above
        break;
          
    } // switch ($key)
  } // foreach
  
  // Note that we don't have to escape or quote the fieldname
  // thanks to the restriction on custom field names
  $sql = "INSERT INTO $table (" . implode(', ',$sql_col) . ") VALUES (" . implode(', ',$sql_val) . ")";

  if (sql_command($sql) < 0)
  {
    fatal_error(TRUE, "Fatal error: " . sql_error());  // probably because the table hasn't been created properly
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
function mrbsGetRepeatEntryList($time, $enddate, $rep_type, $rep_opt,
                                $max_ittr, $rep_num_weeks)
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
        while (!checkdate($month, $day, $year))
        {
          $day--;
          if ($day == 0)
          {
            // should never happen (so not internationalised), but
            // just in case it does, stop an infinite loop 
            fatal_error(TRUE, "Error generating series");
          }
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
 *   ['id']      - 0 if an error occurred, otherwise an id
 *   ['series']  - boolean: TRUE if the id refers to the repeat table
 *                          FALSE if the id refers to the entry table
 *
 */
function mrbsCreateRepeatingEntrys($data)
{
  global $max_rep_entrys;
  
  $result = array('id' => 0, 'series' => FALSE);
   
  $reps = mrbsGetRepeatEntryList($data['start_time'], $data['end_date'],
                                 $data['rep_type'], $data['rep_opt'],
                                 $max_rep_entrys, $data['rep_num_weeks']);

  if (count($reps) > $max_rep_entrys)
  {
    $result['id'] = 0;
    return $result;;
  }

  if (empty($reps))
  {
    $data['entry_type'] = 0;
    $data['repeat_id'] = 0;
    $id = mrbsCreateSingleEntry($data);
    $result['id'] = $id;
    $result['series'] = FALSE;
    return $result;
  }
   
  $id = mrbsCreateRepeatEntry($data);
    
  if ($id)
  {
    $data['entry_type'] = 1;
    $data['repeat_id'] = $id;
    $starttime = $data['start_time'];
    $endtime = $data['end_time'];
    for ($i = 0; $i < count($reps); $i++)
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
    $sql = "UPDATE $tbl_repeat SET reminded=$now WHERE id=$id";
    if (sql_command($sql) >= 0)
    {
      $sql = "UPDATE $tbl_entry SET reminded=$now WHERE repeat_id=$id";
      return sql_command($sql);
    }
  }
  else
  {
    $sql = "UPDATE $tbl_entry SET reminded=$now WHERE id=$id";
    if (sql_command($sql) > 0)
    {
      $repeat_id = sql_query1("SELECT repeat_id FROM $tbl_entry WHERE id=$id LIMIT 1");
      if ($repeat_id >= 0)
      {
        $sql = "UPDATE $tbl_repeat SET reminded=$now WHERE id=$repeat_id";
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
  $sql .= ", info_user='" . addslashes($user) . "'";
  $sql .= ", info_text='" . addslashes($note) . "'";
  $sql .= " WHERE id=$id";
  return sql_command($sql);
}

// mrbsApproveEntry($id, $series)
//
// Approve an entry with id $id.   If series is set to TRUE
// then the id is the id in the repeat table and we must approve
// all the individual entries.
// Returns FALSE on failure, TRUE on success
function mrbsApproveEntry($id, $series)
{
  global $tbl_entry, $tbl_repeat;
  
  if ($series)
  {
    // First update the repeat table
    $sql = "UPDATE $tbl_repeat 
               SET status=status&". ~STATUS_AWAITING_APPROVAL . "
             WHERE id=$id";
    if (sql_command($sql) > 0)  // We expect there to be one row affected
    {
      // Then update the entry table
      $sql = "UPDATE $tbl_entry 
                 SET status=status&". ~STATUS_AWAITING_APPROVAL . "
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
               SET status=status&". ~STATUS_AWAITING_APPROVAL . "
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
  global $approval_enabled;

  // Check that we've got an id
  if (!isset($id))
  {
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(0, ($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id")));
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
      case 'id':
      case 'entry_type':
        // Nothing to do
        break;
        
      case 'repeat_id':
        // repeat_id only exists in the entry table
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
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(0, sql_error());
    }
  }

  if (sql_count($res) < 1)
  {
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(0, ($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id")));
    }
  }

  $row = sql_row_keyed($res, 0);
  sql_free($res);
  
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

?>
