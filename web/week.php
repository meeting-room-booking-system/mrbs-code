<?php
# $Id$

# mrbs/week.php - Week-at-a-time view

include "config.inc";
include "functions.inc";
include "$dbsys.inc";
include "mincals.inc";

if (empty($debug_flag)) $debug_flag = 0;

# If we don't know the right date then use today:
if (!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
} else {
# Make the date valid if day is more then number of days in month:
	while (!checkdate($month, $day, $year))
		$day--;
}

# Set the date back to the previous $weekstarts day (Sunday, if 0):
$time = mktime(0, 0, 0, $month, $day, $year);
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
	$room = sql_query1("select min(id) from mrbs_room where area_id=$area");
# Note $room will be -1 if there are no rooms; this is checked for below.

# print the page header
print_header($day, $month, $year, $area);

# Define the start of day and end of day (default is 7-7)
$am7=mktime($morningstarts,00,0,$month,$day,$year);
$pm7=mktime($eveningends,$eveningends_minutes,0,$month,$day,$year);

# Start and end of week:
$week_midnight = mktime(0, 0, 0, $month, $day, $year);
$week_start = $am7;
$week_end = mktime($eveningends, $eveningends_minutes, 0, $month, $day+6, $year);

if ( $pview != 1 ) {
	# Table with areas, rooms, minicals.
	echo "<table width=\"100%\"><tr>";
	$this_area_name = "";
	$this_room_name = "";

	# Show all areas
	echo "<td width=\"30%\"><u>$vocab[areas]</u><br>";
}

  # show either a select box or the normal html list
  if ($area_list_format == "select") {
	echo make_area_select_html('week.php', $area, $year, $month, $day); # from functions.inc
	$this_area_name = sql_query1("select area_name from mrbs_area where id=$area");
	$this_room_name = sql_query1("select room_name from mrbs_room where id=$room");
  } else {
	$sql = "select id, area_name from mrbs_area order by area_name";
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
  } # end area diaply if
