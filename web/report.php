<?php
# $Id$

require_once "grab_globals.inc.php";
include "config.inc";
include "functions.inc";
include "$dbsys.inc";


function date_time_string($t)
{
        global $twentyfourhour_format;
        if ($twentyfourhour_format)
	{
                $timeformat = "%T";
	}
	else
	{
                # This bit's necessary, because it seems %p in strftime format
                # strings doesn't work
                $ampm = date("a",$t);
                $timeformat = "%I:%M:%S$ampm";
	}
	return strftime("%A %d %B %Y ".$timeformat, $t);
}

# Convert a start time and end time to a plain language description.
# This is similar but different from the way it is done in view_entry.
function describe_span($starts, $ends)
{
	global $vocab, $twentyfourhour_format;
	$start_date = strftime('%A %d %B %Y', $starts);
        if ($twentyfourhour_format)
	{
                $timeformat = "%T";
	}
	else
	{
                # This bit's necessary, because it seems %p in strftime format
                # strings doesn't work
                $ampm = date("a",$starts);
                $timeformat = "%I:%M:%S$ampm";
	}
	$start_time = strftime($timeformat, $starts);
	$duration = $ends - $starts;
	if ($start_time == "00:00:00" && $duration == 60*60*24)
		return $start_date . " - " . $vocab["all_day"];
	toTimeString($duration, $dur_units);
	return $start_date . " " . $start_time . " - " . $duration . " " . $dur_units;
}

# Report on one entry. See below for columns in $row[].
# $last_area_room remembers the current area/room.
function reporton(&$row, &$last_area_room)
{
	global $vocab, $typel;
	# Display Area/Room, but only when it changes:
	$area_room = htmlspecialchars($row[8]) . " - " . htmlspecialchars($row[9]);
	if ($area_room != $last_area_room)
	{
		echo "<hr><h2>$vocab[room] $area_room</h2>\n";
		$last_area_room = $area_room;
	}

	echo "<hr><table width=\"100%\">\n";

	# Brief Description (title), linked to view_entry:
	echo "<tr><td class=\"BL\"><a href=\"view_entry.php?id=$row[0]\">"
		. htmlspecialchars($row[3]) . "</a></td>\n";

	# From date-time and duration:
	echo "<td class=\"BR\" align=right>" . describe_span($row[1], $row[2]) . "</td></tr>\n";
	# Description:
	echo "<tr><td class=\"BL\" colspan=2><b>$vocab[description]</b> " .
		nl2br(htmlspecialchars($row[4])) . "</td></tr>\n";

	# Entry Type:
	$et = empty($typel[$row[5]]) ? "?$row[5]?" : $typel[$row[5]];
	echo "<tr><td class=\"BL\" colspan=2><b>$vocab[type]</b> $et</td></tr>\n";
	# Created by and last update timestamp:
	echo "<tr><td class=\"BL\" colspan=2><small><b>$vocab[createdby]</b> " .
		htmlspecialchars($row[6]) . ", <b>$vocab[lastupdate]</b> " .
		date_time_string($row[7]) . "</small></td></tr>\n";

	echo "</table>\n";
}

# Collect summary statistics on one entry. See below for columns in $row[].
# $sumby selects grouping on brief description (d) or created by (c).
# This also builds hash tables of all unique names and rooms. When sorted,
# these will become the column and row headers of the summary table.
function accumulate(&$row, &$count, &$hours, $report_start, $report_end,
	&$room_hash, &$name_hash)
{
	global $sumby;
	# Use brief description or created by as the name:
	$name = htmlspecialchars($row[($sumby == "d" ? 3 : 6)]);
    # Area and room separated by break:
	$room = htmlspecialchars($row[8]) . "<br>" . htmlspecialchars($row[9]);
	# Accumulate the number of bookings for this room and name:
	@$count[$room][$name]++;
	# Accumulate hours used, clipped to report range dates:
	@$hours[$room][$name] += (min((int)$row[2], $report_end)
		- max((int)$row[1], $report_start)) / 3600.0;
	$room_hash[$room] = 1;
	$name_hash[$name] = 1;
}

# Output a table cell containing a count (integer) and hours (float):
function cell($count, $hours)
{
	echo "<td class=\"BR\" align=right>($count) "
	. sprintf("%.2f", $hours) . "</td>\n";
}

