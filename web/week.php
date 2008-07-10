<?php
// $Id$

// mrbs/week.php - Week-at-a-time view

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mincals.inc";

// Get form variables
$debug_flag = get_form_var('debug_flag', 'int');
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');

if (empty($debug_flag))
{
  $debug_flag = 0;
}

$num_of_days=7; //could also pass this in as a parameter or whatever

// If we don't know the right date then use today:
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}
else
{
  // Make the date valid if day is more then number of days in month:
  while (!checkdate($month, $day, $year))
  {
    $day--;
  }
}

// Set the date back to the previous $weekstarts day (Sunday, if 0):
$time = mktime(12, 0, 0, $month, $day, $year);
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
{
  $time -= $weekday * 86400;
  $day   = date("d", $time);
  $month = date("m", $time);
  $year  = date("Y", $time);
}

if (empty($area))
{
  $area = get_default_area();}
if (empty($room))
{
  $room = get_default_room($area);
}
// Note $room will be 0 if there are no rooms; this is checked for below.

// print the page header
print_header($day, $month, $year, $area);

$format = "Gi";
if( $enable_periods )
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

// Define the start and end of each day of the week in a way which is not
// affected by daylight saving...
for ($j = 0; $j<=($num_of_days-1); $j++)
{
  // are we entering or leaving daylight saving
  // dst_change:
  // -1 => no change
  //  0 => entering DST
  //  1 => leaving DST
  $dst_change[$j] = is_dst($month,$day+$j,$year);
  $am7[$j]=mktime($morningstarts,$morningstarts_minutes,0,
                  $month,$day+$j,$year,is_dst($month,
                                              $day+$j,
                                              $year,
                                              $morningstarts));
  $pm7[$j]=mktime($eveningends,$eveningends_minutes,0,
                  $month,$day+$j,$year,is_dst($month,
                                              $day+$j,
                                              $year,
                                              $eveningends));
}

// Section with areas, rooms, minicals.

?>
<div class="screenonly">
  <div id="dwm_header">
<?php

$this_area_name = "";
$this_room_name = "";

// Show all areas
echo "<div id=\"dwm_areas\"><h3>".get_vocab("areas")."</h3>";

// show either a select box or the normal html list
if ($area_list_format == "select")
{
  echo make_area_select_html('week.php', $area,
                             $year, $month, $day);
  $this_area_name = sql_query1("select area_name
                                from $tbl_area where id=$area");
  $this_room_name = sql_query1("select room_name
                                from $tbl_room where id=$room");
}
else
{
 $sql = "select id, area_name from $tbl_area order by area_name";
  $res = sql_query($sql);
  if ($res)
  {
    echo "<ul>\n";
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      echo "<li><a href=\"month.php?year=$year&amp;month=$month&amp;area=$row[0]\">";
		echo "<span";
		if ($row['id'] == $area)
		{
		  $this_area_name = htmlspecialchars($row['area_name']);
		  echo ' class="current"';
		}
		echo ">";
      echo htmlspecialchars($row['area_name']) . "</span></a></li>\n";
    }
	 echo "</ul>\n";
  }

} // end area display if

echo "</div>\n";

// Show all rooms in the current area
echo "<div id=\"dwm_rooms\"><h3>".get_vocab("rooms")."</h3>";

// should we show a drop-down for the room list, or not?
if ($area_list_format == "select")
{
  echo make_room_select_html('week.php', $area, $room,
                             $year, $month, $day);
}
else
{
  $sql = "select id, room_name from $tbl_room
          where area_id=$area order by room_name";
  $res = sql_query($sql);
  if ($res)
  {
    echo "<ul>\n";
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      echo "<li><a href=\"month.php?year=$year&amp;month=$month&amp;area=$area&amp;room=".$row['id']."\">";
		echo "<span";
		if ($row['id'] == $room)
		{
		  $this_room_name = htmlspecialchars($row['room_name']);
		  echo ' class="current"';
		}
		echo ">";
      echo htmlspecialchars($row['room_name']) . "</span></a></li>\n";
    }
	 echo "</ul>\n";
  }
} // end select if

echo "</div>\n";

