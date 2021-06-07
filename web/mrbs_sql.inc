<?php
namespace MRBS;

define('INTERVAL_DAY', 0);


// Pack the various status fields into a single bitmap
function pack_status(array &$data)
{
  $statuses = array('awaiting_approval' => STATUS_AWAITING_APPROVAL,
                    'private'           => STATUS_PRIVATE,
                    'tentative'         => STATUS_TENTATIVE);

  $data['status'] = 0;

  foreach ($statuses as $key => $value)
  {
    if (!empty($data[$key]))
    {
      $data['status'] |= $value;
    }
    unset($data[$key]);
  }
}


// Translates the status flags into a set of booleans
function unpack_status(array &$data)
{
  if (isset($data))
  {
    $data['awaiting_approval'] = (bool) ($data['status'] & STATUS_AWAITING_APPROVAL);
    $data['private']           = (bool) ($data['status'] & STATUS_PRIVATE);
    $data['tentative']         = (bool) ($data['status'] & STATUS_TENTATIVE);
    unset($data['status']);
  }
}


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
 *   something - An error occurred, the return value is an array of conflicts
 */
function mrbsCheckFree(array $booking, $ignore, $repignore)
{
  global $enable_periods, $periods, $twentyfourhour_format;
  global $strftime_format, $is_private_field;

  $room_id = $booking['room_id'];

  get_area_settings(get_area($room_id));

  // Select any meetings which overlap for this room:
  $sql = "SELECT E.id, name, start_time, create_by, status, room_name
            FROM " . _tbl('entry') . " E, " . _tbl('room') . " R
           WHERE E.room_id=R.id
             AND start_time < ?
             AND end_time > ?
             AND E.room_id = ?";

  $sql_params = array($booking['end_time'],
                      $booking['start_time'],
                      $room_id);

  if ($ignore > 0)
  {
    $sql .= " AND E.id <> ?";
    $sql_params[] = $ignore;
  }
  if ($repignore > 0)
  {
    $sql .= " AND (repeat_id IS NULL OR repeat_id <> ?)";
    $sql_params[] = $repignore;
  }
  $sql .= " ORDER BY start_time";

  $res = db()->query($sql, $sql_params);

  if ($res->count() == 0)
  {
    return "";
  }
  // Get the room's area ID for linking to day, week, and month views:
  $area = mrbsGetRoomArea($room_id);

  // Build an listing all the conflicts:
  $err = array();

  while (false !== ($row = $res->next_row_keyed()))
  {
    unpack_status($row);
    $starts = getdate($row['start_time']);

    if ($enable_periods)
    {
      $p_num =$starts['minutes'];
      $startstr = utf8_strftime($strftime_format['date'] . ", ",
                                $row['start_time']) . htmlspecialchars($periods[$p_num]);
    }
    else
    {
      $startstr = utf8_strftime(($twentyfourhour_format) ? $strftime_format['datetime24'] : $strftime_format['datetime12'],
                                $row['start_time']);
    }

    if (is_private_event($row['private']) &&
        $is_private_field['entry.name'] &&
        !getWritable($row['create_by'], $room_id))
    {
       $row['name'] = get_vocab("unavailable");
    }

    // enclose the viewday etc. links in a span to make it easier for JavaScript to strip them out
    $vars = array('year'  => $starts['year'],
                  'month' => $starts['mon'],
                  'day'   => $starts['mday'],
                  'area'  => $area,
                  'room'  => $room_id);

    $query = http_build_query($vars, '', '&');

    $err[] = "<a href=\"view_entry.php?id=" . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['name']) . '</a>' .
             ' (' . $startstr . ', ' . htmlspecialchars($row['room_name']) . ') ' .
             '<span>(' .
             '<a href="index.php?view=day&amp;' . htmlspecialchars($query) . '">' . get_vocab('viewday') . '</a> | ' .
             '<a href="index.php?view=week&amp;' . htmlspecialchars($query) . '">' . get_vocab('viewweek') . '</a> | ' .
             '<a href="index.php?view=month&amp;' . htmlspecialchars($query) . '">' . get_vocab('viewmonth') . '</a>' .
             ')</span>';
  }

  return $err;
}


// Checks whether the proposed booking $booking would exceed the maximum number/length of
// bookings in the interval of type $interval_type (can be 'day', 'week', 'month', 'year' or
// 'future').  If $only_area is set then only the bookings in the same area are considered.
// If $check_seconds is set then the total length of bookings is checked, otherwise the number.
// Returns NULL if OK, otherwise an error string.
function check_interval(array $booking, $ignore, $repignore, $interval_type='day', $only_area=false, $check_seconds=false)
{
  global $max_per_interval_global, $max_per_interval_area;
  global $max_secs_per_interval_global, $max_secs_per_interval_area;

  // Get the area id.   We only need to do this once as all bookings will be
  // in the same area.
  static $area_id = null;
  if (!isset($area_id))
  {
    $area_id = get_area($booking['room_id']);
  }

  // Get the location for indexing the $existing and $proposed arrays.   If it's a
  // global check then $location = 0, otherwise use the area_id.
  $location = ($only_area) ? $area_id : 0;
  // Get the check type for indexing the $existing and $proposed arrays
  $check_type = ($check_seconds) ? 'length' : 'number';

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
        $interval_end = mktime(0, 0, 0, $start_date['mon'], $start_date['mday'] + ($i * DAYS_PER_WEEK) - $skipback, $start_date['year']);
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

    if (!isset($existing[$location][$check_type][$interval_type][$interval_start]))
    {
      $sql = "SELECT E.start_time, E.end_time
                FROM " . _tbl('entry') . " E, " . _tbl('room') . " R, " . _tbl('area') . " A
               WHERE E.start_time < ?
                 AND E.end_time > ?
                 AND E.create_by = ?
                 AND E.room_id = R.id
                 AND R.area_id = A.id
                 AND R.disabled = 0";

      $sql_params = array($interval_end,
                          $interval_start,
                          $booking['create_by']);

      if ($check_seconds)
      {
        // If we're checking for the actual time of bookings, then exclude areas using periods
        $sql .= " AND A.enable_periods=0";
      }
      if ($only_area)
      {
        $sql .= " AND R.area_id=?";
        $sql_params[] = $area_id;
      }
      if ($ignore > 0)
      {
        $sql .= " AND E.id <> ?";
        $sql_params[] = $ignore;
      }
      if ($repignore > 0)
      {
        $sql .= " AND (E.repeat_id IS NULL OR E.repeat_id <> ?)";
        $sql_params[] = $repignore;
      }

      $existing[$location][$check_type][$interval_type][$interval_start] = 0;
      $proposed[$location][$check_type][$interval_type][$interval_start] = 0;
      $res = db()->query($sql, $sql_params);
      while (false !== ($row = $res->next_row_keyed()))
      {
        // If we're checking for total time we want the length of the existing booking that falls
        // inside the interval.  Otherwise it's just one more booking.
        if ($check_seconds)
        {
          $seconds = min($row['end_time'], $interval_end) - max($row['start_time'], $interval_start);
          $existing[$location][$check_type][$interval_type][$interval_start] += $seconds;
        }
        else
        {
          $existing[$location][$check_type][$interval_type][$interval_start]++;
        }
      }
    }

    // If we're checking for total time we want the length of the proposed booking that falls
    // inside the interval.  Otherwise it's just one more booking.
    if ($check_seconds)
    {
      $seconds = min($booking['end_time'], $interval_end) - max($booking['start_time'], $interval_start);
      $proposed[$location][$check_type][$interval_type][$interval_start] += $seconds;
    }
    else
    {
      $proposed[$location][$check_type][$interval_type][$interval_start]++;
    }

    // Check to see if the maximum number/length of bookings would be exceeded
    if ($check_seconds)
    {
      $max_allowed = ($only_area) ? $max_secs_per_interval_area[$interval_type] : $max_secs_per_interval_global[$interval_type];
      if (($existing[$location][$check_type][$interval_type][$interval_start] +
           $proposed[$location][$check_type][$interval_type][$interval_start]) >
          $max_allowed)
      {
        if ($only_area)
        {
          $max = $max_secs_per_interval_area[$interval_type];
          toTimeString($max, $units);
          return get_vocab("max_secs_per_${interval_type}_area", $max, $units);
        }
        else
        {
          $max = $max_secs_per_interval_global[$interval_type];
          toTimeString($max, $units);
          return get_vocab("max_secs_per_${interval_type}_global", $max, $units);
        }
      }
    }
    else
    {
      $max_allowed = ($only_area) ? $max_per_interval_area[$interval_type] : $max_per_interval_global[$interval_type];
      if (($existing[$location][$check_type][$interval_type][$interval_start] +
           $proposed[$location][$check_type][$interval_type][$interval_start]) >
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
    }

    $interval_start = $interval_end;
    $i++;
  }

  return null;
}


