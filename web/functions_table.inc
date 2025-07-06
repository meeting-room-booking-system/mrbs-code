<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Calendar\CalendarMonthOneRoom;
use MRBS\Calendar\Map;

function get_date_classes(DateTime $date) : array
{
  $result = array();

  if ($date->isWeekend())
  {
    $result[] = 'weekend';
  }
  if ($date->isHoliday())
  {
    $result[] = 'holiday';
  }
  if ($date->isToday())
  {
    $result[] = 'today';
  }

  return $result;
}


// Prepares an entry for display by (a) adding in registration level information
// and (b) replacing the text in private fields if necessary.
function prepare_entry(array $entry) : array
{
  global $is_private_field, $show_registration_level, $auth, $kiosk;

  // Add in the registration level details
  if ($show_registration_level && $entry['allow_registration'])
  {
    // Check whether we should be showing the registrants' names
    $show_names = ($auth['show_registrant_names_in_calendar'] && ($entry['n_registered'] > 0));
    if ($show_names && !$auth['show_registrant_names_in_public_calendar'])
    {
      // If we're not allowed to show names in the public calendar, check that the user is logged in
      // and has an access level of at least 1
      $mrbs_user = session()->getCurrentUser();
      $show_names = isset($mrbs_user) && ($mrbs_user->level > 0);
    }
    $names = ($show_names) ? implode(', ', auth()->getRegistrantsDisplayNames($entry)) : '';
    if ($entry['registrant_limit_enabled'])
    {
      $tag = ($show_names) ? 'registration_level_limited_with_names' : 'registration_level_limited';
      $entry['name'] .= get_vocab($tag, $entry['n_registered'], $entry['registrant_limit'], $names);
    }
    else
    {
      $tag = ($show_names) ? 'registration_level_unlimited_with_names' : 'registration_level_unlimited';
      $entry['name'] .= get_vocab($tag, $entry['n_registered'], $names);
    }
  }

  // Check whether the event is private
  if (is_private_event($entry['private']) &&
      ($kiosk || !getWritable($entry['create_by'], $entry['room_id'])))
  {
    $entry['private'] = true;

    foreach (array('name', 'description') as $key)
    {
      if ($is_private_field["entry.$key"])
      {
        $entry[$key] = get_vocab('unavailable');
      }
    }

    if (!empty($is_private_field['entry.type']))
    {
      $entry['type'] = 'private_type';
    }
  }
  else
  {
    $entry['private'] = false;
  }

  return $entry;
}


// Returns an array of classes to be used for the entry
function get_entry_classes(array $entry) : array
{
  global $approval_enabled, $confirmation_enabled;

  $classes = array($entry['type']);

  if ($entry['private'])
  {
    $classes[] = 'private';
  }

  if ($approval_enabled && ($entry['awaiting_approval']))
  {
    $classes[] = 'awaiting_approval';
  }

  if ($confirmation_enabled && ($entry['tentative']))
  {
    $classes[] = 'tentative';
  }

  if (isset($entry['repeat_id']))
  {
    $classes[] = 'series';
  }

  if ($entry['allow_registration'])
  {
    if ($entry['registrant_limit_enabled'] &&
        ($entry['n_registered'] >= $entry['registrant_limit']))
    {
      $classes[] = 'full';
    }
    else
    {
      $classes[] = 'spaces';
    }
  }

  return $classes;
}


function get_timeslot_text(int $s, int $resolution) : string
{
  global $show_slot_endtime;

  $result = hour_min($s);

  if ($show_slot_endtime)
  {
    $result .= '-' . hour_min($s + $resolution);
  }

  return $result;
}


