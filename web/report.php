<?php
include "config.inc";
include "functions.inc";
include "$dbsys.inc";

# Convert a start time and end time to a plain language description.
# This is similar but different from the way it is done in view_entry.
function describe_span($starts, $ends)
{
	global $lang;
	$start_date = strftime('%A %d %B %Y', $starts);
	$start_time = strftime("%T", $starts);
	$duration = $ends - $starts;
	if ($start_time == "00:00:00" && $duration == 60*60*24)
		return $start_date . " - " . $lang["all_day"];
	toTimeString($duration, $dur_units);
	return $start_date . " " . $start_time . " - " . $duration . " " . $dur_units;
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

# Upper part: The form.
?>
<h1><? echo $lang["report_on"];?></h1>
<form method=post action=report.php>
<table>
<tr><td class=CR><? echo $lang["start_on_or_after"];?></td>
    <td class=CL> <font size="-1">
    <? genDateSelector("From_", $From_day, $From_month, $From_year); ?>
    </font></td></tr>
<tr><td class=CR><? echo $lang["start_no_later"];?></td>
    <td class=CL> <font size="-1">
    <? genDateSelector("To_", $To_day, $To_month, $To_year); ?>
    </font></td></tr>
<tr><td class=CR><? echo $lang["match_area"];?></td>
    <td class=CL><input type=text name=areamatch size=18
    value="<? echo $areamatch_default; ?>">
    </td></tr>
<tr><td class=CR><? echo $lang["match_room"];?></td>
    <td class=CL><input type=text name=roommatch size=18
    value="<? echo $roommatch_default; ?>">
    </td></tr>
<tr><td class=CR><? echo $lang["match_entry"];?></td>
    <td class=CL><input type=text name=namematch size=18
    value="<? echo $namematch_default; ?>">
    </td></tr>
<tr><td class=CR><? echo $lang["match_descr"];?></td>
    <td class=CL><input type=text name=descrmatch size=18
    value="<? echo $descrmatch_default; ?>">
    </td></tr>
<tr><td colspan=2 align=center><input type=submit>
</td></tr>
</table>
</form>

<?

# Lower part: Results, if called with parameters:
if (isset($areamatch))
{
	# Make sure these are not escape-quoted:
	$areamatch = unslashes($areamatch);
	$roommatch = unslashes($roommatch);
	$namematch = unslashes($namematch);
	$descrmatch = unslashes($descrmatch);

	echo "<H1>$lang[mrbs]</H1>\n";

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

	$sql = "SELECT e.id, e.start_time, e.end_time, e.name, e.description,
		e.type, e.create_by, "
		.  sql_syntax_timestamp_to_unix("e.timestamp")
		. ", a.area_name, r.room_name
		FROM mrbs_entry e, mrbs_area a, mrbs_room r
		WHERE e.room_id = r.id AND r.area_id = a.id
		AND e.start_time BETWEEN "
		. mktime(0, 0, 0, $From_month, $From_day, $From_year)
		. " AND " . mktime(23, 59, 59, $To_month, $To_day, $To_year);

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

	#echo "<p>DEBUG: SQL: <tt> $sql </tt>\n";

	$res = sql_query($sql);
	if (! $res) fatal_error(0, sql_error());
	if (sql_count($res) == 0)
	{
		echo "<P>" . $lang["nothing_found"] . "\n";
		sql_free($res);
	}
	else
	{
		$last_area_room = "";

		for ($i = 0; ($row = sql_row($res, $i)); $i++)
		{
			# Area/Room, but only when it changes:
			$area_room = htmlspecialchars($row[8]) . " - "
				. htmlspecialchars($row[9]);
			if ($area_room != $last_area_room)
			{
				echo "<hr><h2>$lang[room] $area_room</h2>\n";
				$last_area_room = $area_room;
			}

			echo "<hr><table width=\"100%\">\n";

			# Brief Description (title), linked to view_entry:
			echo "<tr><td class=BL><a href=\"view_entry.php?id=$row[0]\">"
				. htmlspecialchars($row[3]) . "</a></td>\n";

			# From date-time and duration:
			echo "<td class=BR>" . describe_span($row[1], $row[2]) . "</td></tr>\n";
			# Description:
			echo "<tr><td class=BL colspan=2><b>$lang[description]</b> " .
				nl2br(htmlspecialchars($row[4])) . "</td></tr>\n";

			# Entry Type:
			$et = empty($typel[$row[5]]) ? "?$row[5]?" : $typel[$row[5]];
			echo "<tr><td class=BL colspan=2><b>$lang[type]</b> $et</td></tr>\n";

			# Created by and last update timestamp:
			echo "<tr><td class=BL colspan=2><small><b>$lang[createdby]</b> " .
				htmlspecialchars($row[6]) . ", <b>$lang[lastupdate]</b> " .
				strftime("%A %d %B %Y %T", $row[7]) . "</small></td></tr>\n";

			echo "</table>\n";
		}
		echo "<hr>\n";
	}
}

include "trailer.inc";