// Adjusts a book ahead time when using periods.  Because we don't
// know when periods are in the day we adjust the book ahead time
// down to the nearest whole day.
function adjust_ahead_seconds($seconds)
{
  global $enable_periods;

  if ($enable_periods)
  {
    // Round down to the nearest whole day
    $seconds -=  $seconds % SECONDS_PER_DAY;
  }

  return $seconds;
}


/** mrbsCheckPolicy()
 *
 * Check to see if a proposed booking conforms to any booking policies in force.
 * Can be used both for creating an entry as well as deleting an entry
 *
 * $booking   - The booking in question - an associative array
 * $delete    - TRUE: We're intending to delete an entry
 *            - FALSE:  We're intending to create an entry (the default)
 *
 * Returns:
 *            - An array of human readable errors, index by 'notices' or 'errors'.
 *              If there are no policy violations the array has length 0.
 */
function mrbsCheckPolicy(array $booking, $ignore, $repignore, $delete=FALSE)
{
  global $periods, $enable_periods;
  global $min_create_ahead_enabled, $min_create_ahead_secs;
  global $max_create_ahead_enabled, $max_create_ahead_secs;
  global $min_delete_ahead_enabled, $min_delete_ahead_secs;
  global $max_delete_ahead_enabled, $max_delete_ahead_secs;
  global $min_booking_date_enabled, $min_booking_date, $max_booking_date_enabled, $max_booking_date;
  global $max_duration_enabled, $max_duration_secs, $max_duration_periods;
  global $max_per_interval_global_enabled, $max_per_interval_area_enabled;
  global $max_secs_per_interval_global_enabled, $max_secs_per_interval_area_enabled;
  global $interval_types, $strftime_format;
  global $approved_bookings_cannot_be_changed, $approval_enabled;
  global $prevent_simultaneous_bookings, $prevent_invalid_types;
  global $periods_booking_opens, $measure_max_to_start_time;

  $result = array('notices' => array(),
                  'errors'  => array());

  $violations = array();

  // If the user is a booking admin for this room then we still check for policy
  // violations, but they are for information only and are classed as 'notices'
  $violation_status = (is_book_admin($booking['room_id'])) ? 'notices' : 'errors';

  $now = time();
  if ($enable_periods)
  {
    // And now add on the difference between the internal start of periods (12:00)
    // and the booking day start for periods
    $now += strtotime('12:00') - strtotime($periods_booking_opens);
  }

  // Check deletion policies
  if ($delete)
  {
    if ($approval_enabled && $approved_bookings_cannot_be_changed && !$booking['awaiting_approval'])
    {
      $violations[] = get_vocab('cannot_change_approved_bookings');
    }

    if ($min_delete_ahead_enabled)
    {
      $min_delete_ahead = adjust_ahead_seconds($min_delete_ahead_secs);
      if ($booking['start_time'] - $now < $min_delete_ahead + cross_dst($now, $booking['start_time']))
      {
        toTimeString($min_delete_ahead, $units);
        $violations[] = get_vocab('min_delete_time_before', $min_delete_ahead, $units);
      }
    }

    if ($max_delete_ahead_enabled)
    {
      $max_delete_ahead = adjust_ahead_seconds($max_delete_ahead_secs);
      $edge = ($measure_max_to_start_time) ? $booking['start_time'] : $booking['end_time'];
      if ($edge - $now > $max_delete_ahead + cross_dst($now, $edge))
      {
        toTimeString($max_delete_ahead, $units);
        $tag = ($measure_max_to_start_time) ? 'max_delete_time_before_start' : 'max_delete_time_before';
        $violations[] = get_vocab($tag, $max_delete_ahead, $units);
      }
    }
  }

  // Check creation policies
  else
  {
    // Check whether the user is trying to book two rooms simultaneously
    if ($prevent_simultaneous_bookings)
    {
      $sql_params = array(
          ':end_time'   => $booking['end_time'],
          ':start_time' => $booking['start_time'],
          ':create_by'  => $booking['create_by']
        );

      $sql = "SELECT id
                FROM " . _tbl('entry') . "
               WHERE start_time < :end_time
                 AND end_time > :start_time
                 AND create_by = :create_by";

      if ($ignore > 0)
      {
        $sql .= " AND id <> :ignore";
        $sql_params[':ignore'] = $ignore;
      }

      $sql .= " LIMIT 1";

      $res = db()->query($sql, $sql_params);

      if ($res->count() > 0)
      {
        $violations[] = get_vocab('no_simultaneous_bookings');
      }
    }

    // Check whether this booking type is allowed in this room
    if ($prevent_invalid_types)
    {
      $room_details = get_room_details($booking['room_id']);
      if (isset($room_details['invalid_types']) &&
          in_array($booking['type'], $room_details['invalid_types']))
      {
        $violations[] = get_vocab('type_not_allowed', get_type_vocab($booking['type']), $room_details['room_name']);
      }
    }

    if ($min_create_ahead_enabled)
    {
      $min_create_ahead = adjust_ahead_seconds($min_create_ahead_secs);
      if ($booking['start_time'] - $now < $min_create_ahead + cross_dst($now, $booking['start_time']))
      {
        toTimeString($min_create_ahead, $units);
        $violations[] = get_vocab('min_create_time_before', $min_create_ahead, $units);
      }
    }

    if ($max_create_ahead_enabled)
    {
      $max_create_ahead = adjust_ahead_seconds($max_create_ahead_secs);
      $edge = ($measure_max_to_start_time) ? $booking['start_time'] : $booking['end_time'];
      if ($edge - $now > $max_create_ahead + cross_dst($now, $edge))
      {
        toTimeString($max_create_ahead, $units);
        $tag = ($measure_max_to_start_time) ? 'max_create_time_before_start' : 'max_create_time_before';
        $violations[] = get_vocab($tag, $max_create_ahead, $units);
      }
    }
  }

  // Check min_booking_date
  if ($min_booking_date_enabled && !$delete)
  {
    list($y, $m, $d) = explode('-', $min_booking_date);
    if (isset($y) && isset($m) && isset($d) && checkdate($m, $d, $y))
    {
      if ($booking['start_time'] < mktime(0, 0, 0, $m, $d, $y))
      {
        $violations[] = get_vocab("earliest_booking_date", utf8_strftime($strftime_format['date'], mktime(0, 0, 0, $m, $d, $y)));
      }
    }
    else
    {
      trigger_error("Invalid min_book_ahead_date", E_USER_NOTICE);
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
        $violations[] = get_vocab("latest_booking_date", utf8_strftime($strftime_format['date'], mktime(0, 0, 0, $m, $d, $y)));
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
        $violations[] = get_vocab("max_booking_duration") . " $max_duration_periods " .
                    (($max_duration_periods > 1) ? get_vocab("periods") : get_vocab("period_lc"));
      }
    }
    elseif ($booking['end_time'] - $booking['start_time'] > $max_duration_secs)
    {
      $max_duration = $max_duration_secs;
      toTimeString($max_duration, $units);
      $violations[] = get_vocab("max_booking_duration") . " $max_duration $units";
    }
  }

  // Check max number of bookings allowed per interval for this user for each of
  // the interval types, both globally and for the area
  foreach ($interval_types as $interval_type)
  {
    // globally
    // number of bookings
    if (!empty($max_per_interval_global_enabled[$interval_type]) && !$delete)
    {
      $tmp = check_interval($booking, $ignore, $repignore, $interval_type, false);
      if (isset($tmp))
      {
        $violations[] = $tmp;
      }
    }
    // length of bookings
    if (!empty($max_secs_per_interval_global_enabled[$interval_type]) && !$delete)
    {
      $tmp = check_interval($booking, $ignore, $repignore, $interval_type, false, true);
      if (isset($tmp))
      {
        $violations[] = $tmp;
      }
    }
    // for the area
    // number of bookings
    if (!empty($max_per_interval_area_enabled[$interval_type]) && !$delete)
    {
      $tmp = check_interval($booking, $ignore, $repignore, $interval_type, true);
      if (isset($tmp))
      {
        $violations[] = $tmp;
      }
    }
    // length of bookings
    if (!empty($max_secs_per_interval_area_enabled[$interval_type]) && !$delete)
    {
      $tmp = check_interval($booking, $ignore, $repignore, $interval_type, true, true);
      if (isset($tmp))
      {
        $violations[] = $tmp;
      }
    }
  }

  $result[$violation_status] = $violations;

  return $result;
}


