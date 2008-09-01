<?php
// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mincals.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$debug_flag = get_form_var('debug_flag', 'int');

if (empty($debug_flag))
{
  $debug_flag = 0;
}

// If we dont know the right date then make it up 
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}
else
{
  // Make the date valid if day is more then number of days in month
  while (!checkdate(intval($month), intval($day), intval($year)))
  {
    $day--;
  }
}
if (empty($area))
{
  $area = get_default_area();
}

// print the page header
print_header($day, $month, $year, $area);

$format = "Gi";
if ( $enable_periods )
{
  $format = "i";
  $resolution = 60;
  $morningstarts = 12;
  $morningstarts_minutes = 0;
  $eveningends = 12;
  $eveningends_minutes = count($periods)-1;
}

// ensure that $morningstarts_minutes defaults to zero if not set
if ( empty( $morningstarts_minutes ) )
{
  $morningstarts_minutes=0;
}

// Define the start and end of each day in a way which is not affected by
// daylight saving...
// dst_change:
// -1 => no change
//  0 => entering DST
//  1 => leaving DST
$dst_change = is_dst($month,$day,$year);
$am7=mktime($morningstarts,$morningstarts_minutes,0,
            $month,$day,$year,is_dst($month,$day,$year,$morningstarts));
$pm7=mktime($eveningends,$eveningends_minutes,0,
            $month,$day,$year,is_dst($month,$day,$year,$eveningends));

?>
<div class="screenonly">
  <div id="dwm_header">
      <div id="dwm_areas">
<?php

// Show all avaliable areas
echo "<h3>".get_vocab("areas")."</h3>";

// need to show either a select box or a normal html list,
// depending on the settings in config.inc.php
if ($area_list_format == "select")
{
  echo make_area_select_html('day.php', $area, $year, $month, $day);
}
else
{
  // show the standard html list
 
  $sql = "select id, area_name from $tbl_area order by area_name";
  $res = sql_query($sql);
  if ($res) 
  { 
    echo ("<ul>\n");
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      echo "<li><a href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$row['id']."\">";
      echo "<span" . (($row['id'] == $area) ? ' class="current"' : '') . ">";
      echo htmlspecialchars($row['area_name']) . "</span></a></li>\n";
    }  
    echo ("</ul>\n");
  }
}
echo "</div>\n";

// Draw the three month calendars
minicals($year, $month, $day, $area, '', 'day');
echo "</div>";

?>
</div>
<?php

//y? are year, month and day of yesterday
//t? are year, month and day of tomorrow

