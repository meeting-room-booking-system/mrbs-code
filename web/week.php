<?php
// $Id$

// mrbs/week.php - Week-at-a-time view

require_once "defaultincludes.inc";
require_once "mincals.inc";
require_once "theme.inc";

// Get non-standard form variables
$debug_flag = get_form_var('debug_flag', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');

// Check the user is authorised for this page
checkAuthorised();

$num_of_days=7; //could also pass this in as a parameter or whatever

// Calculate how many days to skip back to get to the start of the week
$time = mktime(12, 0, 0, $month, $day, $year);
$skipback = (date("w", $time) - $weekstarts + 7) % 7;
$day_start_week = $day - $skipback;
// We will use $day for links and $day_start_week for anything to do with showing the bookings,
// because we want the booking display to start on the first day of the week (eg Sunday if $weekstarts is 0)
// but we want to preserve the notion of the current day (or 'sticky day') when switching between pages


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

// Get all appointments for this week in the room that we care about.
//
// row['room_id'] = Room ID
// row['start_time'] = Start time
// row['end_time'] = End time
// row['type'] = Entry type
// row['name'] = Entry name (brief description)
// row['entry_id'] = Entry ID
// row['entry_description'] = Complete description
// row['status'] = status code
// row['entry_create_by'] = User who created entry
// This data will be retrieved day-by-day

$week_map = array();

for ($j = 0; $j<=($num_of_days-1) ; $j++)
{
  $sql = "SELECT room_id, start_time, end_time, type, name, status, repeat_id,
                 id AS entry_id, description AS entry_description,
                 create_by AS entry_create_by
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
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
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

  // START DISPLAYING THE MAIN TABLE
echo "<table class=\"dwm_main\" id=\"week_main\">";
// if the first day of the week to be displayed contains as DST change then
// move to the next day to get the hours in the day.
( $dst_change[0] != -1 ) ? $j = 1 : $j = 0;


  // TABLE HEADER
echo "<thead>\n";
$header = "<tr>\n";

$dformat = "%a<br>" . $strftime_format['daymonth'];
// If we've got a table with times along the top then put everything on the same line
// (ie replace the <br> with a space).   It looks slightly better
if ($times_along_top)
{
  $dformat = preg_replace("/<br>/", " ", $dformat);
}


// We can display the table in two ways
if ($times_along_top)
{
  // with times along the top and days of the week down the side
  $start_first_slot = ($morningstarts*60) + $morningstarts_minutes;   // minutes
  $start_last_slot  = ($eveningends*60) + $eveningends_minutes;       // minutes
  $start_difference = ($start_last_slot - $start_first_slot) * 60;    // seconds
  $n_slots = ($start_difference/$resolution) + 1;
  $column_width = (int)(95 / $n_slots);
  $header .= "<th class=\"first_last\">" . get_vocab("date") . ":</th>";
  for (
       $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day_start_week+$j, $year);
       $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day_start_week+$j, $year);
       $t += $resolution
      )
  {
    $header .= "<th style=\"width: $column_width%\">";
    if ( $enable_periods )
    {
      // convert timestamps to HHMM format without leading zeros
      $time_t = date($format, $t);
      // and get a stripped version of the time for use with periods
      $time_t_stripped = preg_replace( "/^0/", "", $time_t );
      $header .= $periods[$time_t_stripped];
    }
    else
    {
      $header .= utf8_strftime(hour_min_format(),$t);
    }
    $header .= "</th>\n";
  }
  // next: line to display times on right side
  if ( FALSE != $row_labels_both_sides )
  {
    $header .= "<th class=\"first_last\">" . get_vocab("date") . ":</th>";
  } 
} // end "times_along_top" view (for the header)

else
{
  // the standard view, with days along the top and times down the side
  $header .= "<th class=\"first_last\">".($enable_periods ? get_vocab("period") : get_vocab("time")).":</th>";
  for ($j = 0; $j<=($num_of_days-1) ; $j++)
  {
    $t = mktime( 12, 0, 0, $month, $day_start_week+$j, $year); 
    
    if (is_hidden_day(($j + $weekstarts) % 7))
    {
      // These days are to be hidden in the display (as they are hidden, just give the
      // day of the week in the header row 
      $header .= "<th class=\"hidden_day\">" . utf8_strftime($strftime_format['dayname_cal'], $t) . "</th>\n";
    }
  
    else  
    {  
      $header .= "<th><a href=\"day.php?year=" . strftime("%Y", $t) . 
                 "&amp;month=" . strftime("%m", $t) . "&amp;day=" . strftime("%d", $t) . 
                 "&amp;area=$area\" title=\"" . get_vocab("viewday") . "\">" .
                 utf8_strftime($dformat, $t) . "</a></th>\n";
    }
  }
  // next line to display times on right side
  if ( FALSE != $row_labels_both_sides )
  {
    $header .= "<th class=\"first_last\">" .
               ( $enable_periods  ? get_vocab("period") : get_vocab("time") ) .
               ":</th>";
  }
}  // end standard view (for the header)

