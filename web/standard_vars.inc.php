<?php
declare(strict_types=1);
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
if ($view != 'day')
{
  $rooms = new Rooms($area);
  if (!$always_offer_view_all && ($rooms->countVisible() == 1))
  {
    $view_all = 0;
  }
}

// Get the settings (resolution, etc.) for this area
get_area_settings($area);

if (isset($page_date) && ($page_date !== ''))
{
  if (false === ($page_date_split = split_iso_date($page_date)))
  {
    if (is_ajax())
    {
      http_response_code(500);
      trigger_error("Ajax: invalid page_date '$page_date'", E_USER_NOTICE);
      exit;
    }
    trigger_error("Invalid page_date '$page_date'", E_USER_NOTICE);
  }
  else
  {
    list($year, $month, $day) = $page_date_split;
  }
}

$date = new DateTime();

// If we're in kiosk mode and the current time is after the end of the last slot
// then advance to tomorrow - unless we're in periods mode when we don't know
// the actual time of the last slot.
if (isset($kiosk))
{
  if (!$enable_periods && ($date->getTimestamp() > get_end_last_slot($date->getMonth(), $date->getDay(), $date->getYear())))
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
  while ($date->isHiddenDay())
  {
    $date->modify('+1 day');
  }
}

$day = $date->getDay();
$month = $date->getMonth();
$year = $date->getYear();
