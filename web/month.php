<?php
namespace MRBS;

// mrbs/month.php - Month-at-a-time view

require "defaultincludes.inc";
require_once "mincals.inc";
require_once "functions_table.inc";



$debug_flag = get_form_var('debug_flag', 'int');
$ajax = get_form_var('ajax', 'int');

// Check the user is authorised for this page
if (!checkAuthorised($just_check = $ajax))
{
  exit;
}
$user = getUserName();

$inner_html = month_table_innerhtml($day, $month, $year, $room, $area);

if ($ajax)
{
  echo $inner_html;
  exit;
}


// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);


// Note $room will be 0 if there are no rooms; this is checked for below.

// Month view start time. This ignores morningstarts/eveningends because it
// doesn't make sense to not show all entries for the day, and it messes
// things up when entries cross midnight.
$month_start = mktime(0, 0, 0, $month, 1, $year);

if ($enable_periods)
{
  $resolution = 60;
  $morningstarts = 12;
  $morningstarts_minutes = 0;
  $eveningends = 12;
  $eveningends_minutes = count($periods)-1;
}




// Section with areas, rooms, minicals.
echo "<div id=\"dwm_header\" class=\"screenonly\">\n";

// Get the area and room names (we will need them later for the heading)
$this_area_name = get_area_name($area);
$this_room_name = get_room_name($room);
// The room is invalid if it doesn't exist, or else it has been disabled, either explicitly
// or implicitly because the area has been disabled
$room_invalid = !isset($this_area_name) || ($this_area_name === FALSE) ||
                !isset($this_room_name) || ($this_room_name === FALSE);
                          
// Show all available areas
echo make_area_select_html('month.php', $area, $year, $month, $day);  
// Show all available rooms in the current area:
echo make_room_select_html('month.php', $area, $room, $year, $month, $day);
    
// Draw the three month calendars
if (!$display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'month');
}

echo "</div>\n";


// Don't continue if this room is invalid, which could be because the area
// has no rooms, or else the room or area has been disabled
if ($room_invalid)
{
  echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
  print_footer();
  exit;
}

// Show Month, Year, Area, Room header:
echo "<div id=\"dwm\">\n";
echo "<h2>" . utf8_strftime($strftime_format['monthyear'], $month_start)
  . " - " . htmlspecialchars("$this_area_name - $this_room_name") . "</h2>\n";
echo "</div>\n";

// Show Go to month before and after links
//y? are year and month and day of the previous month.
//t? are year and month and day of the next month.
//c? are year and month of this month.   But $cd is the day that was passed to us.

$i= mktime(12,0,0,$month-1,1,$year);
$yy = date("Y",$i);
$ym = date("n",$i);
$yd = $day;
while (!checkdate($ym, $yd, $yy) && ($yd > 1))
{
  $yd--;
}

$i= mktime(12,0,0,$month+1,1,$year);
$ty = date("Y",$i);
$tm = date("n",$i);
$td = $day;
while (!checkdate($tm, $td, $ty) && ($td > 1))
{
  $td--;
}

$cy = date("Y");
$cm = date("m");
$cd = $day;    // preserve the day information
while (!checkdate($cm, $cd, $cy) && ($cd > 1))
{
  $cd--;
}

$href_before = "month.php?area=$area&amp;room=$room&amp;year=$yy&amp;month=$ym&amp;day=$yd";
$href_now    = "month.php?area=$area&amp;room=$room&amp;year=$cy&amp;month=$cm&amp;day=$cd";
$href_after  = "month.php?area=$area&amp;room=$room&amp;year=$ty&amp;month=$tm&amp;day=$td";

$before_after_links_html = "
<nav class=\"date_nav\">
  <a class=\"date_before\" href=\"$href_before\">" . get_vocab("monthbefore") . "</a>
  <a class=\"date_now\" href=\"$href_now\">" . get_vocab("gotothismonth") . "</a>
  <a class=\"date_after\" href=\"$href_after\">" . get_vocab("monthafter") . "</a>
</nav>\n";

echo $before_after_links_html;

if ($debug_flag)
{
  $days_in_month = date("t", $month_start);
  $month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
  // What column the month starts in: 0 means $weekstarts weekday.
  $weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
  echo "<p>DEBUG: month=$month year=$year start=$weekday_start range=$month_start:$month_end</p>\n";
}

echo "<table class=\"dwm_main\" id=\"month_main\">\n";
echo $inner_html;
echo "</table>\n";

echo $before_after_links_html;
show_colour_key();

// Draw the three month calendars
if ($display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'month');
}

print_footer();