// Draw a room cell to be used in the header rows/columns of the calendar views
//    $room    contains the room details
//    $vars    an associative array containing the variables to be used to build the link
function room_cell_html(array $room, array $vars) : string
{
  $link = 'index.php?' . http_build_query($vars, '', '&');
  $link = multisite($link);

  switch ($vars['view'])
  {
    case 'day':
      $tag = 'viewday';
      break;
    case 'week':
      $tag = 'viewweek';
      break;
    case 'month':
      $tag = 'viewmonth';
      break;
    default:
      trigger_error("Unknown view '" . $vars['view'] . "'", E_USER_NOTICE);
      $tag = 'viewweek';
      break;
  }

  $title = get_vocab($tag) . "\n\n" . $room['description'];
  $html = '';
  $html .= '<th data-room="' . escape_html($room['id']) . '">';
  $html .= '<a href="' . escape_html($link) . '"' .
           ' title = "' . escape_html($title) . '">';
  $html .= escape_html($room['room_name']);
  // Put the capacity in a span to give flexibility in styling
  $html .= '<span class="capacity';
  if ($room['capacity'] == 0)
  {
    $html .= ' zero';
  }
  $html .= '">' . escape_html($room['capacity']);
  $html .= '</span>';
  $html .= '</a>';
  $html .= "</th>\n";
  return $html;
}


