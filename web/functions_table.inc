<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Calendar\CalendarMonthOneRoom;
use MRBS\Calendar\CalendarMultidayMultiroom;

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


function week_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight) : string
{
  if ($view_all)
  {
    $calendar = new CalendarMultidayMultiroom($view, $view_all, $year, $month, $day, $area_id, $room_id);
  }
  else
  {
    $calendar = new Calendar\CalendarSlotsWeek($view, $view_all, $year, $month, $day, $area_id, $room_id, $timetohighlight);
  }
  return $calendar->innerHTML();
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


function month_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{
  if ($view_all)
  {
    $calendar = new CalendarMultidayMultiroom($view, $view_all, $year, $month, $day, $area, $room);
  }
  else
  {
    $calendar = new CalendarMonthOneRoom($view, $view_all, $year, $month, $day, $area, $room);
  }
  return $calendar->innerHTML();
}