function get_entry_by_id($id)
{
  if (!isset($id))
  {
    return null;
  }

  $sql = "SELECT *
            FROM " . _tbl('entry') . "
           WHERE id=?
           LIMIT 1";

  $res = db()->query($sql, array($id));

  if ($res->count() == 0)
  {
    $result = null;
  }
  else
  {
    $result = $res->next_row_keyed();
    // Unpack the status bits
    unpack_status($result);
  }

  return $result;
}


// Returns the Unix timestamp for registration closing.  Takes account of
// periods, when registration closes at $periods_booking_opens on the day
// of the booking, because we don't know when in the day periods occur.
// This function compensates for DST changes, so that, for example, 1 week
// before occurs at the same time of day as the start time, even if there's
// a DST transition in between.
function registration_opens_timestamp(array $entry)
{
  global $enable_periods, $periods_booking_opens;

  $start_time = $entry['start_time'];

  // If we're using periods set the time back to midnight
  if ($enable_periods)
  {
    $start_time = timestamp_set_time($start_time, $periods_booking_opens);
  }

  $cross_dst = cross_dst(time(), $entry['start_time']);

  return $start_time - $entry['registration_opens'] - $cross_dst;
}


// Returns the Unix timestamp for registration opening.  Takes account of
// periods, when registration closes at $periods_booking_opens on the day
// of the booking, because we don't know when in the day periods occur.
// This function compensates for DST changes, so that, for example, 1 week
// before occurs at the same time of day as the start time, even if there's
// a DST transition in between.
function registration_closes_timestamp(array $entry)
{
  global $enable_periods, $periods_booking_opens;

  $start_time = $entry['start_time'];

  // If we're using periods set the time back to midnight
  if ($enable_periods)
  {
    $start_time = timestamp_set_time($start_time, $periods_booking_opens);
  }

  $cross_dst = cross_dst(time(), $entry['start_time']);

  return $start_time - $entry['registration_closes'] - $cross_dst;
}


// Checks whether an entry is open for registration
function entry_registration_is_open(array $entry)
{
  return (!$entry['registration_opens_enabled'] || (time() >= registration_opens_timestamp($entry))) &&
         (!$entry['registration_closes_enabled'] || (time() <= registration_closes_timestamp($entry)));
}


// Checks whether it is too late to cancel a registration, which is
// after the closing time for registration, if there is one, or else
// after the entry start time.
function entry_registration_cancellation_has_closed(array $entry)
{
  global $enable_periods;

  if (!empty($entry['registration_closes_enabled']))
  {
    return (time() > registration_closes_timestamp($entry));
  }

  if ($enable_periods)
  {
    // Allow registration to be cancelled all the way up to the end of the
    // the current day, because we don't know when in the day the period starts.
    $date = new DateTime();
    $date->setTimestamp($entry['start_time']);
    $date->setTime(0, 0);
    $date->modify('+1 day');
    return (time() > $date->getTimestamp());
  }
  else
  {
    return (time() > $entry['start_time']);
  }
}


// Get all the entries for the area with $area_id in the interval that starts at $interval_start
// and ends at $interval_start (both Unix timestamps).   Only gets entries for enabled rooms.
// Returns an array of entries.   Each element of the array is itself an array with keys
// corresponding to the entry table columns
function get_entries_by_area($area_id, $interval_start, $interval_end)
{
  // The GROUP BY is necessary to get n_registered to work
  $sql = "SELECT E.*, COUNT(P.entry_id) AS n_registered
            FROM " . _tbl('entry') . " E
       LEFT JOIN " . _tbl('room') . " R ON E.room_id = R.id
       LEFT JOIN " . _tbl('participants') . " P ON P.entry_id = E.id
           WHERE R.area_id = ?
             AND R.disabled = 0
             AND start_time < ? AND end_time > ?
        GROUP BY E.id
        ORDER BY start_time, E.id";   // necessary so that multiple bookings appear in the right order

  $res = db()->query($sql, array($area_id, $interval_end, $interval_start));

  $result = $res->all_rows_keyed();

  foreach ($result as &$row)
  {
    unpack_status($row);
  }
  unset($row);

  return $result;
}


// Get all the entries for the room with $room_id in the interval that starts at $interval_start
// and ends at $interval_start (both Unix timestamps).
// Returns an array of entries.   Each element of the array is itself an array with keys
// corresponding to the entry table columns
function get_entries_by_room($room_id, $interval_start, $interval_end)
{
  // The GROUP BY is necessary to get n_registered to work
  $sql = "SELECT E.*, COUNT(P.entry_id) AS n_registered
            FROM " . _tbl('entry') . " E
       LEFT JOIN " . _tbl('participants') . " P ON P.entry_id = E.id
           WHERE room_id = ?
             AND start_time < ? AND end_time > ?
        GROUP BY E.id
        ORDER BY start_time, id";   // necessary so that multiple bookings appear in the right order

  $res = db()->query($sql, array($room_id, $interval_end,  $interval_start));

  $result = $res->all_rows_keyed();

  foreach ($result as &$row)
  {
    unpack_status($row);
  }
  unset($row);

  return $result;
}


// Get the number of outstanding (ie waiting to be approved) for a user
function get_entries_n_outstanding(User $user)
{
  $sql_approval_enabled = some_area_predicate('approval_enabled');

  // Find out how many bookings are awaiting approval
  // (but only for areas where approval is required)
  $sql = "SELECT room_id, create_by
            FROM " . _tbl('entry') . " E, " . _tbl('room') . " R, " . _tbl('area') . " A
           WHERE (status&" . STATUS_AWAITING_APPROVAL . " != 0)
             AND E.room_id = R.id
             AND R.area_id = A.id
             AND R.disabled = 0
             AND A.disabled = 0
             AND $sql_approval_enabled";

  $res = db()->query($sql);

  $result = 0;

  // Get this user's own bookings awaiting approval or those made by others in rooms
  // for which this user is a booking admin.
  while (false !== ($row = $res->next_row_keyed()))
  {
    if ((strcasecmp($row['create_by'], $user->username) === 0) || is_book_admin($row['room_id']))
    {
      $result++;
    }
  }

  return $result;
}


/** mrbsDelEntry()
 *
 * Delete an entry, or optionally all entries.   Will also delete any newly
 * orphaned rows in the repeat table.
 *
 * $id     - The entry to delete
 * $series - If set, delete the series, except user modified entries
 * $all    - If set, include user modified entries in the series delete
 *
 * Returns FALSE if an error occurred, otherwise an array of start_times that
 * have been deleted.
 *
 */
function mrbsDelEntry($id, $series, $all)
{
  $start_times = array();

  // Get the repeat_id and room_id for this entry
  $sql = "SELECT repeat_id, room_id
            FROM " . _tbl('entry') . "
           WHERE id=?
           LIMIT 1";
  $res = db()->query($sql, array($id));
  if ($res->count() <= 0)
  {
    return FALSE;
  }
  $row = $res->next_row_keyed();
  $repeat_id = $row['repeat_id'];
  $room_id = $row['room_id'];

  $sql = "SELECT * FROM " . _tbl('entry') . " WHERE ";

  $sql_params = array();

  if ($series)
  {
    $sql .= "repeat_id=?";
    $sql_params[] = $repeat_id;
  }
  else
  {
    $sql .= "id=?";
    $sql_params[] = $id;
  }

  $res = db()->query($sql, $sql_params);

  while (false !== ($row = $res->next_row_keyed()))
  {
    unpack_status($row);

    if(!getWritable($row['create_by'], $room_id))
    {
      continue;
    }

    if ($series && $row['entry_type'] == ENTRY_RPT_CHANGED && !$all)
    {
      continue;
    }

    // check that the booking policies allow us to delete this entry
    $tmp = mrbsCheckPolicy($row, 0, 0, TRUE);

    if (empty($tmp['errors']))
    {
      $sql = "DELETE FROM " . _tbl('entry') . " WHERE id=?";
      if (db()->command($sql, array($row['id'])) > 0)
      {
        $start_times[] = $row['start_time'];
      }
    }
  }

  // Get rid of any orphaned rows in the repeat table
  $sql = "SELECT COUNT(*)
            FROM " . _tbl('entry') . "
           WHERE repeat_id=?";
  if (!empty($repeat_id) && (db()->query1($sql, array($repeat_id)) == 0))
  {
    $sql = "DELETE FROM " . _tbl('repeat') . " WHERE id=?";
    db()->command($sql, array($repeat_id));
  }

  asort($start_times);
  return $start_times;
}


