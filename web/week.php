<?php
# $Id$

# mrbs/week.php - Week-at-a-time view

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mincals.inc";

if (empty($debug_flag)) $debug_flag = 0;

$num_of_days=7; #could also pass this in as a parameter or whatever

# If we don't know the right date then use today:
if (!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
} else {
# Make the date valid if day is more then number of days in month:
	while (!checkdate(intval($month), intval($day), intval($year)))
		$day--;
}

# Set the date back to the previous $weekstarts day (Sunday, if 0):
$time = mktime(12, 0, 0, $month, $day, $year);
if (($weekday = (date("w", $time) - $weekstarts + 7) % 7) > 0)
{
	$time -= $weekday * 86400;
	$day   = date("d", $time);
	$month = date("m", $time);
	$year  = date("Y", $time);
}

if (empty($area))
	$area = get_default_area();
if (empty($room))
	$room = get_default_room($area);
# Note $room will be 0 if there are no rooms; this is checked for below.

# print the page header
print_header($day, $month, $year, $area);

$format = "Gi";
if( $enable_periods ) {
	$format = "i";
	$resolution = 60;
	$morningstarts = 12;
	$morningstarts_minutes = 0;
	$eveningends = 12;
	$eveningends_minutes = count($periods)-1;

}

# ensure that $morningstarts_minutes defaults to zero if not set
if( empty( $morningstarts_minutes ) )
	$morningstarts_minutes=0;

# Define the start and end of each day of the week in a way which is not
# affected by daylight saving...
for ($j = 0; $j<=($num_of_days-1); $j++) {
	# are we entering or leaving daylight saving
	# dst_change:
	# -1 => no change
	#  0 => entering DST
	#  1 => leaving DST
	$dst_change[$j] = is_dst($month,$day+$j,$year);
	$am7[$j]=mktime($morningstarts,$morningstarts_minutes,0,$month,$day+$j,$year,is_dst($month,$day+$j,$year,$morningstarts));
	$pm7[$j]=mktime($eveningends,$eveningends_minutes,0,$month,$day+$j,$year,is_dst($month,$day+$j,$year,$eveningends));
}

if ( $pview != 1 ) {
	# Table with areas, rooms, minicals.
	echo "<table width=\"100%\"><tr>";
	$this_area_name = "";
	$this_room_name = "";

	# Show all areas
	echo "<td width=\"30%\"><u>".get_vocab("areas")."</u><br>";
}

  # show either a select box or the normal html list
  if ($area_list_format == "select") {
	echo make_area_select_html('week.php', $area, $year, $month, $day); # from functions.inc
	$this_area_name = sql_query1("select area_name from $tbl_area where id=$area");
	$this_room_name = sql_query1("select room_name from $tbl_room where id=$room");
  } else {
	$sql = "select id, area_name from $tbl_area order by area_name";
	$res = sql_query($sql);
	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{
		if ( $pview != 1 )
			echo "<a href=\"week.php?year=$year&month=$month&day=$day&area=$row[0]\">";
		if ($row[0] == $area)
		{
			$this_area_name = htmlspecialchars($row[1]);
			if ( $pview != 1 )
				echo "<font color=\"red\">$this_area_name</font></a><br>\n";
		}
		else if ( $pview != 1 ) echo htmlspecialchars($row[1]) . "</a><br>\n";
	}
  } # end area display if
if ( $pview != 1) {
	echo "</td>\n";

	# Show all rooms in the current area
echo "<td width=\"30%\"><u>".get_vocab("rooms")."</u><br>";
}

  # should we show a drop-down for the room list, or not?
  if ($area_list_format == "select") {
	echo make_room_select_html('week.php', $area, $room, $year, $month, $day); # from functions.inc
  } else {
	$sql = "select id, room_name, description from $tbl_room where area_id=$area order by room_name";
	$res = sql_query($sql);
	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{
		if ( $pview != 1 )
			echo "<a href=\"week.php?year=$year&month=$month&day=$day&area=$area&room=$row[0]\" title=\"$row[2]\">";
		if ($row[0] == $room)
		{
			$this_room_name = htmlspecialchars($row[1]);
			if ( $pview != 1 )
				echo "<font color=\"red\">$this_room_name</font></a><br>\n";
		}
		else if ( $pview != 1 ) echo htmlspecialchars($row[1]) . "</a><br>\n";
	}
} # end select if

