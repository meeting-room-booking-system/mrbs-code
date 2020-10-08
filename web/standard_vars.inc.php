<?php
namespace MRBS;

// Gets the standard variables of $day, $month, $year, $area and $room
// Checks that they are valid and assigns sensible defaults if not

// Get the standard form variables
$page_date = get_form_var('page_date', 'string');
$view = get_form_var('view', 'string', isset($default_view) ? $default_view : 'day');
$view_all = get_form_var('view_all', 'int', empty($default_view_all) ? 0 : 1);  // Whether to view all rooms
$year = get_form_var('year', 'int');
$month = get_form_var('month', 'int');
$day = get_form_var('day', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');

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

if (isset($page_date))
{
  list($year, $month, $day) = split_iso_date($page_date);
}

// If we don't know the right date then use today's date
if (empty($day) or empty($month) or empty($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}
else
{
  // Make the date valid if day is more than number of days in month:
  while (!checkdate($month, $day, $year))
  {
    $day--;
    if ($day <= 0)
    {
      $day   = date("d");
      $month = date("m");
      $year  = date("Y");
      break;
    }
  }
}

// Advance to the next non-hidden day
if (!empty($hidden_days) &&     // Use !empty in case $hidden_days is not set
    (count($hidden_days) < 7))  // Avoid an infinite loop
{
  $date = new DateTime();
  $date->setDate($year, $month, $day);
  while (in_array($date->format('w'), $hidden_days))
  {
    $date->add(new \DateInterval('P1D'));
  }
  $day = $date->getDay();
  $month = $date->getMonth();
  $year = $date->getYear();
}
