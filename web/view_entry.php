<?php
# $Id$

include "config.inc";
include "functions.inc";
include "$dbsys.inc";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

print_header($day, $month, $year, $area);

$sql = "
SELECT mrbs_entry.name,
       mrbs_entry.description,
       mrbs_entry.create_by,
       mrbs_room.room_name,
       mrbs_area.area_name,
       mrbs_entry.type,
       mrbs_entry.room_id,
       mrbs_entry.repeat_id,
    " . sql_syntax_timestamp_to_unix("mrbs_entry.timestamp") . ",
       (mrbs_entry.end_time - mrbs_entry.start_time),
       mrbs_entry.start_time,
       mrbs_entry.end_time

FROM mrbs_entry, mrbs_room, mrbs_area
WHERE mrbs_entry.room_id = mrbs_room.id
  AND mrbs_room.area_id = mrbs_area.id
  AND mrbs_entry.id=$id
";

$res = sql_query($sql);
if (! $res) fatal_error(0, sql_error());

if(sql_count($res) < 1) fatal_error(0, "Invalid entry id.");

$row = sql_row($res, 0);
sql_free($res);

# Note: Removed stripslashes() calls from name and description. Previous
# versions of MRBS mistakenly had the backslash-escapes in the actual database
# records because of an extra addslashes going on. Fix your database and
# leave this code alone, please.
$name         = htmlspecialchars($row[0]);
$description  = htmlspecialchars($row[1]);
$create_by    = htmlspecialchars($row[2]);
$room_name    = htmlspecialchars($row[3]);
$area_name    = htmlspecialchars($row[4]);
$type         = $row[5];
$room_id      = $row[6];
$repeat_id    = $row[7];
$updated      = strftime('%X - %A %d %B %Y', $row[8]);
$duration     = $row[9];

$start_date = strftime('%X - %A %d %B %Y', $row[10]);
$end_date = strftime('%X - %A %d %B %Y', $row[11]);

$rep_type = 0;

if($repeat_id != 0)
{
	$res = sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks
	                    FROM mrbs_repeat WHERE id=$repeat_id");
	if (! $res) fatal_error(0, sql_error());

	if (sql_count($res) == 1)
	{
		$row = sql_row($res, 0);
		
		$rep_type     = $row[0];
		$rep_end_date = strftime('%A %d %B %Y',$row[1]);
		$rep_opt      = $row[2];
		$rep_num_weeks = $row[3];
	}
	sql_free($res);
}

toTimeString($duration, $dur_units);

$repeat_key = "rep_type_" . $rep_type;

# Now that we know all the data we start drawing it

?>

<H3><? echo $name ?></H3>
 <table border=0>
   <tr>
    <td><b><? echo $vocab["description"] ?></b></td>
    <td><?    echo nl2br($description)  ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["room"]                           ?></b></td>
    <td><?    echo  nl2br($area_name . " - " . $room_name) ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["start_date"] ?></b></td>
    <td><?    echo $start_date         ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["duration"]            ?></b></td>
    <td><?    echo $duration . " " . $dur_units ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["end_date"] ?></b></td>
    <td><?    echo $end_date         ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["type"]   ?></b></td>
    <td><?    echo empty($typel[$type]) ? "?$type?" : $typel[$type]  ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["createdby"] ?></b></td>
    <td><?    echo $create_by         ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["lastupdate"] ?></b></td>
    <td><?    echo $updated            ?></td>
   </tr>
   <tr>
    <td><b><? echo $vocab["rep_type"]  ?></b></td>
    <td><?    echo $vocab[$repeat_key] ?></td>
   </tr>
<?

if($rep_type != 0)
{
	$opt = "";
	if (($rep_type == 2) || ($rep_type == 6))
	{
		# Display day names according to language and preferred weekday start.
		for ($i = 0; $i < 7; $i++)
		{
			$daynum = ($i + $weekstarts) % 7;
			if ($rep_opt[$daynum]) $opt .= day_name($daynum) . " ";
		}
	}
	if ($rep_type == 6)
	{
		echo "<tr><td><b>$vocab[rep_num_weeks]$vocab[rep_for_nweekly]</b></td><td>$rep_num_weeks</td></tr>\n";
	}
	
	if($opt)
		echo "<tr><td><b>$vocab[rep_rep_day]</b></td><td>$opt</td></tr>\n";
	
	echo "<tr><td><b>$vocab[rep_end_date]</b></td><td>$rep_end_date</td></tr>\n";
}

?>
</table>
<br>
<p>
<a href="edit_entry.php?id=<? echo $id ?>"><? echo $vocab["editentry"] ?></a>
<?

if($repeat_id)
	echo " - <a href=\"edit_entry.php?id=$id&edit_type=series&day=$day&month=$month&year=$year\">$vocab[editseries]</a>";

?>
<BR>
<A HREF="del_entry.php?id=<? echo $id ?>&series=0" onClick="return confirm('<? echo $vocab["confirmdel"] ?>');"><? echo $vocab["deleteentry"] ?></A>
<?

if($repeat_id)
	echo " - <A HREF=\"del_entry.php?id=$id&series=1&day=$day&month=$month&year=$year\" onClick=\"return confirm('$vocab[confirmdel]');\">$vocab[deleteseries]</A>";

?>
<BR>
<a href="<? echo $HTTP_REFERER ?>"><? echo $vocab["returnprev"] ?></a>
<? include "trailer.inc"; ?>