if ( $pview != 1 ) {
	echo "</td>\n";

	#Draw the three month calendars
	minicals($year, $month, $day, $area, $room, 'week');
	echo "</tr></table>\n";
}

# Don't continue if this area has no rooms:
if ($room <= 0)
{
	echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
	include "trailer.inc";
	exit;
}

# Show area and room:
echo "<h2 align=center>$this_area_name - $this_room_name</h2>\n";

#y? are year, month and day of the previous week.
#t? are year, month and day of the next week.

$i= mktime(12,0,0,$month,$day-7,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(12,0,0,$month,$day+7,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

if ( $pview != 1 ) {
	#Show Go to week before and after links
	echo "<table width=\"100%\"><tr><td>
	  <a href=\"week.php?year=$yy&month=$ym&day=$yd&area=$area&room=$room\">
	  &lt;&lt; ".get_vocab("weekbefore")."</a></td>
	  <td align=center><a href=\"week.php?area=$area&room=$room\">".get_vocab("gotothisweek")."</a></td>
	  <td align=right><a href=\"week.php?year=$ty&month=$tm&day=$td&area=$area&room=$room\">
	  ".get_vocab("weekafter")."&gt;&gt;</a></td></tr></table>";
}

#Get all appointments for this week in the room that we care about
# row[0] = Start time
# row[1] = End time
# row[2] = Entry type
# row[3] = Entry name (brief description)
# row[4] = Entry ID
# row[5] = Complete description
# This data will be retrieved day-by-day
for ($j = 0; $j<=($num_of_days-1) ; $j++) {

	$sql = "SELECT start_time, end_time, type, name, id, description
	        FROM $tbl_entry
	        WHERE room_id = $room
	        AND start_time <= $pm7[$j] AND end_time > $am7[$j]";

	# Each row returned from the query is a meeting. Build an array of the
	# form:  d[weekday][slot][x], where x = id, color, data, long_desc.
	# [slot] is based at 000 (HHMM) for midnight, but only slots within
	# the hours of interest (morningstarts : eveningends) are filled in.
	# [id], [data] and [long_desc] are only filled in when the meeting
	# should be labeled,  which is once for each meeting on each weekday.
	# Note: weekday here is relative to the $weekstarts configuration variable.
	# If 0, then weekday=0 means Sunday. If 1, weekday=0 means Monday.

	if ($debug_flag) echo "<br>DEBUG: query=$sql\n";
	$res = sql_query($sql);
	if (! $res) echo sql_error();
	else for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{
		if ($debug_flag)
			echo "<br>DEBUG: result $i, id $row[4], starts $row[0], ends $row[1]\n";

	 	# $d is a map of the screen that will be displayed
 		# It looks like:
 		#     $d[Day][Time][id]
 		#                  [color]
 		#                  [data]
 		# where Day is in the range 0 to $num_of_days. 
 	
 		# Fill in the map for this meeting. Start at the meeting start time,
 		# or the day start time, whichever is later. End one slot before the
 		# meeting end time (since the next slot is for meetings which start then),
 		# or at the last slot in the day, whichever is earlier.
 		# Note: int casts on database rows for max may be needed for PHP3.
 		# Adjust the starting and ending times so that bookings which don't
 		# start or end at a recognized time still appear.
 
		$start_t = max(round_t_down($row[0], $resolution, $am7[$j]), $am7[$j]);
 		$end_t = min(round_t_up($row[1], $resolution, $am7[$j]) - $resolution, $pm7[$j]);

 		for ($t = $start_t; $t <= $end_t; $t += $resolution)
 		{
			$d[$j][date($format,$t)]["id"]    = $row[4];
 			$d[$j][date($format,$t)]["color"] = $row[2];
 			$d[$j][date($format,$t)]["data"]  = "";
 			$d[$j][date($format,$t)]["long_descr"]  = "";
 		}
 
 		# Show the name of the booker in the first segment that the booking
 		# happens in, or at the start of the day if it started before today.
 		if ($row[1] < $am7[$j])
		{
 			$d[$j][date($format,$am7[$j])]["data"] = $row[3];
 			$d[$j][date($format,$am7[$j])]["long_descr"] = $row[5];
		}
 		else
		{
 			$d[$j][date($format,$start_t)]["data"] = $row[3];
 			$d[$j][date($format,$start_t)]["long_descr"] = $row[5];
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
	foreach( $am7 as $am7_val)
		print "$am7_val - " . date("r", $am7_val) . "\n";
	print "\$pm7 =\n";
	foreach( $pm7 as $pm7_val)
		print "$pm7_val - " . date("r", $pm7_val) . "\n";

	echo "<p>\$d =\n";
	if (gettype($d) == "array")
	while (list($w_k, $w_v) = each($d))
		while (list($t_k, $t_v) = each($w_v))
			while (list($k_k, $k_v) = each($t_v))
				echo "d[$w_k][$t_k][$k_k] = '$k_v'\n";
	else echo "d is not an array!\n";
	echo "</pre><p>\n";
}

// Include the active cell content management routines. 
// Must be included before the beginnning of the main table.
	if ($javascript_cursor) // If authorized in config.inc.php, include the javascript cursor management.
            {
	    echo "<SCRIPT language=\"JavaScript\" type=\"text/javascript\" src=\"xbLib.js\"></SCRIPT>\n";
            echo "<SCRIPT language=\"JavaScript\">InitActiveCell("
               . ($show_plus_link ? "true" : "false") . ", "
               . "true, "
               . ((FALSE != $times_right_side) ? "true" : "false") . ", "
               . "\"$highlight_method\", "
               . "\"" . get_vocab("click_to_reserve") . "\""
               . ");</SCRIPT>\n";
            }

#This is where we start displaying stuff
echo "<table cellspacing=0 border=1 width=\"100%\">";

# The header row contains the weekday names and short dates.
echo "<tr><th width=\"1%\"><br>".($enable_periods ? get_vocab("period") : get_vocab("time"))."</th>";
if (empty($dateformat))
	$dformat = "%a<br>%b %d";
else
	$dformat = "%a<br>%d %b";
for ($j = 0; $j<=($num_of_days-1) ; $j++)
{
	$t = mktime( 12, 0, 0, $month, $day+$j, $year); 
	echo "<th width=\"14%\"><a href=\"day.php?year=" . strftime("%Y", $t) . 
	"&month=" . strftime("%m", $t) . "&day=" . strftime("%d", $t) . 
	"&area=$area\" title=\"" . get_vocab("viewday") . "\">"
    . utf8_strftime($dformat, $t) . "</a></th>\n";
}
# next line to display times on right side
if ( FALSE != $times_right_side )
{
    echo "<th width=\"1%\"><br>"
    . ( $enable_periods  ? get_vocab("period") : get_vocab("time") )
    . "</th>";
}

echo "</tr>\n";


# This is the main bit of the display. Outer loop is for the time slots,
# inner loop is for days of the week.

# URL for highlighting a time. Don't use REQUEST_URI or you will get
# the timetohighlight parameter duplicated each time you click.
$hilite_url="week.php?year=$year&month=$month&day=$day&area=$area&room=$room&timetohighlight";

# if the first day of the week to be displayed contains as DST change then
# move to the next day to get the hours in the day.
( $dst_change[0] != -1 ) ? $j = 1 : $j = 0;

$row_class = "even_row";
for (
	$t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
	$t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j, $year);
	$t += $resolution, $row_class = ($row_class == "even_row")?"odd_row":"even_row"
)
{
	# use hour:minute format
	$time_t = date($format, $t);
	# Show the time linked to the URL for highlighting that time:
	echo "<tr>";
	tdcell("red");
	if( $enable_periods ){
		$time_t_stripped = preg_replace( "/^0/", "", $time_t );
		echo "<a href=\"$hilite_url=$time_t\"  title=\""
        . get_vocab("highlight_line") . "\">"
        . $periods[$time_t_stripped] . "</a></td>";
	} else {
		echo "<a href=\"$hilite_url=$time_t\" title=\""
        . get_vocab("highlight_line") . "\">"
        . utf8_date(hour_min_format(),$t) . "</a></td>";
	}

	# Color to use for empty cells: white, unless highlighting this row:
	if (isset($timetohighlight) && $timetohighlight == $time_t)
		$empty_color = "red";
	else
		$empty_color = "white";

	# See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
	for ($thisday = 0; $thisday<=($num_of_days-1) ; $thisday++)
	{
		# Three cases:
		# color:  id:   Slot is:   Color:    Link to:
		# -----   ----- --------   --------- -----------------------
		# unset   -     empty      white,red add new entry
		# set     unset used       by type   none (unlabelled slot)
		# set     set   used       by type   view entry

		$wt = mktime( 12, 0, 0, $month, $day+$thisday, $year );
		$wday = date("d", $wt);
		$wmonth = date("m", $wt);
		$wyear = date("Y", $wt);

 		if(isset($d[$thisday][$time_t]["id"]))
 		{
 			$id    = $d[$thisday][$time_t]["id"];
 			$color = $d[$thisday][$time_t]["color"];
 			$descr = htmlspecialchars($d[$thisday][$time_t]["data"]);
 			$long_descr = htmlspecialchars($d[$thisday][$time_t]["long_descr"]);
 		}
 		else
 			unset($id);
 		
 		# $c is the colour of the cell that the browser sees. White normally, 
 		# red if were hightlighting that line and a nice attractive green if the room is booked.
 		# We tell if its booked by $id having something in it
 		if (isset($id))
 			$c = $color;
 		elseif (isset($timetohighlight) && ($time_t == $timetohighlight))
 			$c = "red";
 		else
 			$c = $row_class;
 	
		tdcell($c);
 	
		# If the room isnt booked then allow it to be booked
 		if(!isset($id))
 		{
 			$hour = date("H",$t);
 			$minute  = date("i",$t);
 
 			if ( $pview != 1 ) {
				if ($javascript_cursor)
				{
					echo "<SCRIPT language=\"JavaScript\">\n<!--\n";
					echo "BeginActiveCell();\n";
					echo "// -->\n</SCRIPT>";
				}
	  			echo "<center>";
				if( $enable_periods ) {
					echo "<a href=\"edit_entry.php?room=$room&area=$area"
						. "&period=$time_t_stripped&year=$wyear&month=$wmonth"
						. "&day=$wday\"><img src=new.gif width=10 height=10 border=0></a>";
				} else {
					echo "<a href=\"edit_entry.php?room=$room&area=$area"
						. "&hour=$hour&minute=$minute&year=$wyear&month=$wmonth"
						. "&day=$wday\"><img src=new.gif width=10 height=10 border=0></a>";
				}
	 			echo "</center>";
				if ($javascript_cursor)
				{
					echo "<SCRIPT language=\"JavaScript\">\n<!--\n";
					echo "EndActiveCell();\n";
					echo "// -->\n</SCRIPT>";
				}
 			} else
				echo '&nbsp;';
 		}
 		elseif ($descr != "")
 		{
 			#if it is booked then show 
			echo " <a href=\"view_entry.php?id=$id"
				. "&area=$area&day=$wday&month=$wmonth&year=$wyear\" "
                       		. "title=\"$long_descr\">$descr</a>";
		}
 		else
			echo "&nbsp;\"&nbsp;";
 
		echo "</td>\n";
	}

	# next lines to display times on right side
    if ( FALSE != $times_right_side )
    {
        if( $enable_periods )
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
            . utf8_date(hour_min_format(),$t) . "</a></td>";
        }
    }

	echo "</tr>\n";
}
echo "</table>";

show_colour_key();

include "trailer.inc"; 
?>
