<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Calendar\CalendarMonthOneRoom;
use MRBS\Calendar\CalendarMultidayMultiroom;

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


function week_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight) : string
{
  if ($view_all)
  {
    $calendar = new CalendarMultidayMultiroom($view, $view_all, $year, $month, $day, $area_id, $room_id);
  }
  else
  {
    $calendar = new Calendar\CalendarMultislotWeek($view, $view_all, $year, $month, $day, $area_id, $room_id, $timetohighlight);
  }
  return $calendar->innerHTML();
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
