<?php
# $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";


function date_time_string($t)
{
        global $twentyfourhour_format;
        if ($twentyfourhour_format)
	{
                $timeformat = "%H:%M:%S";
	}
	else
	{
                # This bit's necessary, because it seems %p in strftime format
                # strings doesn't work
                $ampm = utf8_date("a",$t);
                $timeformat = "%I:%M:%S$ampm";
	}
	return utf8_strftime("%A %d %B %Y ".$timeformat, $t);
}

# Convert a start time and end time to a plain language description.
# This is similar but different from the way it is done in view_entry.
function describe_span($starts, $ends)
{
	global $twentyfourhour_format;
	$start_date = utf8_strftime('%A %d %B %Y', $starts);
        if ($twentyfourhour_format)
	{
                $timeformat = "%H:%M:%S";
	}
	else
	{
                # This bit's necessary, because it seems %p in strftime format
                # strings doesn't work
                $ampm = utf8_date("a",$starts);
                $timeformat = "%I:%M:%S$ampm";
	}
	$start_time = utf8_strftime($timeformat, $starts);
	$duration = $ends - $starts;
	if ($start_time == "00:00:00" && $duration == 60*60*24)
		return $start_date . " - " . get_vocab("all_day");
	toTimeString($duration, $dur_units);
	return $start_date . " " . $start_time . " - " . $duration . " " . $dur_units;
}

# Convert a start period and end period to a plain language description.
# This is similar but different from the way it is done in view_entry.
function describe_period_span($starts, $ends)
{
	list( $start_period, $start_date) =  period_date_string($starts);
	list( , $end_date) =  period_date_string($ends, -1);
	$duration = $ends - $starts;
	toPeriodString($start_period, $duration, $dur_units);
	return $start_date . " - " . $duration . " " . $dur_units;
}

# this is based on describe_span but it displays the start and end
# date/time of an entry
function start_to_end($starts, $ends)
{
	global $twentyfourhour_format;
	$start_date = utf8_strftime('%A %d %B %Y', $starts);
        if ($twentyfourhour_format)
	{
                $timeformat = "%H:%M:%S";
	}
	else
	{
                # This bit's necessary, because it seems %p in strftime format
                # strings doesn't work
                $ampm = utf8_date("a",$starts);
                $timeformat = "%I:%M:%S$ampm";
	}
	$start_time = utf8_strftime($timeformat, $starts);

	$end_date = utf8_strftime('%A %d %B %Y', $ends);
        if ($twentyfourhour_format)
	{
                $timeformat = "%H:%M:%S";
	}
	else
	{
                # This bit's necessary, because it seems %p in strftime format
                # strings doesn't work
                $ampm = utf8_date("a",$ends);
                $timeformat = "%I:%M:%S$ampm";
	}
	$end_time = utf8_strftime($timeformat, $ends);
	return $start_date . " " . $start_time . " - " . $end_date . " " . $end_time;
}


# this is based on describe_period_span but it displays the start and end
# date/period of an entry
function start_to_end_period($starts, $ends)
{
	list( , $start_date) =  period_date_string($starts);
	list( , $end_date) =  period_date_string($ends, -1);
	return $start_date . " - " . $end_date;
}