# Output the summary table (a "cross-tab report"). $count and $hours are
# 2-dimensional sparse arrays indexed by [area/room][name].
# $room_hash & $name_hash are arrays with indexes naming unique rooms and names.
function do_summary(&$count, &$hours, &$room_hash, &$name_hash)
{
	global $vocab;

	# Make a sorted array of area/rooms, and of names, to use for column
	# and row indexes. Use the rooms and names hashes built by accumulate().
	# At PHP4 we could use array_keys().
	reset($room_hash);
	while (list($room_key) = each($room_hash)) $rooms[] = $room_key;
	ksort($rooms);
	reset($name_hash);
	while (list($name_key) = each($name_hash)) $names[] = $name_key;
	ksort($names);
	$n_rooms = sizeof($rooms);
	$n_names = sizeof($names);

	echo "<hr><h1>$vocab[summary_header]</h1><table border=2 cellspacing=4>\n";
	echo "<tr><td>&nbsp;</td>\n";
	for ($c = 0; $c < $n_rooms; $c++)
	{
		echo "<td class=\"BL\" align=left><b>$rooms[$c]</b></td>\n";
		$col_count_total[$c] = 0;
		$col_hours_total[$c] = 0.0;
	}
	echo "<td class=\"BR\" align=right><br><b>$vocab[total]</b></td></tr>\n";
	$grand_count_total = 0;
	$grand_hours_total = 0;

	for ($r = 0; $r < $n_names; $r++)
	{
		$row_count_total = 0;
		$row_hours_total = 0.0;
		$name = $names[$r];
		echo "<tr><td class=\"BR\" align=right><b>$name</b></td>\n";
		for ($c = 0; $c < $n_rooms; $c++)
		{
			$room = $rooms[$c];
			if (isset($count[$room][$name]))
			{
				$count_val = $count[$room][$name];
				$hours_val = $hours[$room][$name];
				cell($count_val, $hours_val);
				$row_count_total += $count_val;
				$row_hours_total += $hours_val;
				$col_count_total[$c] += $count_val;
				$col_hours_total[$c] += $hours_val;
			} else {
				echo "<td>&nbsp;</td>\n";
			}
		}
		cell($row_count_total, $row_hours_total);
		echo "</tr>\n";
		$grand_count_total += $row_count_total;
		$grand_hours_total += $row_hours_total;
	}
	echo "<tr><td class=\"BR\" align=right><b>$vocab[total]</b></td>\n";
	for ($c = 0; $c < $n_rooms; $c++)
		cell($col_count_total[$c], $col_hours_total[$c]);
	cell($grand_count_total, $grand_hours_total);
	echo "</tr></table>\n";
}

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

# print the page header
print_header($day, $month, $year, $area);

if (isset($areamatch))
{
	# Resubmit - reapply parameters as defaults.
	# Make sure these are not escape-quoted:
	$areamatch = unslashes($areamatch);
	$roommatch = unslashes($roommatch);
	$namematch = unslashes($namematch);
	$descrmatch = unslashes($descrmatch);

	# Make default values when the form is reused.
	$areamatch_default = htmlspecialchars($areamatch);
	$roommatch_default = htmlspecialchars($roommatch);
	$namematch_default = htmlspecialchars($namematch);
	$descrmatch_default = htmlspecialchars($descrmatch);


} else {
	# New report - use defaults.
	$areamatch_default = "";
	$roommatch_default = "";
	$namematch_default = "";
	$descrmatch_default = "";
	$From_day = $day;
	$From_month = $month;
	$From_year = $year;
	$To_time = mktime(0, 0, 0, $month, $day + $default_report_days, $year);
	$To_day   = date("d", $To_time);
	$To_month = date("m", $To_time);
	$To_year  = date("Y", $To_time);
}
# $summarize: 1=report only, 2=summary only, 3=both.
if (empty($summarize)) $summarize = 1;
# $sumby: d=by brief description, c=by creator.
if (empty($sumby)) $sumby = "d";

# Upper part: The form.
?>
<h1><?php echo $vocab["report_on"];?></h1>
<form method=post action=report.php>
<table>
<tr><td class="CR"><?php echo $vocab["report_start"];?></td>
    <td class="CL"> <font size="-1">
    <?php genDateSelector("From_", $From_day, $From_month, $From_year); ?>
    </font></td></tr>
<tr><td class="CR"><?php echo $vocab["report_end"];?></td>
    <td class="CL"> <font size="-1">
    <?php genDateSelector("To_", $To_day, $To_month, $To_year); ?>
    </font></td></tr>