$header .= "</tr>\n";
echo $header;
echo "</thead>\n";

// Now repeat the header in a footer if required
if ($column_labels_both_ends)
{
  echo "<tfoot>\n";
  echo $header;
  echo "</tfoot>\n";
}



// TABLE BODY LISTING BOOKINGS
echo "<tbody>\n";

// URL for highlighting a time. Don't use REQUEST_URI or you will get
// the timetohighlight parameter duplicated each time you click.
$hilite_url="week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=$room&amp;timetohighlight";
$row_class = "even_row";

// We can display the table in two ways
if ($times_along_top)
{
  // with times along the top and days of the week down the side
  // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
  for ($thisday = 0; $thisday<=($num_of_days-1) ; $thisday++, $row_class = ($row_class == "even_row")?"odd_row":"even_row")
  {
    if (is_hidden_day(($thisday + $weekstarts) % 7))
    {
      // These days are to be hidden in the display: don't display a row
      // Toggle the row class back to keep it in sequence
      $row_class = ($row_class == "even_row")?"odd_row":"even_row";
      continue;
    }
    
    else
    {
      echo "<tr>\n";
      
      $wt = mktime( 12, 0, 0, $month, $day_start_week+$thisday, $year );
      $wday = date("d", $wt);
      $wmonth = date("m", $wt);
      $wyear = date("Y", $wt);
      
      $day_cell_text = utf8_strftime($dformat, $wt);
      $day_cell_link = "day.php?year=" . strftime("%Y", $wt) . 
                       "&amp;month=" . strftime("%m", $wt) . 
                       "&amp;day=" . strftime("%d", $wt) . 
                       "&amp;area=$area";
                       
      draw_day_cell($day_cell_text, $day_cell_link);
      for (
           $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day_start_week+$j, $year);
           $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day_start_week+$j, $year);
           $t += $resolution
          )
      {
        // use hour:minute format
        $time_t = date($format, $t);
        // and get a stripped version for use with periods
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        
        // calculate hour and minute (needed for links)
        $hour = date("H",$t);
        $minute  = date("i",$t);
        
        // set up the query strings to be used for the link in the cell
        $query_strings = array();
        $query_strings['new_periods'] = "room=$room&amp;area=$area&amp;period=$time_t_stripped&amp;year=$wyear&amp;month=$wmonth&amp;day=$wday";
        $query_strings['new_times']   = "room=$room&amp;area=$area&amp;hour=$hour&amp;minute=$minute&amp;year=$wyear&amp;month=$wmonth&amp;day=$wday";
        $query_strings['booking']     = "area=$area&amp;day=$wday&amp;month=$wmonth&amp;year=$wyear";
        
        // and then draw the cell
        if (!isset($week_map[$room][$thisday][$time_t]))
        {
          $week_map[$room][$thisday][$time_t] = array();  // to avoid an undefined index NOTICE error
        }
        $cell_class = $row_class;
        draw_cell($week_map[$room][$thisday][$time_t], $query_strings, $cell_class);
      }  // end looping through the time slots
      if ( FALSE != $row_labels_both_sides )
      {
        draw_day_cell($day_cell_text, $day_cell_link);
      }
      echo "</tr>\n";
    }
    
  }  // end looping through the days of the week
  
} // end "times along top" view (for the body)

else
{
  // the standard view, with days of the week along the top and times down the side
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
    draw_time_cell($t, $time_t, $time_t_stripped, $hilite_url);
  
  
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
        if (isset($timetohighlight) && ($time_t == $timetohighlight))
        {
          $cell_class = "row_highlight";
        }
        else
        {
          $cell_class = $row_class;
        }
        draw_cell($week_map[$room][$thisday][$time_t], $query_strings, $cell_class);
      }
  
    }    // for loop
  
    // next lines to display times on right side
    if ( FALSE != $row_labels_both_sides )
      {
        draw_time_cell($t, $time_t, $time_t_stripped, $hilite_url);
      }
  
    echo "</tr>\n";
  }
}  // end standard view (for the body)
echo "</tbody>\n";
echo "</table>\n";

print $before_after_links_html;

show_colour_key();

require_once "trailer.inc"; 
?>