# Report on one entry. See below for columns in $row[].
# $last_area_room remembers the current area/room.
# $last_date remembers the current date.
function reporton(&$row, &$last_area_room, &$last_date, $sortby, $display)
{
	global $typel;
        global $enable_periods;
	# Display Area/Room, but only when it changes:
	$area_room = htmlspecialchars($row[8]) . " - " . htmlspecialchars($row[9]);
	$date = utf8_strftime("%d-%b-%Y", $row[2]);
	# entries to be sorted on area/room
	if( $sortby == "r" )
	{
		if ($area_room != $last_area_room)
			echo "<hr><h2>". get_vocab("room") . ": " . $area_room . "</h2>\n";
		if ($date != $last_date || $area_room != $last_area_room)
		{
			echo "<hr noshade=\"true\"><h3>". get_vocab("date") . " " . $date . "</h3>\n";
			$last_date = $date;
		}
		# remember current area/room that is being processed.
		# this is done here as the if statement above needs the old
		# values
		if ($area_room != $last_area_room)
			$last_area_room = $area_room;
	}
	else
	# entries to be sorted on start date
	{
		if ($date != $last_date)
			echo "<hr><h2>". get_vocab("date") . " " . $date . "</h2>\n";
		if ($area_room != $last_area_room  || $date != $last_date)
		{
			echo "<hr noshade=\"true\"><h3>". get_vocab("room") . ": " . $area_room . "</h3>\n";
			$last_area_room = $area_room;
		}
		# remember current date that is being processed.
		# this is done here as the if statement above needs the old
		# values
		if ($date != $last_date)
			$last_date = $date;
	}

	echo "<hr><table width=\"100%\">\n";

	# Brief Description (title), linked to view_entry:
	echo "<tr><td class=\"BL\"><a href=\"view_entry.php?id=$row[0]\">"
		. htmlspecialchars($row[3]) . "</a></td>\n";

	# what do you want to display duration or end date/time
	if( $display == "d" )
		# Start date/time and duration:
		echo "<td class=\"BR\" align=right>" .
			(empty($enable_periods) ?
				describe_span($row[1], $row[2]) :
				describe_period_span($row[1], $row[2])) .
			"</td></tr>\n";
	else
		# Start date/time and End date/time:
		echo "<td class=\"BR\" align=right>" .
			(empty($enable_periods) ?
				start_to_end($row[1], $row[2]) :
				start_to_end_period($row[1], $row[2])) .
			"</td></tr>\n";

	# Description:
	echo "<tr><td class=\"BL\" colspan=2><b>".get_vocab("description")."</b> " .
		nl2br(htmlspecialchars($row[4])) . "</td></tr>\n";

	# Entry Type:
	$et = empty($typel[$row[5]]) ? "?$row[5]?" : $typel[$row[5]];
	echo "<tr><td class=\"BL\" colspan=2><b>".get_vocab("type")."</b> $et</td></tr>\n";
	# Created by and last update timestamp:
	echo "<tr><td class=\"BL\" colspan=2><small><b>".get_vocab("createdby")."</b> " .
		htmlspecialchars($row[6]) . ", <b>".get_vocab("lastupdate")."</b> " .
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

function accumulate_periods(&$row, &$count, &$hours, $report_start, $report_end,
	&$room_hash, &$name_hash)
{
	global $sumby;
        global $periods;
        $max_periods = count($periods);

	# Use brief description or created by as the name:
	$name = htmlspecialchars($row[($sumby == "d" ? 3 : 6)]);
    # Area and room separated by break:
	$room = htmlspecialchars($row[8]) . "<br>" . htmlspecialchars($row[9]);
	# Accumulate the number of bookings for this room and name:
	@$count[$room][$name]++;
	# Accumulate hours used, clipped to report range dates:
        $dur = (min((int)$row[2], $report_end) - max((int)$row[1], $report_start))/60;
	@$hours[$room][$name] += ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
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
	global $enable_periods;
        
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

	echo "<hr><h1>".
             (empty($enable_periods) ? get_vocab("summary_header") : get_vocab("summary_header_per")).
             "</h1><table border=2 cellspacing=4>\n";
	echo "<tr><td>&nbsp;</td>\n";
	for ($c = 0; $c < $n_rooms; $c++)
	{
		echo "<td class=\"BL\" align=left><b>$rooms[$c]</b></td>\n";
		$col_count_total[$c] = 0;
		$col_hours_total[$c] = 0.0;
	}
	echo "<td class=\"BR\" align=right><br><b>".get_vocab("total")."</b></td></tr>\n";
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
	echo "<tr><td class=\"BR\" align=right><b>".get_vocab("total")."</b></td>\n";
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
	$typematch_default = $typematch;
	$namematch_default = htmlspecialchars($namematch);
	$descrmatch_default = htmlspecialchars($descrmatch);


} else {
	# New report - use defaults.
	$areamatch_default = "";
	$roommatch_default = "";
	$typematch_default = array();
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
# $sortby: r=room, s=start date/time.
if (empty($sortby)) $sortby = "r";
# $display: d=duration, e=start date/time and end date/time.
if (empty($display)) $display = "d";

# Upper part: The form.
if ( $pview != 1 ) {
?>
<h1><?php echo get_vocab("report_on");?></h1>
<form method=get action=report.php>
<table>
<tr><td class="CR"><?php echo get_vocab("report_start");?></td>
    <td class="CL"> <font size="-1">
    <?php genDateSelector("From_", $From_day, $From_month, $From_year); ?>
    </font></td></tr>
<tr><td class="CR"><?php echo get_vocab("report_end");?></td>
    <td class="CL"> <font size="-1">
    <?php genDateSelector("To_", $To_day, $To_month, $To_year); ?>
    </font></td></tr>
<tr><td class="CR"><?php echo get_vocab("match_area");?></td>
    <td class="CL"><input type=text name=areamatch size=18
    value="<?php echo $areamatch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo get_vocab("match_room");?></td>
    <td class="CL"><input type=text name=roommatch size=18
    value="<?php echo $roommatch_default; ?>">
    </td></tr>
<tr><td CLASS=CR><?php echo get_vocab("match_type")?></td>
    <td CLASS=CL valign=top><table><tr><td>
        <select name="typematch[]" multiple="yes">
<?php
foreach( $typel as $key => $val )
{
	if (!empty($val) )
		echo "<option value=\"$key\"" .
		     (is_array($typematch_default) && in_array ( $key, $typematch_default ) ? " selected" : "") .
		     ">$val\n";
}
?></select></td><td><?php echo get_vocab("ctrl_click_type") ?></td></tr></table>
</td></tr>
<tr><td class="CR"><?php echo get_vocab("match_entry");?></td>
    <td class="CL"><input type=text name=namematch size=18
    value="<?php echo $namematch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo get_vocab("match_descr");?></td>
    <td class="CL"><input type=text name=descrmatch size=18
    value="<?php echo $descrmatch_default; ?>">
    </td></tr>
<tr><td class="CR"><?php echo get_vocab("include");?></td>
    <td class="CL">
      <input type=radio name=summarize value=1<?php if ($summarize==1) echo " checked";
        echo ">" . get_vocab("report_only");?>
      <input type=radio name=summarize value=2<?php if ($summarize==2) echo " checked";
        echo ">" . get_vocab("summary_only");?>
      <input type=radio name=summarize value=3<?php if ($summarize==3) echo " checked";
        echo ">" . get_vocab("report_and_summary");?>
    </td></tr>
<tr><td class="CR"><?php echo get_vocab("sort_rep");?></td>
    <td class="CL">
      <input type=radio name=sortby value=r<?php if ($sortby=="r") echo " checked";
        echo ">". get_vocab("room");?>
      <input type=radio name=sortby value=s<?php if ($sortby=="s") echo " checked";
        echo ">". get_vocab("sort_rep_time");?>
    </td></tr>
<tr><td class="CR"><?php echo get_vocab("rep_dsp");?></td>
    <td class="CL">
      <input type=radio name=display value=d<?php if ($display=="d") echo " checked";
        echo ">". get_vocab("rep_dsp_dur");?>
      <input type=radio name=display value=e<?php if ($display=="e") echo " checked";
        echo ">". get_vocab("rep_dsp_end");?>
    </td></tr>
<tr><td class="CR"><?php echo get_vocab("summarize_by");?></td>
    <td class="CL">
      <input type=radio name=sumby value=d<?php if ($sumby=="d") echo " checked";
        echo ">" . get_vocab("sum_by_descrip");?>
      <input type=radio name=sumby value=c<?php if ($sumby=="c") echo " checked";
        echo ">" . get_vocab("sum_by_creator");?>
    </td></tr>
<tr><td colspan=2 align=center><input type=submit value="<?php echo get_vocab("submitquery") ?>">
</td></tr>
</table>
</form>

<?php
}
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
	if (!empty($typematch)) {
		$sql .= " AND ";
		if( count( $typematch ) > 1 )
		{
			$or_array = array();
			foreach ( $typematch as $type ){
				$or_array[] = "e.type = '$type'";
			}
			$sql .= "(". implode( " OR ", $or_array ) .")";
		}
		else
		{
			$sql .= "e.type = '".$typematch[0]."'";
		}
	}
	if (!empty($namematch))
		$sql .= " AND" .  sql_syntax_caseless_contains("e.name", $namematch);
	if (!empty($descrmatch))
		$sql .= " AND" .  sql_syntax_caseless_contains("e.description", $descrmatch);
    if (!empty($creatormatch))
        $sql .= " AND" .  sql_syntax_caseless_contains("e.create_by", $creatormatch);

	
	if( $sortby == "r" )
		# Order by Area, Room, Start date/time
		$sql .= " ORDER BY 9,10,2";
	else
		# Order by Start date/time, Area, Room
		$sql .= " ORDER BY 2,9,10";

	# echo "<p>DEBUG: SQL: <tt> $sql </tt>\n";

	$res = sql_query($sql);
	if (! $res) fatal_error(0, sql_error());
	$nmatch = sql_count($res);
	if ($nmatch == 0)
	{
		echo "<P><B>" . get_vocab("nothing_found") . "</B>\n";
		sql_free($res);
	}
	else
	{
		$last_area_room = "";
		$last_date = "";
		echo "<P><B>" . $nmatch . " "
		. ($nmatch == 1 ? get_vocab("entry_found") : get_vocab("entries_found"))
		.  "</B>\n";

		for ($i = 0; ($row = sql_row($res, $i)); $i++)
		{
			if ($summarize & 1)
				reporton($row, $last_area_room, $last_date, $sortby, $display);

			if ($summarize & 2)
				(empty($enable_periods) ?
                                 accumulate($row, $count, $hours, $report_start, $report_end,
					$room_hash, $name_hash) :
                                 accumulate_periods($row, $count, $hours, $report_start, $report_end,
					$room_hash, $name_hash)
                                );
		}
		if ($summarize & 2)
			do_summary($count, $hours, $room_hash, $name_hash);
	}
}

include "trailer.inc";