// Draw the three month calendars
minicals($year, $month, $day, $area, $room, 'week');
echo "</div>\n";

// End of "screenonly" div
echo "</div>\n";

// Don't continue if this area has no rooms:
if ($room <= 0)
{
  echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
  include "trailer.inc";
  exit;
}

// Show area and room:
echo "<h2 align=center>$this_area_name - $this_room_name</h2>\n";

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
echo "<div class=\"screenonly\">
  <table width=\"100%\">
    <tr>
      <td>
        <a href=\"week.php?year=$yy&amp;month=$ym&amp;day=$yd&amp;area=$area&amp;room=$room\">
          &lt;&lt; ".get_vocab("weekbefore")."
        </a>
      </td>
      <td align=\"center\">
        <a href=\"week.php?area=$area&amp;room=$room\">
          ".get_vocab("gotothisweek")."
        </a>
      </td>
      <td align=\"right\">
        <a href=\"week.php?year=$ty&amp;month=$tm&amp;day=$td&amp;area=$area&amp;room=$room\">
          ".get_vocab("weekafter")."&gt;&gt;
        </a>
      </td>
    </tr>
  </table>
</div>
";

//Get all appointments for this week in the room that we care about
// row['start_time'] = Start time
// row['end_time'] = End time
// row['type'] = Entry type
// row['name'] = Entry name (brief description)
// row['id'] = Entry ID
// row['description'] = Complete description
// This data will be retrieved day-by-day
for ($j = 0; $j<=($num_of_days-1) ; $j++)
{
  $sql = "SELECT start_time, end_time, type, name, id, description
          FROM $tbl_entry
          WHERE room_id = $room
          AND start_time <= $pm7[$j] AND end_time > $am7[$j]";

  // Each row returned from the query is a meeting. Build an array of the
  // form:  d[weekday][slot][x], where x = id, color, data, long_desc.
  // [slot] is based at 000 (HHMM) for midnight, but only slots within
  // the hours of interest (morningstarts : eveningends) are filled in.
  // [id], [data] and [long_desc] are only filled in when the meeting
  // should be labeled,  which is once for each meeting on each weekday.
  // Note: weekday here is relative to the $weekstarts configuration variable.
  // If 0, then weekday=0 means Sunday. If 1, weekday=0 means Monday.

  if ($debug_flag)
  {
    echo "<br>DEBUG: query=$sql\n";
  }
  $res = sql_query($sql);
  if (! $res)
  {
    echo sql_error();
  }
  else
  {
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      if ($debug_flag)
      {
        echo "<br>DEBUG: result $i, id ".$row['id'].", starts ".$row['start_time'],", ends ".$row['end_time']."\n";
      }

      // $d is a map of the screen that will be displayed
      // It looks like:
      //     $d[Day][Time][id]
      //                  [color]
      //                  [data]
      // where Day is in the range 0 to $num_of_days. 
      
      // Fill in the map for this meeting. Start at the meeting start time,
      // or the day start time, whichever is later. End one slot before the
      // meeting end time (since the next slot is for meetings which start then),
      // or at the last slot in the day, whichever is earlier.
      // Note: int casts on database rows for max may be needed for PHP3.
      // Adjust the starting and ending times so that bookings which don't
      // start or end at a recognized time still appear.
 
      $start_t = max(round_t_down($row['start_time'],
                                  $resolution, $am7[$j]), $am7[$j]);
      $end_t = min(round_t_up($row['end_time'],
                              $resolution, $am7[$j]) - $resolution, $pm7[$j]);

      for ($t = $start_t; $t <= $end_t; $t += $resolution)
      {
        $d[$j][date($format,$t)]["id"]    = $row['id'];
        $d[$j][date($format,$t)]["color"] = $row['type'];
        $d[$j][date($format,$t)]["data"]  = "";
        $d[$j][date($format,$t)]["long_descr"]  = "";
      }
 
      // Show the name of the booker in the first segment that the booking
      // happens in, or at the start of the day if it started before today.
      if ($row['end_time'] < $am7[$j])
      {
        $d[$j][date($format,$am7[$j])]["data"] = $row['name'];
        $d[$j][date($format,$am7[$j])]["long_descr"] = $row['description'];
      }
      else
      {
        $d[$j][date($format,$start_t)]["data"] = $row['name'];
        $d[$j][date($format,$start_t)]["long_descr"] = $row['description'];
      }
    }
  }
} 