/** mrbsCreateEntry()
 *
 * Create an entry in the database
 *
 * $table         - The table in which to create the entry
 * $data          - An array containing the row data for the entry
 *
 * Returns:
 *   0        - An error occurred while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateEntry($table, $data)
{
  global $standard_fields, $db_tbl_prefix;

  $sql_col = array();
  $sql_val = array();
  $table_no_prefix = utf8_substr($table, utf8_strlen($db_tbl_prefix));  // strip the prefix off the table name

  $fields = db()->field_info($table);

  $sql_params = array();

  // Pack the status fields into the bit map
  pack_status($data);

  foreach ($fields as $field)
  {
    $key = $field['name'];

    // Check to see whether custom field names obey the MRBS rules.   If we don't check
    // other parts of the code will run without producing PHP or SQL errors but will give
    // the wrong results.   A bit of a crude check, and in due course we probably ought to
    // allow custom fields with spaces in them (or else have custom fields created by MRBS).
    if (utf8_strpos($key, ' ') !== FALSE)
    {
      fatal_error("Custom field names cannot contain spaces.");
    }

    // If the key doesn't exist in the $data array then the database will just use its
    // default value for the column.    If it does exist and is set to NULL then we'll
    // write NULL to the database (which may not necessarily be the default value).
    if (array_key_exists($key, $data))
    {
      switch ($key)
      {
        // booleans
        case 'allow_registration':
        case 'registrant_limit_enabled':
        case 'registration_opens_enabled':
        case 'registration_closes_enabled':
          if (isset($data[$key]))
          {
            $sql_col[] = $key;
            $sql_val[] = '?';
            $sql_params[] = ($data[$key]) ? 1 : 0;
          }
          break;

        // integers
        case 'start_time':
        case 'end_time':
        case 'entry_type':
        case 'repeat_id':
        case 'rep_type':
        case 'month_absolute':
        case 'end_date':
        case 'room_id':
        case 'status':
        case 'ical_sequence':
          $sql_col[] = $key;
          $sql_val[] = '?';
          $sql_params[] = (isset($data[$key])) ? (int)$data[$key] : null;
          break;

        // integers NOT NULL
        case 'registrant_limit':
        case 'registration_opens':
        case 'registration_closes':
          if (isset($data[$key]))
          {
            $sql_col[] = $key;
            $sql_val[] = '?';
            $sql_params[] = (int)$data[$key];
          }
          break;

        // strings
        case 'create_by':
        case 'modified_by':
        case 'name':
        case 'type':
        case 'description':
        case 'month_relative':
        case 'ical_uid':
        case 'ical_recur_id':
          $sql_col[] = $key;
          $sql_val[] = '?';
          $sql_params[] = (isset($data[$key])) ? $data[$key] : null;
          break;

        // special case - rep_opt
        case 'rep_opt':
          // pgsql doesn't like empty strings
          $sql_col[] = $key;
          $sql_val[] = '?';
          $sql_params[] = (empty($data[$key])) ? '0' : $data[$key];
          break;

        // special case - rep_interval
        case 'rep_interval':
          if (!empty($data[$key]))
          {
            $sql_col[] = $key;
            $sql_val[] = '?';
            $sql_params[] = $data[$key];
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
                  $value = ($field['is_nullable']) ? null : 0;
                }
                else
                {
                  $value = (int)$data[$key];
                }
                break;
              default:
                if (!isset($data[$key]))
                {
                  $value = ($field['is_nullable']) ? null : '';
                }
                else
                {
                  $value = $data[$key];
                }
                break;
            } // switch ($field_natures[$key])

            $sql_val[] = '?';
            $sql_params[] = $value;
          }
          // do nothing for fields that aren't custom or otherwise listed above
          break;

      } // switch ($key)
    } // if
  } // foreach

  foreach ($sql_col as &$col)
  {
    $col = db()->quote($col);
  }

  $sql = "INSERT INTO $table (" . implode(', ', $sql_col) . ") VALUES (" . implode(', ',$sql_val) . ")";

  db()->command($sql, $sql_params);

  return db()->insert_id($table, "id");
}

/** mrbsCreateSingleEntry()
 *
 * Create a single (non-repeating) entry in the database
 *
 * $data      - An array containing the entry details
 *
 * Returns:
 *   0        - An error occurred while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateSingleEntry(array $data)
{
  // Make sure that any entry is of a positive duration.
  // This is to trap potential negative duration created when DST comes
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
    return mrbsCreateEntry(_tbl('entry'), $data);
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
 *   0        - An error occurred while inserting the entry
 *   non-zero - The entry's ID
 */
function mrbsCreateRepeatEntry(array $data)
{
  return mrbsCreateEntry(_tbl('repeat'), $data);
}


function trimToEndOfMonth(&$month, &$day, &$year)
{
  // Make the month valid so that we can use checkdate()
  while ($month > 12)
  {
    $month -= 12;
    $year++;
  }
  // Make the date valid if day is more than number of days in month:
  while (!checkdate($month, $day, $year) && ($day > 1))
  {
    $day--;
  }
}


/** mrbsGetRepeatEntryList
 *
 * Returns a list of the repeating entries
 *
 * $time          The start time
 * $rep_end_time  When the repeat ends (Unix timestamp)
 * $rep_details   An associative array containing the repeat details, indexed by
 *                  rep_type        What type of repeat is it
 *                  rep_opt         The repeat entries (if the repeat type is weekly then at least one
 *                                  repeat day must be set)
 *                  rep_interval    The repeat interval
 *                  month_absolute  The repeat day of the month for monthly repeats
 * $n             Maximum number of entries to find
 *
 * Returns:
 *   empty     - The entry does not repeat
 *   an array  - This is a list of start times of each of the repeat entries
 */

function mrbsGetRepeatEntryList($time, $rep_end_time, array $rep_details, $n)
{
  $entries = array();

  $date = getdate($time);

  $sec         = $date['seconds'];
  $min         = $date['minutes'];
  $hour        = $date['hours'];
  $day         = $date['mday'];
  $month       = $date['mon'];
  $year        = $date['year'];
  $start_day   = $date['wday'];
  $start_dom   = $day;  // the starting day of the month
  $start_month = $month;

  // Make sure that the first date is a member of the series
  switch($rep_details['rep_type'])
  {
    case REP_WEEKLY:
      for ($j=$start_day; ($j<DAYS_PER_WEEK+$start_day) && !$rep_details['rep_opt'][$j%DAYS_PER_WEEK]; $j++)
      {
        $day++;
      }
      break;
    case REP_MONTHLY:
      if (isset($rep_details['month_absolute']))
      {
        $day = $rep_details['month_absolute'];
        if ($day < $start_dom)
        {
          $month++;
        }
      }
      elseif (isset($rep_details['month_relative']))
      {
        $day = byday_to_day($year, $month, $rep_details['month_relative']);
        while (($day === FALSE) || (($day < $start_dom) && ($month == $start_month)))
        {
          $month++;
          $day = byday_to_day($year, $month, $rep_details['month_relative']);
        }
      }
      else
      {
        trigger_error("No monthly repeat type, E_USER_WARNING");
      }
      trimToEndOfMonth($month, $day, $year);
      break;
    default:
      break;
  }

  for ($i = 0; $i < $n; $i++)
  {
    $time = mktime($hour, $min, $sec, $month, $day, $year);

    if ($time > $rep_end_time)
    {
      break;
    }

    $entries[] = $time;

    switch($rep_details['rep_type'])
    {
      case REP_DAILY:
        $day = $day + $rep_details['rep_interval'];
        break;

      case REP_WEEKLY:
        $j = $cur_day = date("w", $time);
        // Skip over days of the week which are not enabled:
        do
        {
          $day++;
          $j = ($j + 1) % DAYS_PER_WEEK;
          // If we've got back to the beginning of the week, then skip
          // over the weeks we've got to miss out (eg miss out one week
          // if we're repeating every two weeks)
          if ($j == $start_day)
          {
            $day += DAYS_PER_WEEK * ($rep_details['rep_interval'] - 1);
          }
        }
        while (($j != $cur_day) && !$rep_details['rep_opt'][$j]);
        break;

      case REP_MONTHLY:
        do
        {
          $month = $month + $rep_details['rep_interval'];
          if (isset($rep_details['month_absolute']))
          {
            // Get the day of the month back to where it should be (in case we
            // decremented it to make it a valid date last time round)
            $day = $rep_details['month_absolute'];
          }
          else
          {
            $day = byday_to_day($year, $month, $rep_details['month_relative']);
          }
        } while ($day === FALSE);
        trimToEndOfMonth($month, $day, $year);
        break;

      case REP_YEARLY:
        // Get the day of the month back to where it should be (in case we
        // decremented it to make it a valid date last time round)
        $day = $start_dom;
        $year = $year + $rep_details['rep_interval'];
        trimToEndOfMonth($month, $day, $year);
        break;

      // Unknown repeat option
      default:
        trigger_error("Unknown repeat type, E_USER_NOTICE");
        break;
    }
  }

  return $entries;
}


