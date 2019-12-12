<?php
namespace MRBS;

// Gets the standard variables of $day, $month, $year, $area and $room
// Checks that they are valid and assigns sensible defaults if not

// Get the standard form variables
$page_date = get_form_var('page_date', 'string');
$view = get_form_var('view', 'string', isset($default_view) ? $default_view : 'day');
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