if ($debug_flag) 
{
  echo "<p>DEBUG:<pre>\n";
  echo "\$dst_change = ";
  print_r( $dst_change );
  print "\n";
  print "\$am7 =\n";
  foreach ( $am7 as $am7_val)
  {
    print "$am7_val - " . date("r", $am7_val) . "\n";
  }
  print "\$pm7 =\n";
  foreach( $pm7 as $pm7_val)
  {
    print "$pm7_val - " . date("r", $pm7_val) . "\n";
  }

  echo "<p>\$d =\n";
  if (gettype($d) == "array")
  {
    while (list($w_k, $w_v) = each($d))
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
    echo "d is not an array!\n";
  }
  echo "</pre><p>\n";
}

// Include the active cell content management routines. 
// Must be included before the beginnning of the main table.
if ($javascript_cursor) // If authorized in config.inc.php, include the javascript cursor management.
{
  echo "<script type=\"text/javascript\" src=\"xbLib.js\"></script>\n";
  echo "<script type=\"text/javascript\">InitActiveCell("
    . ($show_plus_link ? "true" : "false") . ", "
    . "true, "
    . ((FALSE != $times_right_side) ? "true" : "false") . ", "
    . "\"$highlight_method\", "
    . "\"" . get_vocab("click_to_reserve") . "\""
    . ");</script>\n";
}

//This is where we start displaying stuff
echo "<table cellspacing=0 border=1 width=\"100%\">";

// The header row contains the weekday names and short dates.
echo "<tr><th width=\"1%\"><br>".($enable_periods ? get_vocab("period") : get_vocab("time")).":</th>";
if (empty($dateformat))
{
  $dformat = "%a<br>%b %d";
}
else
{
  $dformat = "%a<br>%d %b";
}
for ($j = 0; $j<=($num_of_days-1) ; $j++)
{
  $t = mktime( 12, 0, 0, $month, $day+$j, $year); 
  echo "<th width=\"14%\"><a href=\"day.php?year=" . strftime("%Y", $t) . 
    "&amp;month=" . strftime("%m", $t) . "&amp;day=" . strftime("%d", $t) . 
    "&amp;area=$area\" title=\"" . get_vocab("viewday") . "\">"
    . utf8_strftime($dformat, $t) . "</a></th>\n";
}
// next line to display times on right side
if ( FALSE != $times_right_side )
{
  echo "<th width=\"1%\"><br>"
    . ( $enable_periods  ? get_vocab("period") : get_vocab("time") )
    . ":</th>";
}

echo "</tr>\n";


// This is the main bit of the display. Outer loop is for the time slots,
// inner loop is for days of the week.

// URL for highlighting a time. Don't use REQUEST_URI or you will get
// the timetohighlight parameter duplicated each time you click.
$hilite_url="week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=$room&amp;timetohighlight";

// if the first day of the week to be displayed contains as DST change then
// move to the next day to get the hours in the day.
( $dst_change[0] != -1 ) ? $j = 1 : $j = 0;

