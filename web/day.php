<?php
namespace MRBS;

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

$inner_html = day_table_innerhtml($day, $month, $year, $room, $area, $timetohighlight);

if ($ajax)
{
  echo $inner_html;
  exit;
}


// Form the room parameter for use in query strings.    We want to preserve room information
// if possible when switching between views
$room_param = (empty($room)) ? "" : "&amp;room=$room";

$timestamp = mktime(12, 0, 0, $month, $day, $year);

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);

echo "<div id=\"dwm_header\" class=\"screenonly\">\n";

// Show all available areas
echo make_area_select_html('day.php', $area, $year, $month, $day);

// Draw the three month calendars
if (!$display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'day');
}

echo "</div>\n";


//y? are year, month and day of yesterday
//t? are year, month and day of tomorrow

// find the last non-hidden day
$d = $day;
do
{  
  $d--;
  $i= mktime(12,0,0,$month,$d,$year);
}
while (is_hidden_day(date("w", $i)) && ($d > $day - 7));  // break the loop if all days are hidden
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

// find the next non-hidden day
$d = $day;
do
{
  $d++;
  $i= mktime(12, 0, 0, $month, $d, $year);
}
while (is_hidden_day(date("w", $i)) && ($d < $day + 7));  // break the loop if all days are hidden
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);



// Show current date and timezone
echo "<div id=\"dwm\">\n";
echo "<h2>" . utf8_strftime($strftime_format['date'], $timestamp) . "</h2>\n";
if ($display_timezone)
{
  echo "<div class=\"timezone\">";
  echo get_vocab("timezone") . ": " . date('T', $timestamp) . " (UTC" . date('O', $timestamp) . ")";
  echo "</div>\n";
}
echo "</div>\n";
  
// Generate Go to day before and after links
$href_before = "day.php?area=$area$room_param&amp;year=$yy&amp;month=$ym&amp;day=$yd";
$href_now    = "day.php?area=$area$room_param";
$href_after  = "day.php?area=$area$room_param&amp;year=$ty&amp;month=$tm&amp;day=$td";

$before_after_links_html = "
<nav class=\"date_nav\">
  <a class=\"date_before\" href=\"$href_before\">" . get_vocab("daybefore") . "</a>
  <a class=\"date_now\" href=\"$href_now\">" . get_vocab("gototoday") . "</a>
  <a class=\"date_after\" href=\"$href_after\">" . get_vocab("dayafter") . "</a>
</nav>\n";

// and output them
echo $before_after_links_html;

echo "<table class=\"dwm_main\" id=\"day_main\" data-resolution=\"$resolution\">\n";
echo $inner_html;
echo "</table>\n";
  
echo $before_after_links_html;

show_colour_key();
// Draw the three month calendars
if ($display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'day');
}


output_trailer();