$i= mktime(12,0,0,$month,$day-1,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(12,0,0,$month,$day+1,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

//We want to build an array containing all the data we want to show
//and then spit it out. 

//Get all appointments for today in the area that we care about
//Note: The predicate clause 'start_time < ...' is an equivalent but simpler
//form of the original which had 3 BETWEEN parts. It selects all entries which
//occur on or cross the current day.
$sql = "SELECT $tbl_room.id AS room_id, start_time, end_time, name, $tbl_entry.id AS entry_id, type,
        $tbl_entry.description AS entry_description
   FROM $tbl_entry, $tbl_room
   WHERE $tbl_entry.room_id = $tbl_room.id
   AND area_id = $area
   AND start_time < $pm7 AND end_time > $am7";

$res = sql_query($sql);
if (! $res)
{
  fatal_error(0, sql_error());
}
for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
{
  // Each row we've got here is an appointment.
  //  row['room_id'] = Room ID
  //  row['start_time'] = start time
  //  row['end_time'] = end time
  //  row['name'] = short description
  //  row['entry_id'] = id of this booking
  //  row['type'] = type (internal/external)
  //  row['entry_description'] = description

  // $today is a map of the screen that will be displayed
  // It looks like:
  //     $today[Room ID][Time][id]
  //                          [color]
  //                          [data]
  //                          [long_descr]
  //                          [slots]
  
  // slots records the duration of the booking in number of time slots.
	// Used to calculate how high to make the block used for clipping
	// overflow descriptions.

  // Fill in the map for this meeting. Start at the meeting start time,
  // or the day start time, whichever is later. End one slot before the
  // meeting end time (since the next slot is for meetings which start then),
  // or at the last slot in the day, whichever is earlier.
  // Time is of the format HHMM without leading zeros.
  //
  // Note: int casts on database rows for max may be needed for PHP3.
  // Adjust the starting and ending times so that bookings which don't
  // start or end at a recognized time still appear.
  $start_t = max(round_t_down($row['start_time'], $resolution, $am7), $am7);
  $end_t = min(round_t_up($row['end_time'], $resolution, $am7) - $resolution, $pm7);
  for ($t = $start_t; $t <= $end_t; $t += $resolution)
  {
    $today[$row['room_id']][date($format,$t)]["id"]    = $row['entry_id'];
    $today[$row['room_id']][date($format,$t)]["color"] = $row['type'];
    $today[$row['room_id']][date($format,$t)]["data"]  = "";
    $today[$row['room_id']][date($format,$t)]["long_descr"]  = "";
    // Calculate the number of slots.   Because $end_t ends one slot before the meeting
    // end time, you need to add 1 to get the number of slots - unless $end_t is the 
    // last slot in the day.
    $s = intval(($end_t - $start_t)/$resolution) + 1;
    if ($end_t == $pm7)
    {
      $s = $s - 1;
    }
    $today[$row['room_id']][date($format,$t)]["slots"] = $s;
  }

  // Show the name of the booker in the first segment that the booking
  // happens in, or at the start of the day if it started before today.
  if ($row['start_time'] < $am7)
  {
    $today[$row['room_id']][date($format,$am7)]["data"] = $row['name'];
    $today[$row['room_id']][date($format,$am7)]["long_descr"] = $row['entry_description'];
  }
  else
  {
    $today[$row['room_id']][date($format,$start_t)]["data"] = $row['name'];
    $today[$row['room_id']][date($format,$start_t)]["long_descr"] = $row['entry_description'];
  }
}

if ($debug_flag) 
{
  echo "<p>DEBUG:<pre>\n";
  echo "\$dst_change = $dst_change\n";
  echo "\$am7 = $am7 or " . date($format,$am7) . "\n";
  echo "\$pm7 = $pm7 or " . date($format,$pm7) . "\n";
  if (gettype($today) == "array")
  {
    while (list($w_k, $w_v) = each($today))
    {
      while (list($t_k, $t_v) = each($w_v))
      {
        while (list($k_k, $k_v) = each($t_v))
        {
          echo "d[$w_k][$t_k][$k_k] = '$k_v'\n";
        }
      }
    }
  }
  else
  {
    echo "today is not an array!\n";
  }
  echo "</pre><p>\n";
}

// We need to know what all the rooms area called, so we can show them all
// pull the data from the db and store it. Convienently we can print the room
// headings and capacities at the same time

$sql = "select room_name, capacity, id, description from $tbl_room where area_id=$area order by 1";

$res = sql_query($sql);

// It might be that there are no rooms defined for this area.
// If there are none then show an error and don't bother doing anything
// else
if (! $res)
{
  fatal_error(0, sql_error());
}
if (sql_count($res) == 0)
{
  echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
  sql_free($res);
}
else
{
  // Show current date
  echo "<h2 id=\"dwm\">" . utf8_strftime("%A %d %B %Y", $am7) . "</h2>\n";
  // Generate Go to day before and after links

  $before_after_links_html = "
<div class=\"screenonly\">
  <div class=\"date_nav\">
    <div class=\"date_before\">
      <a href=\"day.php?year=$yy&amp;month=$ym&amp;day=$yd&amp;area=$area\">&lt;&lt;". get_vocab("daybefore") ."
      </a>
    </div>
    <div class=\"date_now\">
      <a href=\"day.php?area=$area\">" . get_vocab("gototoday") . "</a>
    </div>
    <div class=\"date_after\">
      <a href=\"day.php?year=$ty&amp;month=$tm&amp;day=$td&amp;area=$area\">". get_vocab("dayafter") . "&gt;&gt;
      </a>
    </div>
  </div>
</div>\n";

  // and output them
  print $before_after_links_html;

  // Include the active cell content management routines.
  // Must be included before the beginnning of the main table.
  if ($javascript_cursor) // If authorized in config.inc.php, include the javascript cursor management.
  {
    echo "<script type=\"text/javascript\" src=\"xbLib.js\"></script>\n";
    echo "<script type=\"text/javascript\">\n";
    echo "//<![CDATA[\n";
    echo "InitActiveCell("
      . ($show_plus_link ? "true" : "false") . ", "
      . "true, "
      . ((FALSE != $times_right_side) ? "true" : "false") . ", "
      . "\"$highlight_method\", "
      . "\"" . get_vocab("click_to_reserve") . "\""
      . ");\n";
    echo "//]]>\n";
    echo "</script>\n";
  }

  // This is where we start displaying stuff
  echo "<table class=\"dwm_main\" id=\"day_main\">\n";
  
  // Table header giving room names
  echo "<thead>\n";
  echo "<tr>\n";
  echo "<th class=\"first_last\">".($enable_periods ? get_vocab("period") : get_vocab("time")).":</th>";

  $room_column_width = (int)(95 / sql_count($res));
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    echo "<th style=\"width: $room_column_width%\">
            <a href=\"week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=".$row['id']."\"
            title=\"" . get_vocab("viewweek") . " &#10;&#10;".$row['description']."\">"
      . htmlspecialchars($row['room_name']) . ($row['capacity'] > 0 ? "(".$row['capacity'].")" : "") . "</a></th>";
    $rooms[] = $row['id'];
  }

  // next line to display times on right side
  if ( FALSE != $times_right_side )
  {
    echo "<th class=\"first_last\">". ( $enable_periods  ? get_vocab("period") : get_vocab("time") )
      .":</th>";
  }
  echo "</tr>\n";
  echo "</thead>\n";
  
  
  // Table body listing bookings
  echo "<tbody>\n";
  
  // This is the main bit of the display
  // We loop through time and then the rooms we just got

  // if the today is a day which includes a DST change then use
  // the day after to generate timesteps through the day as this
  // will ensure a constant time step
  
  // URL for highlighting a time. Don't use REQUEST_URI or you will get
  // the timetohighlight parameter duplicated each time you click.
  $hilite_url="day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;timetohighlight";
  
  ( $dst_change != -1 ) ? $j = 1 : $j = 0;
   
  $row_class = "even_row";
  for (
       $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
       $t < mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
       $t += $resolution, $row_class = ($row_class == "even_row")?"odd_row":"even_row"
      )
  {
    // convert timestamps to HHMM format without leading zeros
    $time_t = date($format, $t);

    // Show the time linked to the URL for highlighting that time
    echo "<tr>";
    tdcell("times", 1);
    echo "<div class=\"celldiv1\">\n";
    if( $enable_periods )
    {
      $time_t_stripped = preg_replace( "/^0/", "", $time_t );
      echo "<a href=\"$hilite_url=$time_t\"  title=\""
        . get_vocab("highlight_line") . "\">"
        . $periods[$time_t_stripped] . "</a>\n";
    }
    else
    {
      echo "<a href=\"$hilite_url=$time_t\" title=\""
        . get_vocab("highlight_line") . "\">"
        . utf8_strftime(hour_min_format(),$t) . "</a>\n";
    }
    echo "</div></td>\n";

    // Loop through the list of rooms we have for this area
    while (list($key, $room) = each($rooms))
    {
      if(isset($today[$room][$time_t]["id"]))
      {
        $id    = $today[$room][$time_t]["id"];
        $color = $today[$room][$time_t]["color"];
        $descr = htmlspecialchars($today[$room][$time_t]["data"]);
        $long_descr = htmlspecialchars($today[$room][$time_t]["long_descr"]);
        $slots = $today[$room][$time_t]["slots"];
      }
      else
      {
        unset($id);
        $slots = 1;
      }

      // $c is the colour of the cell that the browser sees. Zebra stripes normally,
      // row_highlight if we're highlighting that line and the appropriate colour if
      // it is booked (determined by the type).
      // We tell if its booked by $id having something in it
      if (isset($id))
      {
        $c = $color;
      }
      else if (isset($timetohighlight) && ($time_t == $timetohighlight))
      {
        $c = "row_highlight";
      }
      else
      {
        $c = $row_class; // Use the default color class for the row.
      }
      
      // Don't put in a <td> cell if the slot is booked and there's no description.
		  // This would mean that it's the second or subsequent slot of a booking and so the
		  // <td> for the first slot would have had a rowspan that extended the cell down for
		  // the number of slots of the booking.
		
		if (!(isset($id) && ($descr == ""))) 
      {
        tdcell($c, $slots);
  
        // If the room isn't booked then allow it to be booked
        if (!isset($id))
        {
          $hour = date("H",$t);
          $minute  = date("i",$t);
          echo "<div class=\"celldiv1\">\n";  // a bookable slot is only one unit high
  
          if ($javascript_cursor)
          {
            echo "<script type=\"text/javascript\">\n";
            echo "//<![CDATA[\n";
            echo "BeginActiveCell();\n";
            echo "//]]>\n";
            echo "</script>\n";
          }
          
          if( $enable_periods )
          {
            echo "<a href=\"edit_entry.php?area=$area&amp;room=$room&amp;period=$time_t_stripped&amp;year=$year&amp;month=$month&amp;day=$day\">\n";
            echo "<img class=\"new_booking\" src=\"new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
            echo "</a>\n";
          }
          else
          {
            echo "<a href=\"edit_entry.php?area=$area&amp;room=$room&amp;hour=$hour&amp;minute=$minute&amp;year=$year&amp;month=$month&amp;day=$day\">\n";
            echo "<img class=\"new_booking\" src=\"new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
            echo "</a>\n";
          }
          
          if ($javascript_cursor)
          {
            echo "<script type=\"text/javascript\">\n";
            echo "//<![CDATA[\n";
            echo "EndActiveCell();\n";
            echo "//]]>\n";
            echo "</script>\n";
          }
          echo "</div>\n";
        }
        else                 // if it is booked then show the booking
        {    
	        echo "<div class=\"celldiv" . $slots . "\">\n";		// we want clipping of overflow
          echo "  <a href=\"view_entry.php?id=$id&amp;area=$area&amp;day=$day&amp;month=$month&amp;year=$year\" title=\"$long_descr\">$descr</a>\n";
          echo "</div>\n";
        }
        echo "</td>\n";
      }
    }
    // next lines to display times on right side
    if ( FALSE != $times_right_side )
    {
      tdcell("times", 1);
      echo "<div class=\"celldiv1\">\n";
      if ( $enable_periods )
      {
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        echo "<a href=\"$hilite_url=$time_t\"  title=\""
          . get_vocab("highlight_line") . "\">"
          . $periods[$time_t_stripped] . "</a>\n";
      }
      else
      {
        echo "<a href=\"$hilite_url=$time_t\" title=\""
          . get_vocab("highlight_line") . "\">"
          . utf8_strftime(hour_min_format(),$t) . "</a>\n";
      }
      echo "</div></td>\n";
    }

    echo "</tr>\n";
    reset($rooms);
  }
  echo "</tbody>\n";
  echo "</table>\n";

  print $before_after_links_html;

  show_colour_key();
}

include "trailer.inc";
?>