$row_class = "even_row";
for (
     $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
     $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
     $t += $resolution, $row_class = ($row_class == "even_row")?"odd_row":"even_row"
)
{
  // use hour:minute format
  $time_t = date($format, $t);
  // Show the time linked to the URL for highlighting that time:
  echo "<tr>";
  tdcell("red");
  if ( $enable_periods )
  {
    $time_t_stripped = preg_replace( "/^0/", "", $time_t );
    echo "<a href=\"$hilite_url=$time_t\"  title=\""
      . get_vocab("highlight_line") . "\">"
      . $periods[$time_t_stripped] . "</a></td>";
  }
  else
  {
    echo "<a href=\"$hilite_url=$time_t\" title=\""
      . get_vocab("highlight_line") . "\">"
      . utf8_strftime(hour_min_format(),$t) . "</a></td>";
  }

  // Color to use for empty cells: white, unless highlighting this row:
  if (isset($timetohighlight) && $timetohighlight == $time_t)
  {
    $empty_color = "red";
  }
  else
  {
    $empty_color = "white";
  }

  // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
  for ($thisday = 0; $thisday<=($num_of_days-1) ; $thisday++)
  {
    // Three cases:
    // color:  id:   Slot is:   Color:    Link to:
    // -----   ----- --------   --------- -----------------------
    // unset   -     empty      white,red add new entry
    // set     unset used       by type   none (unlabelled slot)
    // set     set   used       by type   view entry

    $wt = mktime( 12, 0, 0, $month, $day+$thisday, $year );
    $wday = date("d", $wt);
    $wmonth = date("m", $wt);
    $wyear = date("Y", $wt);

    if (isset($d[$thisday][$time_t]["id"]))
    {
      $id    = $d[$thisday][$time_t]["id"];
      $color = $d[$thisday][$time_t]["color"];
      $descr = htmlspecialchars($d[$thisday][$time_t]["data"]);
      $long_descr = htmlspecialchars($d[$thisday][$time_t]["long_descr"]);
    }
    else
    {
      unset($id);
    }
    
    // $c is the colour of the cell that the browser sees. White normally, 
    // red if were hightlighting that line and a nice attractive green if the room is booked.
    // We tell if its booked by $id having something in it
    if (isset($id))
    {
      $c = $color;
    }
    else if (isset($timetohighlight) && ($time_t == $timetohighlight))
    {
      $c = "red";
    }
    else
    {
      $c = $row_class;
    }
    
    tdcell($c);
 	
    // If the room isnt booked then allow it to be booked
    if (!isset($id))
    {
      $hour = date("H",$t);
      $minute  = date("i",$t);
 
      if ($javascript_cursor)
      {
        echo "<script type=\"text/javascript\">\n<!--\n";
        echo "BeginActiveCell();\n";
        echo "// -->\n</script>";
      }
      echo "<div align=\"center\">";
      if ( $enable_periods )
      {
        echo "<a href=\"edit_entry.php?room=$room&amp;area=$area"
          . "&amp;period=$time_t_stripped&amp;year=$wyear&amp;month=$wmonth"
          . "&amp;day=$wday\"><img src=\"new.gif\" alt=\"New\" width=\"10\" height=\"10\" border=\"0\"></a>";
      }
      else
      {
        echo "<a href=\"edit_entry.php?room=$room&amp;area=$area"
          . "&amp;hour=$hour&amp;minute=$minute&amp;year=$wyear&amp;month=$wmonth"
          . "&amp;day=$wday\"><img src=\"new.gif\" alt=\"New\" width=\"10\" height=\"10\" border=\"0\"></a>";
      }
      echo "</div>";
      if ($javascript_cursor)
      {
        echo "<script type=\"text/javascript\">\n<!--\n";
        echo "EndActiveCell();\n";
        echo "// -->\n</script>";
      }
    }
    else if ($descr != "")
    {
      //if it is booked then show 
      echo " <a href=\"view_entry.php?id=$id"
        . "&amp;area=$area&amp;day=$wday&amp;month=$wmonth&amp;year=$wyear\" "
        . "title=\"$long_descr\">$descr</a>";
    }
    else
    {
      echo "&nbsp;\"&nbsp;";
    }
    
    echo "</td>\n";
  }

  // next lines to display times on right side
  if ( FALSE != $times_right_side )
    {
      if ( $enable_periods )
      {
        tdcell("red");
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        echo "<a href=\"$hilite_url=$time_t\"  title=\""
          . get_vocab("highlight_line") . "\">"
          . $periods[$time_t_stripped] . "</a></td>";
      }
      else
      {
        tdcell("red");
        echo "<a href=\"$hilite_url=$time_t\" title=\""
          . get_vocab("highlight_line") . "\">"
          . utf8_strftime(hour_min_format(),$t) . "</a></td>";
      }
    }

  echo "</tr>\n";
}
echo "</table>";

show_colour_key();

include "trailer.inc"; 
?>
