<?php
// $Id$

// mrbs/week.php - Week-at-a-time view

require_once "grab_globals.inc.php";
require_once "config.inc.php";
require_once "functions.inc";
require_once "dbsys.inc";
require_once "mrbs_auth.inc";
require_once "mincals.inc";
require_once "Themes/$theme.inc";

// Get form variables
$debug_flag = get_form_var('debug_flag', 'int');
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');

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


// Calculate how many days to skip back to get to the start of the week
$time = mktime(12, 0, 0, $month, $day, $year);
$skipback = (date("w", $time) - $weekstarts + 7) % 7;
$day_start_week = $day - $skipback;
// We will use $day for links and $day_start_week for anything to do with showing the bookings,
// because we want the booking display to start on the first day of the week (eg Sunday if $weekstarts is 0)
// but we want to preserve the notion of the current day (or 'sticky day') when switching between pages


if (empty($area))
{
  $area = get_default_area();}
if (empty($room))
{
  $room = get_default_room($area);
}
// Note $room will be 0 if there are no rooms; this is checked for below.

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : "");

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
  $dst_change[$j] = is_dst($month,$day_start_week+$j,$year);
  $am7[$j]=mktime($morningstarts,$morningstarts_minutes,0,
                  $month,$day_start_week+$j,$year,is_dst($month,
                                              $day_start_week+$j,
                                              $year,
                                              $morningstarts));
  $pm7[$j]=mktime($eveningends,$eveningends_minutes,0,
                  $month,$day_start_week+$j,$year,is_dst($month,
                                              $day_start_week+$j,
                                              $year,
                                              $eveningends));
}

// Section with areas, rooms, minicals.

?>
<div class="screenonly">
  <div id="dwm_header">
<?php

// Get the area and room names (we will need them later for the heading)
$this_area_name = "";
$this_room_name = "";
$this_area_name = htmlspecialchars(sql_query1("select area_name
                                  from $tbl_area where id=$area"));
$this_room_name = htmlspecialchars(sql_query1("select room_name
                                  from $tbl_room where id=$room"));

$sql = "select id, area_name from $tbl_area order by area_name";
$res = sql_query($sql);
// Show all available areas
// but only if there's more than one of them, otherwise there's no point
if ($res && (sql_count($res)>1))
{
  echo "<div id=\"dwm_areas\"><h3>".get_vocab("areas")."</h3>";
  
  // show either a select box or the normal html list
  if ($area_list_format == "select")
  {
    echo make_area_select_html('week.php', $area, $year, $month, $day);   
  }
  else
  {
    echo "<ul>\n";
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      echo "<li><a href=\"week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$row[0]\">";
      echo "<span" . (($row['id'] == $area) ? ' class="current"' : '') . ">";
      echo htmlspecialchars($row['area_name']) . "</span></a></li>\n";
    }
    echo "</ul>\n";
  } // end area display if
  
  echo "</div>\n";
}

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
      echo "<li><a href=\"week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=".$row['id']."\">";
      echo "<span" . (($row['id'] == $room) ? ' class="current"' : '') . ">";
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
  require_once "trailer.inc";
  exit;
}

// Show area and room:
echo "<h2 id=\"dwm\">$this_area_name - $this_room_name</h2>\n";

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

//Get all appointments for this week in the room that we care about
// row['start_time'] = Start time
// row['end_time'] = End time
// row['type'] = Entry type
// row['name'] = Entry name (brief description)
// row['id'] = Entry ID
// row['description'] = Complete description
// This data will be retrieved day-by-day

$week_map = array();

for ($j = 0; $j<=($num_of_days-1) ; $j++)
{
  $sql = "SELECT start_time, end_time, type, name, id AS entry_id, description AS entry_description
          FROM $tbl_entry
          WHERE room_id = $room
          AND start_time <= $pm7[$j] AND end_time > $am7[$j]
          ORDER BY start_time";   // necessary so that multiple bookings appear in the right order

  // Each row returned from the query is a meeting. Build an array of the
  // form:  $week_map[room][weekday][slot][x], where x = id, color, data, long_desc.
  // [slot] is based at 000 (HHMM) for midnight, but only slots within
  // the hours of interest (morningstarts : eveningends) are filled in.
  // [id], [data] and [long_desc] are only filled in when the meeting
  // should be labeled,  which is once for each meeting on each weekday.
  // Note: weekday here is relative to the $weekstarts configuration variable.
  // If 0, then weekday=0 means Sunday. If 1, weekday=0 means Monday.

  if ($debug_flag)
  {
    echo "<p>DEBUG: query=$sql</p>\n";
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
        echo "<p>DEBUG: result $i, id ".$row['id'].", starts ".$row['start_time'],", ends ".$row['end_time']."</p>\n";
      }
      map_add_booking($row, $week_map[$room][$j], $am7[$j], $pm7[$j], $format);
    }
  }
} 



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

//This is where we start displaying stuff
echo "<table class=\"dwm_main\" id=\"week_main\">";


