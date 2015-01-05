<?php
// $Id$

// mrbs/week.php - Week-at-a-time view

require "defaultincludes.inc";
require_once "mincals.inc";
require_once "functions_table.inc";

// Get non-standard form variables
$timetohighlight = get_form_var('timetohighlight', 'int');
$ajax = get_form_var('ajax', 'int');

$inner_html = week_table_innerhtml($day, $month, $year, $room, $area, $timetohighlight);

if ($ajax)
{
  if (checkAuthorised(TRUE))
  {
    echo $inner_html;
  }
  exit;
}

// Check the user is authorised for this page
checkAuthorised();

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : "");

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
$before_after_links_html = "
<div class=\"screenonly\">
  <div class=\"date_nav\">
    <div class=\"date_before\">
      <a href=\"week.php?year=$yy&amp;month=$ym&amp;day=$yd&amp;area=$area&amp;room=$room\">
          &lt;&lt;&nbsp;".get_vocab("weekbefore")."
      </a>
    </div>
    <div class=\"date_now\">
      <a href=\"week.php?area=$area&amp;room=$room\">
          ".get_vocab("gotothisweek")."
      </a>
    </div>
    <div class=\"date_after\">
      <a href=\"week.php?year=$ty&amp;month=$tm&amp;day=$td&amp;area=$area&amp;room=$room\">
          ".get_vocab("weekafter")."&nbsp;&gt;&gt;
      </a>
    </div>
  </div>
</div>
";

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

