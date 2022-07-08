<?php
namespace MRBS;

// Gets the standard variables of $day, $month, $year, $area and $room
// Checks that they are valid and assigns sensible defaults if not

// Get the standard form variables
use DateInterval;

$page_date = get_form_var('page_date', 'string');
$view = get_form_var('view', 'string', $default_view ?? 'day');
$view_all = get_form_var('view_all', 'int', empty($default_view_all) ? 0 : 1);  // Whether to view all rooms
$year = get_form_var('year', 'int');
$month = get_form_var('month', 'int');
$day = get_form_var('day', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$kiosk = get_form_var('kiosk', 'string');

if (empty($area))
{
  $area = get_default_area();
}

if (empty($room))
{
  $room = get_default_room($area);
}

// No point in showing all the rooms if there's only one of them.  Show
// the normal view (with time slots) instead
if (($view != 'day') && count(get_rooms($area)) == 1)
{
  $view_all = false;
}

// Get the settings (resolution, etc.) for this area
get_area_settings($area);

if (isset($page_date) && ($page_date !== ''))
{
  if (false === ($page_date_split = split_iso_date($page_date)))
  {
    trigger_error("Invalid page_date '$page_date'", E_USER_NOTICE);
  }
  else
  {
    list($year, $month, $day) = $page_date_split;
  }
}

$date = new DateTime();

// If we're in kiosk mode and the current time is after the end of the last slot
// then advance to tomorrow.
if (isset($kiosk))
{
  if ($date->getTimestamp() > get_end_last_slot($date->getMonth(), $date->getDay(), $date->getYear()))
  {
    $date->add(new DateInterval('P1D'));
  }
}
// If we are not in kiosk mode and have been given a date then use that
elseif (!empty($day) && !empty($month) && !empty($year))
{
  $date->setDate($year, $month, $day);
}

// Advance to the next non-hidden day
if (!empty($hidden_days) &&     // Use !empty in case $hidden_days is not set
    (count($hidden_days) < 7))  // Avoid an infinite loop
{
  while (in_array($date->format('w'), $hidden_days))
  {
    $date->add(new DateInterval('P1D'));
  }
}

$day = $date->getDay();
$month = $date->getMonth();
$year = $date->getYear();
