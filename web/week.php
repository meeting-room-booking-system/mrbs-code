<?php
namespace MRBS;

// mrbs/week.php - Week-at-a-time view

require "defaultincludes.inc";
require_once "mincals.inc";
require_once "functions_table.inc";

// Get non-standard form variables
$timetohighlight = get_form_var('timetohighlight', 'int');
$ajax = get_form_var('ajax', 'int');

// Check the user is authorised for this page
if (!checkAuthorised($just_check = $ajax))
{
  exit;
}

$inner_html = week_table_innerhtml($day, $month, $year, $room, $area, $timetohighlight);

if ($ajax)
{
  echo $inner_html;
  exit;
}


// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);

// Section with areas, rooms, minicals.

echo "<div id=\"dwm_header\" class=\"screenonly\">\n";

// Show all available areas
echo make_area_select_html('week.php', $area, $year, $month, $day);   
// Show all available rooms in the current area:
echo make_room_select_html('week.php', $area, $room, $year, $month, $day);

// Draw the three month calendars
if (!$display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'week');
}

echo "</div>\n";

// Show area and room:
// Get the area and room names
$this_area_name = get_area_name($area);
$this_room_name = get_room_name($room);
// The room is invalid if it doesn't exist, or else it has been disabled, either explicitly
// or implicitly because the area has been disabled
if (!isset($this_area_name) || ($this_area_name === FALSE))
{
  $this_area_name = '';
}
if (!isset($this_room_name) || ($this_room_name === FALSE))
{
  $this_room_name = '';
}
echo "<div id=\"dwm\">\n";
echo "<h2>" . htmlspecialchars("$this_area_name - $this_room_name") . "</h2>\n";
echo "</div>\n";

//y? are year, month and day of the previous week.
//t? are year, month and day of the next week.

$i= mktime(12,0,0,$month,$day-7,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(12,0,0,$month,$day+7,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

// Show Go to week before and after links
$href_before = "week.php?area=$area&amp;room=$room&amp;year=$yy&amp;month=$ym&amp;day=$yd";
$href_now    = "week.php?area=$area&amp;room=$room";
$href_after  = "week.php?area=$area&amp;room=$room&amp;year=$ty&amp;month=$tm&amp;day=$td";

$before_after_links_html = "
<nav class=\"date_nav\">
  <a class=\"date_before\" href=\"$href_before\">" . get_vocab("weekbefore") . "</a>
  <a class=\"date_now\" href=\"$href_now\">" . get_vocab("gotothisweek") . "</a>
  <a class=\"date_after\" href=\"$href_after\">" . get_vocab("weekafter") . "</a>
</nav>\n";

print $before_after_links_html;

echo "<table class=\"dwm_main\" id=\"week_main\" data-resolution=\"$resolution\">";
echo $inner_html;
echo "</table>\n";

print $before_after_links_html;

show_colour_key();
// Draw the three month calendars
if ($display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'week');
}

output_trailer(); 

