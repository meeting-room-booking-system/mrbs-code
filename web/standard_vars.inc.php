<?php

// $Id$

// Gets the standard variables of $day, $month, $year, $area and $room
// Checks that they are valid and assigns sensible defaults if not

// Get the standard form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
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
    if ($day == 0)
    {
      $day   = date("d");
      $month = date("m");
      $year  = date("Y");   
      break;
    }
  }
}

?>