if ( $pview != 1) {
	echo "</td>\n";

	# Show all rooms in the current area
echo "<td width=\"30%\"><u>$vocab[room]</u><br>";
}

  # should we show a drop-down for the room list, or not?
  if ($area_list_format == "select") {
	echo make_room_select_html('week.php', $area, $room, $year, $month, $day); # from functions.inc
  } else {
	$sql = "select id, room_name from mrbs_room where area_id=$area order by room_name";
	$res = sql_query($sql);
	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{
		if ( $pview != 1 )
			echo "<a href=\"week.php?year=$year&month=$month&day=$day&area=$area&room=$row[0]\">";
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
	minicals($year, $month, $day, $area, 'week');
	echo "</tr></table>\n";

	# Don't continue if this area has no rooms:
	if ($room <= 0)
	{
		echo "<h1>$vocab[no_rooms_for_area]</h1>";
		include "trailer.inc";
		exit;
	}
}

# Show area and room:
echo "<h2 align=center>$this_area_name - $this_room_name</h2>\n";

#y? are year, month and day of the previous week.
#t? are year, month and day of the next week.

$i= mktime(0,0,0,$month,$day-7,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(0,0,0,$month,$day+7,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

if ( $pview != 1 ) {
	#Show Go to week before and after links
	echo "<table width=\"100%\"><tr><td>
	  <a href=\"week.php?year=$yy&month=$ym&day=$yd&area=$area&room=$room\">
	  &lt;&lt; $vocab[weekbefore]</a></td>
	  <td align=center><a href=\"week.php?area=$area&room=$room\">$vocab[gotothisweek]</a></td>
	  <td align=right><a href=\"week.php?year=$ty&month=$tm&day=$td&area=$area&room=$room\">
	  $vocab[weekafter] &gt;&gt;</a></td></tr></table>";
}

#Get all appointments for this week in the room that we care about
# row[0] = Start time
# row[1] = End time
# row[2] = Entry type
# row[3] = Entry name (brief description)
# row[4] = Entry ID
# The range predicate (starts <= week_end && ends > week_start) is
# equivalent but more efficient than the original 3-BETWEEN clauses.
$sql = "SELECT start_time, end_time, type, name, id
   FROM mrbs_entry
   WHERE room_id=$room
   AND start_time <= $week_end AND end_time > $week_start";

# Each row returned from the query is a meeting. Build an array of the
# form:  d[weekday][slot][x], where x = id, color, data.
# [slot] is based at 0 for midnight, but only slots within the hours of
# interest (morningstarts : eveningends) are filled in.
# [id] and [data] are only filled in when the meeting should be labeled,
# which is once for each meeting on each weekday.
# Note: weekday here is relative to the $weekstarts configuration variable.
# If 0, then weekday=0 means Sunday. If 1, weekday=0 means Monday.

$first_slot = $morningstarts * 3600 / $resolution;
$last_slot = ($eveningends * 3600 + $eveningends_minutes * 60) / $resolution;

if ($debug_flag) echo "<br>DEBUG: query=$sql <br>slots=$first_slot:$last_slot\n";
$res = sql_query($sql);
if (! $res) echo sql_error();
else for ($i = 0; ($row = sql_row($res, $i)); $i++)
{
	if ($debug_flag)
		echo "<br>DEBUG: result $i, id $row[4], starts $row[0], ends $row[1]\n";

	# Fill in slots for the meeting. Start at the meeting start time or
	# week start (which ever is later), and end one slot before the meeting
	# end time or week end (which ever is earlier).
	# Note: int casts on database rows for min and max is needed for PHP3.

	$t = max(round_t_down($row[0], $resolution, $am7), $week_start);
	$end_t = min((int)round_t_up((int)$row[1],
				     (int)$resolution, $am7), 
		                     (int)$week_end+1);
	$weekday = (date("w", $t) + 7 - $weekstarts) % 7;
	$prev_weekday = -1; # Invalid value to force initial label.
	$slot = ($t - $week_midnight) % 86400 / $resolution;
	do
	{
		if ($debug_flag) echo "<br>DEBUG: t=$t, weekday=$weekday, slot=$slot\n";

		if ($slot < $first_slot)
		{
			# This is before the start of the displayed day; skip to first slot.
			$slot = $first_slot;
			$t = $weekday * 86400 + $am7;
			continue;
		}

		if ($slot <= $last_slot)
		{
			# This is within the working day; color it.
			$d[$weekday][$slot]["color"] = $row[2];
			# Only label it if it is the first time on this day:
			if ($prev_weekday != $weekday)
			{
				$prev_weekday = $weekday;
				$d[$weekday][$slot]["data"] = $row[3];
				$d[$weekday][$slot]["id"] = $row[4];
			}
		}
		# Step to next time period and slot:
		$t += $resolution;
		$slot++;

		if ($slot > $last_slot)
		{
			# Skip to first slot of next day:
			$weekday++;
			$slot = $first_slot;
			$t = $weekday * 86400 + $am7;
		}
	} while ($t < $end_t);
}
if ($debug_flag) 
{
	echo "<p>DEBUG:<p><pre>\n";
	if (gettype($d) == "array")
	while (list($w_k, $w_v) = each($d))
		while (list($t_k, $t_v) = each($w_v))
			while (list($k_k, $k_v) = each($t_v))
				echo "d[$w_k][$t_k][$k_k] = '$k_v'\n";
	else echo "d is not an array!\n";
	echo "</pre><p>\n";
}

#This is where we start displaying stuff
echo "<table cellspacing=0 border=1 width=\"100%\">";

# The header row contains the weekday names and short dates.
echo "<tr><th width=\"1%\"><br>$vocab[time]</th>";
if (empty($dateformat))
	$dformat = "%a<br>%b %d";
else
	$dformat = "%a<br>%d %b";
for ($t = $week_start; $t < $week_end; $t += 86400)
	echo "<th width=\"14%\">" . strftime($dformat, $t) . "</th>\n";
echo "</tr>\n";


# This is the main bit of the display. Outer loop is for the time slots,
# inner loop is for days of the week.

# URL for highlighting a time. Don't use REQUEST_URI or you will get
# the timetohighlight parameter duplicated each time you click.
$hilite_url="week.php?year=$year&month=$month&day=$day&area=$area&room=$room&timetohighlight";

# $t is the date/time for the first day of the week (Sunday, if $weekstarts=0).
# $wt is for the weekday in the inner loop.
$t = $am7;
for ($slot = $first_slot; $slot <= $last_slot; $slot++)
{
	# Show the time linked to the URL for highlighting that time:
	echo "<tr>";
	tdcell("red");
	echo "<a href=\"$hilite_url=$t\">" . date(hour_min_format(),$t) . "</a></td>";

	$wt = $t;

	# Color to use for empty cells: white, unless highlighting this row:
	if (isset($timetohighlight) && $timetohighlight == $t)
		$empty_color = "red";
	else
		$empty_color = "white";

	# See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
	for ($weekday = 0; $weekday < 7; $weekday++)
	{
		# Three cases:
		# color:  id:   Slot is:   Color:    Link to:
		# -----   ----- --------   --------- -----------------------
		# unset   -     empty      white,red add new entry
		# set     unset used       by type   none (unlabelled slot)
		# set     set   used       by type   view entry

		$wday = date("d", $wt);
		$wmonth = date("m", $wt);
		$wyear = date("Y", $wt);
		if(!isset($d[$weekday][$slot]["color"]))
		{
			tdcell($empty_color);
			$hour = date("H",$wt);
			$minute  = date("i",$wt);
			echo "<center>";
			if ( $pview != 1 ) {
				echo "<a href=\"edit_entry.php?room=$room&area=$area"
				. "&hour=$hour&minute=$minute&year=$wyear&month=$wmonth"
				. "&day=$wday\"><img src=new.gif width=10 height=10 border=0>";
			} else echo '&nbsp;';
			echo "</a></center>";

		} else {
			tdcell($d[$weekday][$slot]["color"]);
			if (!isset($d[$weekday][$slot]["id"])) {
				echo "&nbsp;\"&nbsp;";
			} else {
				echo " <a href=\"view_entry.php?id=" . $d[$weekday][$slot]["id"]
					. "&area=$area&day=$wday&month=$wmonth&year=$wyear\">"
					. htmlspecialchars($d[$weekday][$slot]["data"]) . "</a>";
			}
		}
		echo "</td>\n";
		$wt += 86400;
	}
	echo "</tr>\n";
	$t += $resolution;
}
echo "</table>";

if ( $pview != 1 ) show_colour_key();

include "trailer.inc"; 
?>
