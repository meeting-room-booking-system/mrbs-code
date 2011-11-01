<?php
// $Id$

// mrbs/week.php - Week-at-a-time view

require_once "defaultincludes.inc";
require_once "mincals.inc";
require_once "functions_table.inc";

// Get non-standard form variables
$timetohighlight = get_form_var('timetohighlight', 'int');

// Check the user is authorised for this page
checkAuthorised();

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : "");

// Section with areas, rooms, minicals.

?>
<div class="screenonly">
  <div id="dwm_header">
<?php

// Get the area and room names (we will need them later for the heading)
$this_area_name = "";
$this_room_name = "";
$this_area_name = sql_query1("SELECT area_name FROM $tbl_area WHERE id=$area AND disabled=0 LIMIT 1");
$this_room_name = sql_query1("SELECT room_name FROM $tbl_room WHERE id=$room AND disabled=0 LIMIT 1");
// The room is invalid if it doesn't exist, or else it has been disabled, either explicitly
// or implicitly because the area has been disabled
$room_invalid = ($this_area_name === -1) || ($this_room_name === -1);

// Show all available areas
echo make_area_select_html('week.php', $area, $year, $month, $day);   
// Show all available rooms in the current area:
echo make_room_select_html('week.php', $area, $room, $year, $month, $day);

// Draw the three month calendars
minicals($year, $month, $day, $area, $room, 'week');
echo "</div>\n";

// End of "screenonly" div
echo "</div>\n";

// Don't continue if this room is invalid, which could be because the area
// has no rooms, or else the room or area has been disabled
if ($room_invalid)
{
  echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
  require_once "trailer.inc";
  exit;
}

// Show area and room:
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

echo "<table class=\"dwm_main\" id=\"week_main\">";
echo week_table_innerhtml($day, $month, $year, $room, $area, $timetohighlight);
echo "</table>\n";

print $before_after_links_html;

show_colour_key();

require_once "trailer.inc"; 
?>
