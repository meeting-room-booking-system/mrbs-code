<?php
// $Id$

require_once "defaultincludes.inc";
require_once "mincals.inc";
require_once "theme.inc";

// Get non-standard form variables
$timetohighlight = get_form_var('timetohighlight', 'int');
$debug_flag = get_form_var('debug_flag', 'int');

if (empty($debug_flag))
{
  $debug_flag = 0;
}

// Check the user is authorised for this page
checkAuthorised();

// form the room parameter for use in query strings.    We want to preserve room information
// if possible when switching between views
if (empty($room))
{
  $room_param = "";
}
else
{
  $room_param = "&amp;room=$room";
}

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : "");

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
      
<?php

$sql = "select id, area_name from $tbl_area order by area_name";
$res = sql_query($sql);
// Show all available areas
// but only if there's more than one of them, otherwise there's no point
if ($res && (sql_count($res)>1))
{
  echo "<div id=\"dwm_areas\">\n";
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
    echo ("<ul>\n");
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      echo "<li><a href=\"day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$row['id']."\">";
      echo "<span" . (($row['id'] == $area) ? ' class="current"' : '') . ">";
      echo htmlspecialchars($row['area_name']) . "</span></a></li>\n";
    }  
    echo ("</ul>\n");
  }
  echo "</div>\n";
}

// Draw the three month calendars
minicals($year, $month, $day, $area, $room, 'day');
echo "</div>";

?>
</div>
<?php

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
  $i= mktime(12,0,0,$month,$d,$year);
}
while (is_hidden_day(date("w", $i)) && ($d < $day + 7));  // break the loop if all days are hidden
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);


//We want to build an array containing all the data we want to show
//and then spit it out. 

//Get all appointments for today in the area that we care about
//Note: The predicate clause 'start_time <= ...' is an equivalent but simpler
//form of the original which had 3 BETWEEN parts. It selects all entries which
//occur on or cross the current day.
$sql = "SELECT $tbl_room.id AS room_id, start_time, end_time, name, $tbl_entry.id AS entry_id, type,
        $tbl_entry.description AS entry_description, status,
        $tbl_entry.private AS entry_private, $tbl_entry.create_by AS entry_create_by
   FROM $tbl_entry, $tbl_room
   WHERE $tbl_entry.room_id = $tbl_room.id
   AND area_id = $area
   AND start_time <= $pm7 AND end_time > $am7
   ORDER BY start_time";   // necessary so that multiple bookings appear in the right order
   
$res = sql_query($sql);
if (! $res)
{
  fatal_error(0, sql_error());
}

$today = array();

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
  //  row['entry_private'] = if entry is private
  //  row['entry_create_by'] = Creator/owner of entry
  //  row['status'] = Status code of the entry
  
  map_add_booking($row, $today[$row['room_id']][$day], $am7, $pm7, $format);

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