/** mrbsCreateRepeatingEntrys()
 *
 * Creates a repeat entry in the data base + all the repeating entries
 *
 * $data      - An array containing the entry details
 *
 * Returns:
 *   an array
 *   ['id']          - 0 if an error occurred or if no bookings could be
 *                     made, otherwise an id
 *   ['series']      - boolean: TRUE if the id refers to the repeat table
 *                              FALSE if the id refers to the entry table
 *   ['start_times'] - an array of start times that have been created
 *
 */
function mrbsCreateRepeatingEntrys(array $data)
{
  global $max_rep_entrys;

  $result = array('id' => 0, 'series' => FALSE, 'start_times' => array());

  if (!isset($data['skip_list']))
  {
    $data['skip_list'] = array();
  }

  $rep_details = array();
  foreach (array('rep_type',
                 'rep_opt',
                 'rep_interval',
                 'month_absolute',
                 'month_relative') as $key)
  {
    if (isset($data[$key]))
    {
      $rep_details[$key] = $data[$key];
    }
  }

  $reps = mrbsGetRepeatEntryList($data['start_time'],
                                 $data['end_date'],
                                 $rep_details,
                                 $max_rep_entrys);

  // Don't make any bookings if
  // (a) we've been asked to book up more entries than we are
  //     allowed in a single repeat, or
  // (b) the repeat results in an empty set, or
  // (c) we've got to skip past all the entries
  if ((count($reps) > $max_rep_entrys) ||
      (count($reps) == 0) ||
      (count($reps) == count($data['skip_list'])))
  {
    $result['id'] = 0;
    return $result;
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
    $start_time = $data['start_time'];
    $end_time = $data['end_time'];
    // Even if there is only one entry in the series we treat the booking
    // as a series rather than a single booking.   It allows the series to
    // be extended later.
    for ($i = 0; $i < count($reps); $i++)
    {
      // Provided this isn't one of the entries to skip, go ahead
      // and make the booking
      if (!in_array($reps[$i], $data['skip_list']))
      {
        // calculate diff each time and correct where events
        // cross DST
        $diff = $end_time - $start_time;
        $diff += cross_dst($reps[$i], $reps[$i] + $diff);
        $data['start_time'] = $reps[$i];
        $data['end_time'] = $reps[$i] + $diff;

        $ent_id = mrbsCreateSingleEntry($data);
        $result['start_times'][] = $data['start_time'];
      }
    }
  }
  $result['id'] = $id;
  $result['series'] = TRUE;
  return $result;
}


// Gets the repeat_id for an entry in the entry table with id $entry_id
// Returns the repeat_id or NULL
function get_repeat_id($entry_id)
{
  $sql = "SELECT repeat_id
            FROM " . _tbl('entry') . "
           WHERE id=?
           LIMIT 1";

  $res = db()->query($sql, array($entry_id));

  if ($res->count() == 0)
  {
    // This should not happen
    trigger_error('$entry_id=' . "$entry_id does not exist.", E_USER_NOTICE);
    return NULL;
  }

  return $res->next_row_keyed()['repeat_id'];
}