// Gets the number of time slots between the beginning and end of the booking
// day.   (This is the normal number on a non-DST transition day)
function get_n_time_slots() : int
{
  global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $resolution;

  $start_first = (($morningstarts * 60) + $morningstarts_minutes) * 60;           // seconds
  $end_last = ((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution;  // seconds
  $end_last = $end_last % SECONDS_PER_DAY;
  if (day_past_midnight())
  {
    $end_last += SECONDS_PER_DAY;
  }

  // Force the result to be an int.  It normally will be, but might not be if, say,
  // $force_resolution is set.
  return intval(($end_last - $start_first)/$resolution);
}


// Returns the HTML for a booking, or a free set of slots
//    $slots    The number of slots occupied
//    $classes  A scalar or array giving the class or classes to be used in the class attribute
//    $title    The value of the title attribute
//    $text     The value of the text to be used in the div
function get_flex_div(int $slots, $classes, ?string $title=null, ?string $text=null) : string
{
  $result = "<div style=\"flex: $slots\"";

  if (isset($classes))
  {
    $value = (is_array($classes)) ? implode(' ', $classes) : $classes;
    $result .= ' class="' . escape_html($value) . '"';
  }

  if (isset($title) && ($title !== ''))
  {
    $result .= ' title="' . escape_html($title) . '"';
  }

  $result .= '>';

  if (isset($text) && ($text !== ''))
  {
    $result .= escape_html($text);
  }

  $result .= '</div>';

  return $result;
}


function week_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight) : string
{
  if ($view_all)
  {
    return multiday_view_all_rooms_innerhtml($view, $view_all, $year, $month, $day, $area_id, $room_id);
  }
  else
  {
    $calendar = new Calendar\CalendarSlotsWeek($view, $view_all, $year, $month, $day, $area_id, $room_id, $timetohighlight);
    return $calendar->innerHTML();
  }
}


function get_date(int $t, string $view) : string
{
  global $datetime_formats;

  if ($view == 'month')
  {
    return datetime_format(['pattern' => 'd'], $t);
  }
  else
  {
    return datetime_format($datetime_formats['view_week_day_month'], $t);
  }
}


function get_day(int $t, string $view) : string
{
  // In the month view use a pattern which will tend to give a narrower result, to save space.
  $pattern = ($view == 'month') ? 'cccccc' : 'ccc';

  return datetime_format(['pattern' => $pattern], $t);
}


function multiday_header_rows(
  string $view,
  int $view_all,
  int $year,
  int $month,
  int $day_start_interval,
  int $area_id,
  int $room_id,
  int $n_days,
  int $start_dow,
  string $label=''
) : array
{
  global $row_labels_both_sides;

  $result = array();
  $n_rows = 2;
  // Loop through twice: one row for the days of the week, the next for the date.
  for ($i = 0; $i < $n_rows; $i++)
  {
    $result[$i] = "<tr>\n";

    // Could use a rowspan here, but we'd need to make sure the sticky cells work
    // and change the JavaScript in refresh.js.php
    $text = ($i == 0) ? '' : $label;
    $first_last_html = '<th class="first_last">' . escape_html($text) . "</th>\n";
    $result[$i] .= $first_last_html;

    $vars = array(
      'view' => 'day',
      'view_all' => $view_all,
      'area' => $area_id,
      'room' => $room_id
    );

    // the standard view, with days along the top and rooms down the side
    for ($j = 0; $j < $n_days; $j++)
    {
      if (is_hidden_day(($j + $start_dow) % DAYS_PER_WEEK))
      {
        continue;
      }
      $vars['page_date'] = format_iso_date($year, $month, $day_start_interval + $j);
      $link = "index.php?" . http_build_query($vars, '', '&');
      $link = multisite($link);
      $t = mktime(12, 0, 0, $month, $day_start_interval + $j, $year);
      $text = ($i === 0) ? get_day($t, $view) : get_date($t, $view);
      $date = new DateTime();
      $date->setTimestamp($t);
      $classes = get_date_classes($date);
      $result[$i] .= '<th' .
        // Add the date for JavaScript.  Only really necessary for the first row in
        // the week view when not viewing all the rooms, but just add it always.
        ' data-date="' . escape_html($date->getISODate()) . '"' .
        ((!empty($classes)) ? ' class="' . implode(' ', $classes) . '"' : '') .
        '><a href="' . escape_html($link) . '">' . escape_html($text) . "</a></th>\n";
    }

    // next line to display rooms on right side
    if ($row_labels_both_sides)
    {
      $result[$i] .= $first_last_html;
    }

    $result[$i] .= "</tr>\n";
  }

  return $result;
}


// TODO: Handle the case where there is more than one booking per slot
function multiday_view_all_rooms_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id) : string
{
  global $row_labels_both_sides, $column_labels_both_ends;
  global $weekstarts;
  global $resolution, $morningstarts, $morningstarts_minutes;
  global $view_all_always_go_to_day_view;

  // It's theoretically possible to display a transposed table with rooms along the top and days
  // down the side.  However it doesn't seem a very useful display and so hasn't yet been implemented.
  // The problem is that the results don't look good whether you have the flex direction as 'row' or
  // 'column'.  If you set it to 'row' the bookings are read from left to right within a day, but from
  // top to bottom within the interval (week/month), so you have to read the display by snaking down
  // the columns, which is potentially confusing.  If you set it to 'column' then the bookings are in
  // order reading straight down the column, but the text within the bookings is usually clipped unless
  // the booking lasts the whole day.  When the days are along the top and the text is clipped you can
  // at least see the first few characters which is useful, but when the days are down the side you only
  // see the top of the line.
  //
  // As a result $days_along_top is always true, but is here so that there can be stubs in the code in
  // case people want a transposed view in future.
  $days_along_top = true;

  $rooms = get_rooms($area_id);
  $n_rooms = count($rooms);

  // Check to see whether there are any rooms in the area
  if ($n_rooms == 0)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>" . get_vocab("no_rooms_for_area") . "</h1></td></tr></tbody>";
  }

  // Calculate/get:
  //    the first day of the interval
  //    how many days there are in it
  //    the day of the week of the first day in the interval
  $time = mktime(12, 0, 0, $month, $day, $year);
  switch ($view)
  {
    case 'week':
      $skipback = day_of_MRBS_week($time);
      $day_start_interval = $day - $skipback;
      $n_days = DAYS_PER_WEEK;
      $start_dow = $weekstarts;
      break;
    case 'month':
      $day_start_interval = 1;
      $n_days = (int) date('t', $time);
      $start_dow = (int) date('N', mktime(12, 0, 0, $month, 1, $year));
      break;
    default:
      trigger_error("Unsupported view '$view'", E_USER_WARNING);
      break;
  }

  // Get the time slots
  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  $start_date = (new DateTime())->setTimestamp(get_start_first_slot($month, $day_start_interval, $year));
  $end_date = (new DateTime())->setTimestamp(get_end_last_slot($month, $day_start_interval + $n_days-1, $year));

  // Get the data.  It's much quicker to do a single SQL query getting all the
  // entries for the interval in one go, rather than doing a query for each day.
  $entries = get_entries_by_area($area_id, $start_date, $end_date);

  // We want to build an array containing all the data we want to show and then spit it out.
  $map = new Map($start_date, $end_date, $resolution);
  $map->addEntries($entries);

  // TABLE HEADER
  $thead = '<thead';

  $slots = get_slots($month, $day_start_interval, $year, $n_days, true);
  if (isset($slots))
  {
    // Add some data to enable the JavaScript to draw the timeline
    $thead .= ' data-slots="' . escape_html(json_encode($slots)) . '"';
    $thead .= ' data-timeline-vertical="' . (($days_along_top) ? 'true' : 'false') . '"';
    $thead .= ' data-timeline-full="true"';
  }

  $thead .= ">\n";

  if ($days_along_top)
  {
    $header_inner_rows = multiday_header_rows($view, $view_all, $year, $month, $day_start_interval, $area_id, $room_id, $n_days, $start_dow);
  }
  else
  {
    // See comment above
    trigger_error("Not yet implemented", E_USER_WARNING);
  }

  $thead .= implode('', $header_inner_rows);
  $thead .= "</thead>\n";

  // Now repeat the header in a footer if required
  $tfoot = ($column_labels_both_ends) ? "<tfoot>\n" . implode('',array_reverse($header_inner_rows)) . "</tfoot>\n" : '';

  // TABLE BODY LISTING BOOKINGS
  $tbody = "<tbody>\n";

  $room_link_vars = array(
      'view'      => $view,
      'view_all'  => 0,
      'page_date' => format_iso_date($year, $month, $day),
      'area'      => $area_id
    );

  if ($days_along_top)
  {
    // the standard view, with days along the top and rooms down the side
    foreach ($rooms as $room)
    {
      $room_id = $room['id'];
      $room_link_vars['room'] = $room_id;
      $tbody .= "<tr>\n";
      $row_label = room_cell_html($room, $room_link_vars);
      $tbody .= $row_label;

      for ($j = 0, $date = clone $start_date; $j < $n_days; $j++, $date->modify('+1 day'))
      {
        if ($date->isHiddenDay())
        {
          continue;
        }

        // Add a classes for weekends and classes
        $classes = get_date_classes($date);

        $tbody .= '<td';
        if (!empty($classes))
        {
          $tbody .= ' class="' . implode(' ', $classes) . '"';
        }
        $tbody .= '>';
        $vars = array(
            'view_all'  => $view_all,
            'page_date' => $date->getISODate(),
            'area'      => $area_id,
            'room'      => $room['id']
          );

        // If there is more than one slot per day, then it can be very difficult to pick
        // out an individual one, which could be just one pixel wide, so we go to the
        // day view first where it's easier to see what you are doing.  Otherwise, we go
        // direct to edit_entry.php if the slot is free, or view_entry.php if it is not.
        // Note: the structure of the cell, with a single link and multiple flex divs,
        // only allows us to direct to the booking if there's only one slot per day.
        if ($view_all_always_go_to_day_view || ($n_time_slots > 1))
        {
          $page = 'index.php';
          $vars['view'] = 'day';
        }
        else
        {
          $vars['view'] = $view;
          $this_slot = $map->slot($room_id, $j, $morning_slot_seconds);
          if (empty($this_slot))
          {
            $page = 'edit_entry.php';
          }
          else
          {
            $page = 'view_entry.php';
            $vars['id'] = $this_slot[0]['id'];
          }
        }

        $link = "$page?" . http_build_query($vars, '', '&');
        $link = multisite($link);
        $tbody .= '<a href="' . escape_html($link) . "\">\n";
        $s = $morning_slot_seconds;
        $slots = 0;
        while ($s <= $evening_slot_seconds)
        {
          $this_slot = $map->slot($room_id, $j, $s);
          if (!empty($this_slot))
          {
            if ($slots > 0)
            {
              $tbody .= get_flex_div($slots, 'free');
            }
            $this_entry = $this_slot[0];
            $n =    $this_entry['n_slots'];
            $text = $this_entry['name'];
            $classes = get_entry_classes($this_entry);
            $tbody .= get_flex_div($n, $classes, $text, $text);
            $slots = 0;
          }
          else
          {
            $n = 1;
            $slots++;
          }
          $s = $s + ($n * $resolution);
        }

        if ($slots > 0)
        {
          $tbody .= get_flex_div($slots, 'free');
        }

        $tbody .= "</a>\n";
        $tbody .= "</td>\n";
      }

      if ($row_labels_both_sides)
      {
        $tbody .= $row_label;
      }
      $tbody .= "</tr>\n";
    }
  }
  else
  {
    // See comment above
    trigger_error("Not yet implemented", E_USER_WARNING);
  }

  $tbody .= "</tbody>\n";
  return $thead . $tfoot . $tbody;
}