<tr><td class="CR"><?php echo $vocab["match_area"];?></td>
    <td class="CL"><input type=text name=areamatch size=18
    value="<?php echo $areamatch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo $vocab["match_room"];?></td>
    <td class="CL"><input type=text name=roommatch size=18
    value="<?php echo $roommatch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo $vocab["match_entry"];?></td>
    <td class="CL"><input type=text name=namematch size=18
    value="<?php echo $namematch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo $vocab["match_descr"];?></td>
    <td class="CL"><input type=text name=descrmatch size=18
    value="<?php echo $descrmatch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo $vocab["include"];?></td>
    <td class="CL">
      <input type=radio name=summarize value=1<?php if ($summarize==1) echo " checked";
        echo ">" . $vocab["report_only"];?>
      <input type=radio name=summarize value=2<?php if ($summarize==2) echo " checked";
        echo ">" . $vocab["summary_only"];?>
      <input type=radio name=summarize value=3<?php if ($summarize==3) echo " checked";
        echo ">" . $vocab["report_and_summary"];?>
    </td></tr>
<tr><td class="CR"><?php echo $vocab["summarize_by"];?></td>
    <td class="CL">
      <input type=radio name=sumby value=d<?php if ($sumby=="d") echo " checked";
        echo ">" . $vocab["sum_by_descrip"];?>
      <input type=radio name=sumby value=c<?php if ($sumby=="c") echo " checked";
        echo ">" . $vocab["sum_by_creator"];?>
    </td></tr>
<tr><td colspan=2 align=center><input type=submit value="<?php echo $vocab['submitquery'] ?>">
</td></tr>
</table>
</form>

<?php

# Lower part: Results, if called with parameters:
if (isset($areamatch))
{
	# Make sure these are not escape-quoted:
	$areamatch = unslashes($areamatch);
	$roommatch = unslashes($roommatch);
	$namematch = unslashes($namematch);
	$descrmatch = unslashes($descrmatch);

	# Start and end times are also used to clip the times for summary info.
	$report_start = mktime(0, 0, 0, $From_month, $From_day, $From_year);
	$report_end = mktime(0, 0, 0, $To_month, $To_day+1, $To_year);

#   SQL result will contain the following columns:
# Col Index  Description:
#   1  [0]   Entry ID, not displayed -- used for linking to View script.
#   2  [1]   Start time as Unix time_t
#   3  [2]   End time as Unix time_t
#   4  [3]   Entry name or short description, must be HTML escaped
#   5  [4]   Entry description, must be HTML escaped
#   6  [5]   Type, single char mapped to a string
#   7  [6]   Created by (user name or IP addr), must be HTML escaped
#   8  [7]   Creation timestamp, converted to Unix time_t by the database
#   9  [8]   Area name, must be HTML escaped
#  10  [9]   Room name, must be HTML escaped

	$sql = "SELECT e.id, e.start_time, e.end_time, e.name, e.description, "
		. "e.type, e.create_by, "
		.  sql_syntax_timestamp_to_unix("e.timestamp")
		. ", a.area_name, r.room_name"
		. " FROM mrbs_entry e, mrbs_area a, mrbs_room r"
		. " WHERE e.room_id = r.id AND r.area_id = a.id"
		. " AND e.start_time < $report_end AND e.end_time > $report_start";

	if (!empty($areamatch))
		$sql .= " AND" .  sql_syntax_caseless_contains("a.area_name", $areamatch);
	if (!empty($roommatch))
		$sql .= " AND" .  sql_syntax_caseless_contains("r.room_name", $roommatch);
	if (!empty($namematch))
		$sql .= " AND" .  sql_syntax_caseless_contains("e.name", $namematch);
	if (!empty($descrmatch))
		$sql .= " AND" .  sql_syntax_caseless_contains("e.description", $descrmatch);

	# Order by Area, Room, Start date/time:
	$sql .= " ORDER BY 9,10,2";

	# echo "<p>DEBUG: SQL: <tt> $sql </tt>\n";

	$res = sql_query($sql);
	if (! $res) fatal_error(0, sql_error());
	$nmatch = sql_count($res);
	if ($nmatch == 0)
	{
		echo "<P><B>" . $vocab["nothing_found"] . "</B>\n";
		sql_free($res);
	}
	else
	{
		$last_area_room = "";
		echo "<P><B>" . $nmatch . " "
		. ($nmatch == 1 ? $vocab["entry_found"] : $vocab["entries_found"])
		.  "</B>\n";

		for ($i = 0; ($row = sql_row($res, $i)); $i++)
		{
			if ($summarize & 1)
				reporton($row, $last_area_room);

			if ($summarize & 2)
				accumulate($row, $count, $hours, $report_start, $report_end,
					$room_hash, $name_hash);
		}
		if ($summarize & 2)
			do_summary($count, $hours, $room_hash, $name_hash);
	}
}

include "trailer.inc";