// Update the time of last reminding.
// If the entry is part of a repeating series, then also increment
// the last reminder time in the repeat table and all the individual
// entries.  (Although strictly speaking the reminder time should apply
// either to a series or an individual entry, we update everything to
// prevent users bombarding admins with reminder emails)
//
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use the DB class error() method to get the error message.
function update_last_reminded($id, $series)
{
  $now = time();
  if ($series)
  {
    $sql = "UPDATE " . _tbl('repeat') . "
               SET reminded=?,
                   ical_sequence=ical_sequence+1
             WHERE id=?";
    db()->command($sql, array($now, $id));

    $sql = "UPDATE " . _tbl('entry') . "
               SET reminded=?,
                   ical_sequence=ical_sequence+1
             WHERE repeat_id=?";
    return db()->command($sql, array($now, $id));
  }
  else
  {
    $sql = "UPDATE " . _tbl('entry') . "
               SET reminded=?,
                   ical_sequence=ical_sequence+1
             WHERE id=?";
    if (db()->command($sql, array($now,$id)) > 0)
    {
      $repeat_id = get_repeat_id($id);
      if (isset($repeat_id))
      {
        $sql = "UPDATE " . _tbl('repeat') . "
                   SET reminded=?,
                       ical_sequence=ical_sequence+1
                 WHERE id=?";
        return db()->command($sql, array($now,$id));
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
// Returns -1 on error; use the DB class error() method to get the error
// message.
function update_more_info($id, $series, $user, $note)
{
  $table = ($series) ? _tbl('repeat') : _tbl('entry');
  $now = time();

  $sql_params = array();
  $sql = "UPDATE $table SET";
  $sql .= " info_time=?";
  $sql_params[] = $now;
  $sql .= ", info_user=?";
  $sql_params[] = $user;
  $sql .= ", info_text=?";
  $sql_params[] = $note;
  $sql .= " WHERE id=?";
  $sql_params[] = $id;
  return db()->command($sql, $sql_params);
}

// mrbsApproveEntry($id, $series)
//
// Approve an entry with id $id.   If series is set to TRUE
// then the id is the id in the repeat table and we must approve
// all the individual entries.
// We also update the ical_sequence number so that any emails that
// are generated will be treated by calendar clients as referring
// to the same meeting, rather than a new meeting.
//
// Returns FALSE on failure, otherwise an array of start times that
// have been approved
function mrbsApproveEntry($id, $series)
{
  if ($series)
  {
    // First update the repeat table if it's a series
    $sql = "UPDATE " . _tbl('repeat') . "
               SET status=status&(~" . STATUS_AWAITING_APPROVAL . "),
                   ical_sequence=ical_sequence+1
             WHERE id=?";  // PostgreSQL does not support LIMIT with UPDATE
    db()->command($sql, array($id));
    $id_column = 'repeat_id';
  }
  else
  {
    $id_column = 'id';
  }
  // Then update the entry table.  First of all we get a list of the
  // start times that will be approved, then we do the approval.
  $condition = "$id_column=? AND status&" . STATUS_AWAITING_APPROVAL . "!=0";
  $sql_params = array($id);
  $sql = "SELECT start_time
            FROM " . _tbl('entry') . "
           WHERE $condition";
  $start_times = db()->query_array($sql, $sql_params);

  if (($start_times !== FALSE) && (count($start_times) != 0))
  {
    $sql = "UPDATE " . _tbl('entry') . "
               SET status=status&(~" . STATUS_AWAITING_APPROVAL . "),
                   ical_sequence=ical_sequence+1
             WHERE $condition";  // PostgreSQL does not support LIMIT with UPDATE

    db()->command($sql, $sql_params);
  }

  if (is_array($start_times))
  {
    asort($start_times);
  }

  return $start_times;
}


function get_registration_by_id($id)
{
  if (!isset($id))
  {
    return null;
  }

  $sql = "SELECT *
            FROM " . _tbl('participants') . "
           WHERE id=?
           LIMIT 1";

  $res = db()->query($sql, array($id));

  if ($res->count() == 0)
  {
    $result = null;
  }
  else
  {
    $result = $res->next_row_keyed();
  }

  return $result;
}


// Get the people registered for an event
function get_registrants($id, $series)
{
  // You can't register for a series (yet)
  if ($series)
  {
    return null;
  }

  $sql = "SELECT *
            FROM " . _tbl('participants') . "
           WHERE entry_id=:entry_id";

  $res = db()->query($sql, array(':entry_id' => $id));

  return $res->all_rows_keyed();
}


// Check whether any entries in a series have users registered
function series_has_registrants($repeat_id)
{
  $sql = "SELECT COUNT(username)
            FROM " . _tbl('entry') . " E
       LEFT JOIN " . _tbl('participants') . " P ON E.id=P.entry_id
           WHERE E.repeat_id=:repeat_id
           LIMIT 1";

  $sql_params = array(':repeat_id' => $repeat_id);

  return (db()->query1($sql, $sql_params) > 0);
}


// get_booking_info($id, $series)
//
// Gets all the details for a booking with $id, which is in the
// repeat table if $series is set, otherwise in the entry table.

// Returns the results in an array with keys the same as the table
// field names.  In the event of an error stops with a fatal error,
// unless $silent is TRUE, when it returns FALSE.
function get_booking_info($id, $series, $silent=FALSE)
{
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
      fatal_error(($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id")));
    }
  }

  $table = ($series) ? _tbl('repeat') : _tbl('entry');
  $table_fields = db()->field_info($table);

  // Build an array of the field names in the repeat table so that
  // we'll be able to do some sanity checking later
  $repeat_fields = db()->field_info(_tbl('repeat'));
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
                 "A.enable_periods",
                 "(end_time - start_time) AS duration");

  foreach ($table_fields as $field)
  {
    switch ($field['name'])
    {
      // these fields only exist in the entry table
      case 'entry_type':
      case 'repeat_id':
      case 'ical_recur_id':
      case 'allow_registration':
      case 'registrant_limit':
      case 'registrant_limit_enabled':
      case 'registration_opens':
      case 'registration_opens_enabled':
      case 'registration_closes':
      case 'registration_closes_enabled':
        array_push($terms, $field['name']);
        break;

      case 'timestamp':
        array_push($terms, db()->syntax_timestamp_to_unix("timestamp") . "AS last_updated");
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
          // exists in the repeat table
          fatal_error("Custom fields problem, '".$field['name']."' exists in entry table but not in repeat table");
        }

        array_push($terms, "T." . db()->quote($field['name']));
        break;
    }
  }

  $sql = "SELECT " . implode(", ",$terms)."
            FROM $table T, " . _tbl('room') . " M, " . _tbl('area') . " A
           WHERE T.room_id = M.id
             AND M.area_id = A.id
             AND T.id=?";

  try
  {
    $res = db()->query($sql, array($id));
  }
  catch (DBException $e)
  {
    // Not sure why we've got the silent option in here.  Need to revisit this sometime.
    trigger_error(db()->error(), E_USER_WARNING);
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      throw $e;
    }
  }

  $row = $res->next_row_keyed();
  unset($res);

  if (!is_array($row) || (!is_visible($row['room_id'])))
  {
    // It's quite possible that the id will have disappeared, eg if somebody
    // else has deleted or edited the entry, or if the back button is pressed
    // after deleting an entry.
    if ($silent)
    {
      return FALSE;
    }
    else
    {
      fatal_error(($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id")));
    }
  }



  // Now get the duration.
  // Don't translate the units at this stage.   We'll translate them later.
  $d = get_duration($row['start_time'], $row['end_time'], $row['enable_periods'], $row['area_id'], false);
  $row['duration'] = $d['duration'];
  $row['dur_units'] = $d['dur_units'];

  // Translate the status field into a set of booleans
  unpack_status($row);

  // Get some extra information
  $row['registrants'] = get_registrants($id, $series);

  if ($series)
  {
    $row['entry_info_time'] = '';
    $row['entry_info_user'] = '';
    $row['entry_info_text'] = '';
  }
  else
  {
    // Get the repeat information
    if (!isset($row['repeat_id']))
    {
      $row['rep_type'] = REP_NONE;   // just as a precaution
      $row['repeat_info_time'] = '';
      $row['repeat_info_user'] = '';
      $row['repeat_info_text'] = '';
    }
    else
    {
      $sql = "SELECT rep_type, end_date, rep_opt, rep_interval, month_absolute, month_relative,
                     info_time AS repeat_info_time, info_user AS repeat_info_user,
                     info_text AS repeat_info_text
                FROM " . _tbl('repeat') . "
               WHERE id=?
               LIMIT 1";
      $res = db()->query($sql, array($row['repeat_id']));

      if (!$extra_row = $res->next_row_keyed())
      {
        if (!$res)
        {
          trigger_error(db()->error(), E_USER_WARNING);
        }
        if ($silent)
        {
          return FALSE;
        }
        else
        {
          fatal_error(get_vocab("invalid_series_id"));
        }
      }

      $row['rep_type']         = $extra_row['rep_type'];
      $row['end_date']         = $extra_row['end_date'];
      $row['rep_opt']          = $extra_row['rep_opt'];
      $row['rep_interval']     = $extra_row['rep_interval'];
      $row['month_absolute']   = $extra_row['month_absolute'];
      $row['month_relative']   = $extra_row['month_relative'];
      $row['repeat_info_time'] = $extra_row['repeat_info_time'];
      $row['repeat_info_user'] = $extra_row['repeat_info_user'];
      $row['repeat_info_text'] = $extra_row['repeat_info_text'];
      unset($res);
    }
  }

  return $row;
}


// Gets the id of an area with a given name.
// Returns NULL or the id.
function get_area_id($area_name)
{
  $sql = "SELECT id
            FROM " . _tbl('area') . "
           WHERE area_name=?
           LIMIT 1";

  $res = db()->query($sql, array($area_name));

  return ($res->count() == 0) ? null : $res->next_row()[0];
}


// Get the name of the area with id $id.   If $all is set to TRUE then all areas
// are searched, otherwise just those which have not been disabled
// Returns FALSE if there is an error, NULL if the id does not exist
function get_area_name($id, $all=FALSE)
{
  $id = (int)$id;

  $sql = "SELECT area_name
            FROM " . _tbl('area') . "
           WHERE id=?";
  if (empty($all))
  {
    $sql .= " AND disabled=0";
  }
  $sql .= " LIMIT 1";

  $res = db()->query($sql, array($id));

  return ($res->count() == 0) ? null : $res->next_row()[0];
}


// Gets an array of area names indexed by area id.
// If $all=TRUE then all areas are returned, otherwise just the ones that
// are not disabled
function get_area_names($all=FALSE)
{
  $areas = array();

  $sql = "SELECT id, area_name FROM " . _tbl('area');
  if (empty($all))
  {
    $sql .= " WHERE disabled=0";
  }
  $sql .= " ORDER BY sort_key";

  $res = db()->query($sql);

  while (false !== ($row = $res->next_row_keyed()))
  {
    $areas[$row['id']] = $row['area_name'];
  }

  return $areas;
}


// Get all the area details for $area_id.
// Returns NULL if $area_id does not exist.
function get_area_details($area_id)
{
  $sql = "SELECT *
            FROM " . _tbl('area') . "
           WHERE id=?
           LIMIT 1";

  $res = db()->query($sql, array($area_id));

  return ($res->count() == 0) ? null : $res->next_row_keyed();
}


// Get the name of the room with id $room_id.   If $all is set to TRUE then all rooms
// are searched, otherwise just those which have not been disabled
// Returns NULL if the id does not exist
function get_room_name($room_id, $all=FALSE)
{
  $room_id = (int)$room_id;

  $sql = "SELECT room_name
            FROM " . _tbl('room') . " R, " . _tbl('area') . " A
           WHERE R.id=?
             AND R.area_id=A.id";

  if (empty($all))
  {
    $sql .= " AND R.disabled=0 AND A.disabled=0";
  }
  $sql .= " LIMIT 1";

  $res = db()->query($sql, array($room_id));

  return ($res->count() == 0) ? null : $res->next_row()[0];
}


// Gets an array of room names in an area indexed by room id.
// If $all=TRUE then all areas are returned, otherwise just the ones that
// are not disabled
function get_room_names($area_id, $all=FALSE)
{
  $rooms = array();

  $sql = "SELECT id, room_name
            FROM " . _tbl('room') . "
           WHERE area_id = :area_id";
  if (empty($all))
  {
    $sql .= " AND disabled=0";
  }
  $sql .= " ORDER BY sort_key";

  $res = db()->query($sql, array(':area_id' => $area_id));

  while (false !== ($row = $res->next_row_keyed()))
  {
    if (is_visible($row['id']))
    {
      $rooms[$row['id']] = $row['room_name'];
    }
  }

  return $rooms;
}


// Get all the room details for $room_id.
// Returns FALSE on error, NULL if $room_id does not exist or is invisible.
function get_room_details($room_id)
{
  if (!is_visible($room_id))
  {
    return null;
  }

  $sql = "SELECT *
            FROM " . _tbl('room') . "
           WHERE id=?
           LIMIT 1";

  $res = db()->query($sql, array($room_id));

  $details = ($res->count() == 0) ? null : $res->next_row_keyed();

  // The invalid types column is json_encoded
  if (isset($details['invalid_types']))
  {
    $details['invalid_types'] = json_decode($details['invalid_types']);
  }

  return $details;
}


// Gets the rooms in an area
// If $include_disabled is TRUE then all rooms are returned, whether or not they are disabled,
// otherwise just the ones that are not disabled.
// If $include_invisible is TRUE then all rooms are returned, whether or not they are visible,
// otherwise just the ones that are visible to the current user.
function get_rooms($area, $include_disabled=false, $include_invisible=false)
{
  $sql = "SELECT R.*
            FROM " . _tbl('room') . " R, " . _tbl('area') . " A
           WHERE R.area_id=?
           AND R.area_id = A.id";

  if (!($include_disabled))
  {
    $sql .= " AND R.disabled = 0" .
            " AND A.disabled = 0";
  }

  // In early versions of MRBS the sort_key field didn't exist and this function
  // is called before the database can be upgraded.
  if (db()->field_exists(_tbl('room'), 'sort_key'))
  {
    $sql .= " ORDER BY R.sort_key";
  }

  $res = db()->query($sql, array($area));

  $result = array();

  while (false !== ($row = $res->next_row_keyed()))
  {
    if ($include_invisible || is_visible($row['id']))
    {
      if (isset($row['invalid_types']))
      {
        $row['invalid_types'] = json_decode($row['invalid_types']);
      }
      $result[] = $row;
    }
  }

  return $result;
}


// Gets the ids of all the invisible rooms in the system
function get_invisible_room_ids()
{
  $sql = "SELECT id FROM " . _tbl('room');

  $res = db()->query($sql);

  $result = array();

  while (false !== ($row = $res->next_row_keyed()))
  {
    if (!is_visible($row['id']))
    {
      $result[] = $row['id'];
    }
  }

  return $result;
}


// Gets a two-dimensional array of period names indexed by area id.
function get_period_names()
{
  static $period_names = null;

  if (isset($period_names))
  {
    return $period_names;
  }

  $period_names = array();

  $sql = "SELECT id, periods FROM " . _tbl('area');

  $res = db()->query($sql);

  while (false !== ($row = $res->next_row_keyed()))
  {
    $period_names[$row['id']] = json_decode($row['periods']);
  }

  return $period_names;
}


function mrbsGetRoomArea($id)
{
  $sql = "SELECT area_id
            FROM " . _tbl('room') . "
           WHERE id=?
           LIMIT 1";

  $id = db()->query1($sql, array($id));

  return ($id <= 0) ? 0 : $id;
}


// Adds an area, returning the new id, or FALSE on failure with the
// error in $error
function mrbsAddArea($name, &$error)
{
  global $area_defaults, $boolean_fields;

  // First of all check that we've got a name
  if (!isset($name) || ($name === ''))
  {
    $error = "empty_name";
    return FALSE;
  }

  // Truncate the name field to the maximum length as a precaution.
  $name = utf8_substr($name, 0, maxlength('area.area_name'));
  // Acquire a mutex to lock out others who might be editing the area
  if (!db()->mutex_lock(_tbl('area')))
  {
    fatal_error(get_vocab("failed_to_acquire"));
  }
  // Check that the area name is unique
  $sql = "SELECT COUNT(*)
            FROM " . _tbl('area') . "
           WHERE area_name=?
           LIMIT 1";
  if (db()->query1($sql, array($name)) > 0)
  {
    db()->mutex_unlock(_tbl('area'));
    $error = "invalid_area_name";
    return FALSE;
  }
  // If so, insert the area into the database.   We insert the area name that
  // we have been given, together with the default values for the per-area settings

  // Build arrays of data to be inserted into the table
  $sql_params = array();
  $sql_col = array();
  $sql_val = array();
  // Get the information about the fields in the room table
  $fields = db()->field_info(_tbl('area'));
  // Loop through the fields and build up the arrays
  foreach ($fields as $field)
  {
    $key = $field['name'];
    switch ($key)
    {
    case 'area_name':
    case 'sort_key':
      $sql_col[] = $key;
      $sql_val[] = "?";
      $sql_params[] = $name;
      break;
    case 'periods':
      $sql_col[] = $key;
      $sql_val[] = "?";
      $sql_params[] = json_encode($area_defaults[$key]);
      break;
    default:
      if (array_key_exists($key, $area_defaults))
      {
        $sql_col[] = $key;
        $sql_val[] = "?";
        if (in_array($key, $boolean_fields['area']))
        {
          $sql_params[] = ($area_defaults[$key]) ? 1 : 0;
        }
        else
        {
          $sql_params[] = $area_defaults[$key];
        }
      }
      break;
    }
  }

  $sql = "INSERT INTO " . _tbl('area') . " (" . implode(', ',$sql_col) . ")
               VALUES (" . implode(', ',$sql_val) . ")";
  db()->command($sql, $sql_params);
  $area = db()->insert_id(_tbl('area'), 'id');

  // Release the mutex
  db()->mutex_unlock(_tbl('area'));
  return $area;
}


// Adds a room, returning the new id, or FALSE on failure with the
// error in $error
function mrbsAddRoom($name, $area, &$error, $description='', $capacity='', $room_admin_email='')
{
  // First of all check that we've got a name
  if (!isset($name) || ($name === ''))
  {
    $error = "empty_name";
    return FALSE;
  }

  // Truncate the name and description fields to the maximum length as a precaution.
  $name = utf8_substr($name, 0, maxlength('room.room_name'));
  $description = utf8_substr($description, 0, maxlength('room.description'));

  if (empty($capacity))
  {
    $capacity = 0;
  }
  // Acquire a mutex to lock out others who might be editing rooms
  if (!db()->mutex_lock(_tbl('room')))
  {
    fatal_error(get_vocab("failed_to_acquire"));
  }
  // Check that the room name is unique within the area
  $sql = "SELECT COUNT(*)
            FROM " . _tbl('room') . "
           WHERE room_name=? AND area_id=?
           LIMIT 1";
  if (db()->query1($sql, array($name, $area)) > 0)
  {
    db()->mutex_unlock(_tbl('room'));
    $error = "invalid_room_name";
    return FALSE;
  }
  // If so, insert the room into the database
  $sql = "INSERT INTO " . _tbl('room') . " (room_name, sort_key, area_id, description, capacity, room_admin_email)
          VALUES (?, ?, ?, ?, ?, ?)";
  db()->command($sql, array($name,$name,$area,$description,$capacity,$room_admin_email));
  $room = db()->insert_id(_tbl('room'), 'id');

  // Release the mutex
  db()->mutex_unlock(_tbl('room'));
  return $room;
}


function update_participants($old_id, $new_id)
{
  $sql = "UPDATE " . _tbl('participants') . "
             SET entry_id=:new_id
           WHERE entry_id=:old_id";

  $sql_params = array(':new_id' => $new_id, ':old_id' => $old_id);

  try
  {
    db()->command($sql, $sql_params);
  }
  catch (DBException $exception)
  {
    trigger_error($exception->getMessage(), E_USER_WARNING);
    return false;
  }

  return true;
}



// Makes bookings
//    $bookings     an array of bookings
//    $id           the id of the current booking when editing an existing entry
function mrbsMakeBookings(array $bookings, $id=NULL, $just_check=FALSE, $skip=FALSE, $original_room_id=NULL, $send_mail=FALSE, $edit_type='')
{
  global $max_rep_entrys, $enable_periods, $resolution, $mail_settings;
  global $prevent_simultaneous_bookings;

  // All the data, except for the status and room id, will be common
  // across the bookings
  $common = $bookings[0];
  // Work out the duration in seconds, but adjust it for DST changes so that
  // the user will still see, for example, "24 hours" when a booking goes from
  // 1200 one day to 1200 the next, crossing a DST boundary.
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
    $rep_details = array();
    foreach (array('rep_type',
                   'rep_opt',
                   'rep_interval',
                   'month_absolute',
                   'month_relative') as $key)
    {
      if (isset($common[$key]))
      {
        $rep_details[$key] = $common[$key];
      }
    }
    $reps = mrbsGetRepeatEntryList($common['start_time'],
                                   isset($common['end_date']) ? $common['end_date'] : 0,
                                   $rep_details,
                                   $max_rep_entrys);
  }

  // When checking for overlaps, for Edit (not New), ignore this entry and series:
  if (isset($id))
  {
    $ignore_id = $id;
    $repeat_id = get_repeat_id($id);
  }
  else
  {
    $ignore_id = 0;
    $repeat_id = null;
  }

  // Validate the booking for (a) conflicting bookings and (b) conformance to policy rules
  $valid_booking = true;
  $conflicts  = array();                      // Holds a list of all the conflicts
  $violations = array('notices' => array(),   // Holds an array of the policy rules that have been broken
                      'errors'  => array());  // classified by 'notices' and 'errors'
  $skip_lists = array();    // Holds a 2D array of bookings to skip past.  Indexed
                            // by room id and start time

  // Do the simple check for simultaneous bookings: if there is more than one booking
  // then this is going to result in a simultaneous booking.  The only question is whether
  // this a notice or an error.  If the user is an ordinary user in more than one of
  // the rooms, then it's an error, otherwise a notice.
  // The policy check later on will check whether the booking is at the same time as
  // any other bookings made by this user.
  if ($prevent_simultaneous_bookings && (count($bookings) > 1))
  {
    $violation_status = 'notices';
    $non_admin_rooms = 0;
    foreach($bookings as $booking)
    {
      if (!is_book_admin($booking['room_id']))
      {
        $non_admin_rooms++;
        if ($non_admin_rooms > 1)
        {
          $violation_status = 'errors';
          $valid_booking = false;
          break;
        }
      }
    }
    $violations[$violation_status][] = get_vocab('no_simultaneous_bookings');
  }

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
          // Calculate diff each time and correct where events cross DST
          $diff = $duration_seconds;
          $diff += cross_dst($reps[$i], $reps[$i] + $diff);

          $this_booking = $booking;
          $this_booking['start_time'] = $reps[$i];
          $this_booking['end_time'] = $reps[$i] + $diff;

          // Check that this entry doesn't overlap with the next in the series.
          // (This can happen if people mistake the end date for the repeat end date.)
          if (isset($reps[$i+1]) && ($this_booking['end_time'] > $reps[$i+1]))
          {
            $valid_booking = false;
            $conflicts[] = get_vocab('conflicts_with_self');
            break;
          }

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
          // If we're not going to skip past this booking, check that the booking
          // conforms to the booking policy.  (If we're going to skip past this
          // booking then it doesn't matter whether or not it conforms to the policy
          // because we're never going to make it)
          if (!$skip_this_booking)
          {
            $violations_this_booking = mrbsCheckPolicy($this_booking, $ignore_id, $repeat_id);
            foreach (array('notices', 'errors') as $key)
            {
              $violations[$key] = array_merge($violations[$key], $violations_this_booking[$key]);
            }
            if (!empty($violations_this_booking['errors']))
            {
              $valid_booking = FALSE;
            }
          }
        } // for
      }
      else
      {
        if (is_book_admin($booking['room_id']))
        {
          $violation_status = 'notices';
        }
        else
        {
          $violation_status = 'errors';
          $valid_booking = false;
        }
        $violations[$violation_status][] = get_vocab("too_many_entries", $max_rep_entrys - 1);
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
      // check that the booking conforms to the booking policy
      $violations_this_booking = mrbsCheckPolicy($booking, $ignore_id, 0);
      foreach (array('notices', 'errors') as $key)
      {
        $violations[$key] = array_merge($violations[$key], $violations_this_booking[$key]);
      }
      if (!empty($violations_this_booking['errors']))
      {
        $valid_booking = FALSE;
      }
    }

  } // end foreach bookings

  // If we are editing an existing booking then we need to check that we are
  // allowed to delete it
  if (isset($id))
  {

    if ($edit_type == "series")
    {
      $old_bookings = get_bookings_in_series($repeat_id);
    }
    else
    {
      $old_bookings = array();
      $old_bookings[] = get_entry_by_id($id);
    }

    foreach ($old_bookings as $old_booking)
    {
      $violations_this_booking = mrbsCheckPolicy($old_booking, FALSE, FALSE, TRUE);
      foreach (array('notices', 'errors') as $key)
      {
        $violations[$key] = array_merge($violations[$key], $violations_this_booking[$key]);
      }
      if (!empty($violations_this_booking['errors']))
      {
        $valid_booking = FALSE;
      }
    }

  }

  // Tidy up the lists of conflicts and rules broken, getting rid of duplicates
  $conflicts = array_values(array_unique($conflicts));
  foreach (array('notices', 'errors') as $key)
  {
   $violations[$key] = array_values(array_unique($violations[$key]));
  }

  $result = array();
  $result['valid_booking'] = $valid_booking;
  $result['violations'] = $violations;
  $result['conflicts'] = $conflicts;
  // If we've just been asked to check the bookings, or if it wasn't a valid
  // booking, then stop here and return the results
  if ($just_check || !$valid_booking)
  {
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
      $booking['entry_type'] = (isset($repeat_id)) ? ENTRY_RPT_CHANGED : ENTRY_SINGLE;
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
      asort($details['start_times']);
      $result['start_times'] = $details['start_times'];
    }
    else
    {
      // Create the entry:
      $new_id = mrbsCreateSingleEntry($booking);
      // Update the participants table
      if (isset($id))
      {
        update_participants($id, $new_id);
      }

      $is_repeat_table = FALSE;
      $result['start_times'] = array($booking['start_time']);
    }
    $new_details[] = array('id' => $new_id, 'room_id' => $booking['room_id']);
    $booking['id'] = $new_id;  // Add in the id now we know it

    // Send an email if necessary, provided that the entry creation was successful
    if ($send_mail && !empty($new_id))
    {
      // Only send an email if (a) this is a changed entry and we have to send emails
      // on change or (b) it's a new entry and we have to send emails for new entries
      if ((isset($id) && $mail_settings['on_change']) ||
          (!isset($id) && $mail_settings['on_new']))
      {
        require_once "functions_mail.inc";

        // Get room name and area name for email notifications.
        $sql = "SELECT R.room_name, A.area_name
                  FROM " . _tbl('room') . " R, " . _tbl('area') . " A
                 WHERE R.id=? AND R.area_id = A.id
                 LIMIT 1";
        $res = db()->query($sql, array($booking['room_id']));
        $row = $res->next_row_keyed();
        $booking['room_name'] = $row['room_name'];
        $booking['area_name'] = $row['area_name'];

        // If this is a modified entry then get the previous entry data
        // so that we can highlight the changes
        if (isset($id))
        {
          if ($edit_type == "series")
          {
            $mail_previous = get_booking_info($repeat_id, TRUE);
          }
          else
          {
            $mail_previous = get_booking_info($id, FALSE);
          }
        }
        else
        {
          $mail_previous = array();
        }
        // Send the email
        // TODO: The email notifications really need to be moved to a later point in edit_entry_handler.php
        // TODO: so that they are outside the transaction block.  There are two reasons for this.  The first is
        // TODO: that if one of the bookings fails for some reason the transaction will be rolled back, but
        // TODO: the email notifications will already have been sent.  The second is that they are inside a
        // TODO: lock and so if the email notifications take a while, it will keep the lock longer than
        // TODO: necessary.  (This isn't as much of a problem as it used to be now that notifyAdminOnBooking()
        // TODO: only adds a message to a queue, and the actual sending of the email is performed on shutdown,
        // TODO: but can still be a problem if, for example, email addresses have to be looked up on a remote
        // TODO: server, eg an LDAP server, and the query takes a while.
        notifyAdminOnBooking($booking, $mail_previous, !isset($id), $is_repeat_table, $result['start_times']);
      }
    }
  } // end foreach $bookings

  $result['new_details'] = $new_details;
  $result['slots'] = intval(($common['end_time'] - $common['start_time'])/$resolution);

  return $result;
}


function get_bookings_in_series($repeat_id)
{
  $bookings = array();

  $sql = "SELECT id
           FROM " . _tbl('entry') . "
          WHERE repeat_id = ?";
  $res = db()->query_array($sql, array($repeat_id));

  foreach ($res as $id)
  {
    $bookings[] = get_entry_by_id($id);
  }

  return $bookings;
}