// The header row contains the weekday names and short dates.
echo "<thead>\n";
echo "<tr><th class=\"first_last\">".($enable_periods ? get_vocab("period") : get_vocab("time")).":</th>";
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
  $t = mktime( 12, 0, 0, $month, $day_start_week+$j, $year); 
  
  if (is_hidden_day(($j + $weekstarts) % 7))
  {
    // These days are to be hidden in the display (as they are hidden, just give the
    // day of the week in the header row 
    echo "<th class=\"hidden_day\">" . utf8_strftime('%a', $t) . "</th>\n";
  }

  else  
  {  
    echo "<th><a href=\"day.php?year=" . strftime("%Y", $t) . 
      "&amp;month=" . strftime("%m", $t) . "&amp;day=" . strftime("%d", $t) . 
      "&amp;area=$area\" title=\"" . get_vocab("viewday") . "\">"
      . utf8_strftime($dformat, $t) . "</a></th>\n";
  }
}
// next line to display times on right side
if ( FALSE != $times_right_side )
{
  echo "<th class=\"first_last\">"
    . ( $enable_periods  ? get_vocab("period") : get_vocab("time") )
    . ":</th>";
}

echo "</tr>\n";
echo "</thead>\n";


// This is the main bit of the display. Outer loop is for the time slots,
// inner loop is for days of the week.
echo "<tbody>\n";

// URL for highlighting a time. Don't use REQUEST_URI or you will get
// the timetohighlight parameter duplicated each time you click.
$hilite_url="week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=$room&amp;timetohighlight";

// if the first day of the week to be displayed contains as DST change then
// move to the next day to get the hours in the day.
( $dst_change[0] != -1 ) ? $j = 1 : $j = 0;

$row_class = "even_row";
for (
     $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day_start_week+$j, $year);
     $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day_start_week+$j, $year);
     $t += $resolution, $row_class = ($row_class == "even_row")?"odd_row":"even_row"
)
{
  // use hour:minute format
  $time_t = date($format, $t);
  // and get a stripped version for use with periods
  $time_t_stripped = preg_replace( "/^0/", "", $time_t );
  
  // calculate hour and minute (needed for links)
  $hour = date("H",$t);
  $minute  = date("i",$t);
  
  // Show the time linked to the URL for highlighting that time:
  echo "<tr>";
  tdcell("times", 1);
  echo "<div class=\"celldiv1\">\n";
  if ( $enable_periods )
  {
    echo "<a href=\"$hilite_url=$time_t\"  title=\""
      . get_vocab("highlight_line") . "\">"
      . $periods[$time_t_stripped] . "</a>";
  }
  else
  {
    echo "<a href=\"$hilite_url=$time_t\" title=\""
      . get_vocab("highlight_line") . "\">"
      . utf8_strftime(hour_min_format(),$t) . "</a>";
  }
  echo "</div></td>\n";


  // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
  for ($thisday = 0; $thisday<=($num_of_days-1) ; $thisday++)
  {
    if (is_hidden_day(($thisday + $weekstarts) % 7))
    {
      // These days are to be hidden in the display
      echo "<td class=\"hidden_day\">&nbsp;</td>\n";
    }
    else
    {
      // set up the query strings to be used for the link in the cell
      $wt = mktime( 12, 0, 0, $month, $day_start_week+$thisday, $year );
      $wday = date("d", $wt);
      $wmonth = date("m", $wt);
      $wyear = date("Y", $wt);
      
      $query_strings = array();
      $query_strings['new_periods'] = "room=$room&amp;area=$area&amp;period=$time_t_stripped&amp;year=$wyear&amp;month=$wmonth&amp;day=$wday";
      $query_strings['new_times']   = "room=$room&amp;area=$area&amp;hour=$hour&amp;minute=$minute&amp;year=$wyear&amp;month=$wmonth&amp;day=$wday";
      $query_strings['booking']     = "area=$area&amp;day=$wday&amp;month=$wmonth&amp;year=$wyear";
      
      // and then draw the cell
      if (!isset($week_map[$room][$thisday][$time_t]))
      {
        $week_map[$room][$thisday][$time_t] = array();  // to avoid an undefined index NOTICE error
      }
      draw_cell($week_map[$room][$thisday][$time_t], $query_strings, $row_class);
    }

  }    // for loop

  // next lines to display times on right side
  if ( FALSE != $times_right_side )
    {
      tdcell("times", 1);
      echo "<div class=\"celldiv1\">\n";
      if ( $enable_periods )
      {
        echo "<a href=\"$hilite_url=$time_t\"  title=\""
          . get_vocab("highlight_line") . "\">"
          . $periods[$time_t_stripped] . "</a>";
      }
      else
      {
        echo "<a href=\"$hilite_url=$time_t\" title=\""
          . get_vocab("highlight_line") . "\">"
          . utf8_strftime(hour_min_format(),$t) . "</a>";
      }
      echo "</div></td>\n";
    }

  echo "</tr>\n";
}
echo "</tbody>\n";
echo "</table>\n";

print $before_after_links_html;

show_colour_key();

require_once "trailer.inc"; 
?>