// If we're not using periods, construct an array describing the slots to pass to the JavaScript so that
// it can calculate where the timeline should be drawn.  (If we are using periods then the timeline is
// meaningless because we don't know when periods begin and end.)
//    $month, $day, $year   the start of the interval
//    $n_days               the number of days in the interval
//    $day_cells            if the columns/rows represent a full day (as in the week/month all rooms views)
function get_slots(int $month, int $day, int $year, int $n_days=1, bool $day_cells=false) : ?array
{
  global $enable_periods, $morningstarts, $morningstarts_minutes, $resolution;

  if ($enable_periods)
  {
    return null;
  }

  $slots = array();

  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  for ($j = 0; $j < $n_days; $j++)
  {
    $d = $day + $j;

    // If there's more than one day in the interval then don't include the hidden days in the array, because
    // they don't appear in the DOM.  If there's only one day then we've managed to display the hidden day.
    if (($n_days > 1) &&
        is_hidden_day(intval(date('w', mktime($morningstarts, $morningstarts_minutes, 0, $month, $d, $year)))))
    {
      continue;
    }

    $this_day = array();

    if ($day_cells)
    {
      $this_day[] = mktime(0, 0, $morning_slot_seconds, $month, $d, $year);
      // Need to do mktime() again for the end of the slot as we can't assume that the end slot is $resolution
      // seconds after the start of the slot because of the possibility of DST transitions
      $this_day[] = mktime(0, 0, $evening_slot_seconds + $resolution, $month, $d, $year);
    }
    else
    {
      for ($s = $morning_slot_seconds;
           $s <= $evening_slot_seconds;
           $s += $resolution)
      {
        $this_slot = array();
        $this_slot[] = mktime(0, 0, $s, $month, $d, $year);
        // Need to do mktime() again for the end of the slot as we can't assume that the end slot is $resolution
        // seconds after the start of the slot because of the possibility of DST transitions
        $this_slot[] = mktime(0, 0, $s + $resolution, $month, $d, $year);
        $this_day[] = $this_slot;
      }
    }
    $slots[] = $this_day;
  }

  if ($day_cells)
  {
    $slots = array($slots);
  }

  return $slots;
}


// Gets the table header row for the single room month view
function get_table_head() : string
{
  global $weekstarts;

  $html = '';

  // Weekday name header row:
  $html .= "<thead>\n";
  $html .= "<tr>\n";
  for ($i = 0; $i< DAYS_PER_WEEK; $i++)
  {
    $classes = [];
    $dow = ($i + $weekstarts) % DAYS_PER_WEEK;
    if (is_hidden_day($dow))
    {
      $classes[] = 'hidden_day';
    }
    if (is_weekend($dow))
    {
      $classes[] = 'weekend';
    }
    $html .= '<th';
    if (!empty($classes))
    {
      $html .= ' class="' . implode(' ', $classes) . '"';
    }
    $html .= '>' . day_name(($i + $weekstarts)%DAYS_PER_WEEK) . '</th>';
  }
  $html .= "\n</tr>\n";
  $html .= "</thead>\n";

  return $html;
}


function month_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{

  if ($view_all)
  {
    return multiday_view_all_rooms_innerhtml($view, $view_all, $year, $month, $day, $area, $room);
  }
  else
  {
    $calendar = new CalendarMonthOneRoom($view, $view_all, $year, $month, $day, $area, $room);
    return $calendar->innerHTML();
  }
}