$sql = "SELECT room_name, capacity, id, description FROM $tbl_room WHERE area_id=$area ORDER BY sort_key";

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
      <a href=\"day.php?year=$yy&amp;month=$ym&amp;day=$yd&amp;area=$area$room_param\">&lt;&lt;&nbsp;". get_vocab("daybefore") ."
      </a>
    </div>
    <div class=\"date_now\">
      <a href=\"day.php?area=$area$room_param\">" . get_vocab("gototoday") . "</a>
    </div>
    <div class=\"date_after\">
      <a href=\"day.php?year=$ty&amp;month=$tm&amp;day=$td&amp;area=$area$room_param\">". get_vocab("dayafter") . "&nbsp;&gt;&gt;
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
      . ((FALSE != $row_labels_both_sides) ? "true" : "false") . ", "
      . "\"$highlight_method\", "
      . "\"" . get_vocab("click_to_reserve") . "\""
      . ");\n";
    echo "//]]>\n";
    echo "</script>\n";
  }

  // START DISPLAYING THE MAIN TABLE
  echo "<table class=\"dwm_main\" id=\"day_main\">\n";
  ( $dst_change != -1 ) ? $j = 1 : $j = 0;
  
  // TABLE HEADER
  echo "<thead>\n";
  $header = "<tr>\n";
  
  
  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and rooms down the side
    $start_first_slot = ($morningstarts*60) + $morningstarts_minutes;   // minutes
    $start_last_slot  = ($eveningends*60) + $eveningends_minutes;       // minutes
    $start_difference = ($start_last_slot - $start_first_slot) * 60;    // seconds
    $n_slots = ($start_difference/$resolution) + 1;
    $column_width = (int)(95 / $n_slots);
    $header .= "<th class=\"first_last\">" . get_vocab("room") . ":</th>";
    for (
         $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
         $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
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
      $header .= "<th class=\"first_last\">" . get_vocab("room") . ":</th>";
    }
  } // end "times_along_top" view (for the header)
  
  else
  {
    // the standard view, with rooms along the top and times down the side
    $header .= "<th class=\"first_last\">" . ($enable_periods ? get_vocab("period") : get_vocab("time")) . ":</th>";
  
    $column_width = (int)(95 / sql_count($res));
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      $header .= "<th style=\"width: $column_width%\">
                  <a href=\"week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=".$row['id']."\"
                  title=\"" . get_vocab("viewweek") . " &#10;&#10;".$row['description']."\">" .
                  htmlspecialchars($row['room_name']) . ($row['capacity'] > 0 ? "(".$row['capacity'].")" : "") . "</a></th>";
      $rooms[] = $row['id'];
    }
  
    // next line to display times on right side
    if ( FALSE != $row_labels_both_sides )
    {
      $header .= "<th class=\"first_last\">" . ( $enable_periods  ? get_vocab("period") : get_vocab("time") ) . ":</th>";
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
  
  // This is the main bit of the display
  // We loop through time and then the rooms we just got

  // if the today is a day which includes a DST change then use
  // the day after to generate timesteps through the day as this
  // will ensure a constant time step
  
  // URL for highlighting a time. Don't use REQUEST_URI or you will get
  // the timetohighlight parameter duplicated each time you click.
  $hilite_url="day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area$room_param&amp;timetohighlight";
  
  
   
  $row_class = "even_row";
  
  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and rooms down the side
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++, $row_class = ($row_class == "even_row")?"odd_row":"even_row")
    {
      echo "<tr>\n";
      $room_id = $row['id']; 
      $room_cell_link = "week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=$room_id";
      draw_room_cell($row, $room_cell_link);
      for (
           $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
           $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
           $t += $resolution
          )
      {
        // convert timestamps to HHMM format without leading zeros
        $time_t = date($format, $t);
        // and get a stripped version of the time for use with periods
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        
        // calculate hour and minute (needed for links)
        $hour = date("H",$t);
        $minute = date("i",$t);
        
        // set up the query strings to be used for the link in the cell
        $query_strings = array();
        $query_strings['new_periods'] = "area=$area&amp;room=$room_id&amp;period=$time_t_stripped&amp;year=$year&amp;month=$month&amp;day=$day";
        $query_strings['new_times']   = "area=$area&amp;room=$room_id&amp;hour=$hour&amp;minute=$minute&amp;year=$year&amp;month=$month&amp;day=$day";
        $query_strings['booking']     = "area=$area&amp;day=$day&amp;month=$month&amp;year=$year";
        // and then draw the cell
        if (!isset($today[$room_id][$day][$time_t]))
        {
          $today[$room_id][$day][$time_t] = array();  // to avoid an undefined index NOTICE error
        }   
        $cell_class = $row_class;
        draw_cell($today[$room_id][$day][$time_t], $query_strings, $cell_class);
      }  // end for (looping through the times)
      if ( FALSE != $row_labels_both_sides )
      {
        draw_room_cell($row, $room_cell_link);
      }
      echo "</tr>\n";
    }  // end for (looping through the rooms)
  }  // end "times_along_top" view (for the body)
  
  else
  {
    // the standard view, with rooms along the top and times down the side
    for (
         $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
         $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
         $t += $resolution, $row_class = ($row_class == "even_row")?"odd_row":"even_row"
        )
    {
      // convert timestamps to HHMM format without leading zeros
      $time_t = date($format, $t);
      // and get a stripped version of the time for use with periods
      $time_t_stripped = preg_replace( "/^0/", "", $time_t );
      
      // calculate hour and minute (needed for links)
      $hour = date("H",$t);
      $minute = date("i",$t);
  
      // Show the time linked to the URL for highlighting that time
      echo "<tr>";
      draw_time_cell($t, $time_t, $time_t_stripped, $hilite_url);
  
      // Loop through the list of rooms we have for this area
      while (list($key, $room_id) = each($rooms))
      {
        // set up the query strings to be used for the link in the cell
        $query_strings = array();
        $query_strings['new_periods'] = "area=$area&amp;room=$room_id&amp;period=$time_t_stripped&amp;year=$year&amp;month=$month&amp;day=$day";
        $query_strings['new_times']   = "area=$area&amp;room=$room_id&amp;hour=$hour&amp;minute=$minute&amp;year=$year&amp;month=$month&amp;day=$day";
        $query_strings['booking']     = "area=$area&amp;day=$day&amp;month=$month&amp;year=$year";
        // and then draw the cell
        if (!isset($today[$room_id][$day][$time_t]))
        {
          $today[$room_id][$day][$time_t] = array();  // to avoid an undefined index NOTICE error
        }
        if (isset($timetohighlight) && ($time_t == $timetohighlight))
        {
          $cell_class = "row_highlight";
        }
        else
        {
          $cell_class = $row_class;
        }
        draw_cell($today[$room_id][$day][$time_t], $query_strings, $cell_class);
      }
      
      // next lines to display times on right side
      if ( FALSE != $row_labels_both_sides )
      {
        draw_time_cell($t, $time_t, $time_t_stripped, $hilite_url);
      }
  
      echo "</tr>\n";
      reset($rooms);
    }
  }  // end standard view (for the body)
  
  echo "</tbody>\n";
  echo "</table>\n";

  print $before_after_links_html;

  show_colour_key();
}

require_once "trailer.inc";
?>
