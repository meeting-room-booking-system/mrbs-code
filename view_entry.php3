<?php

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

print_header($day, $month, $year, $area);

# Find all the data about our booking
$sql = "select name, description, start_time, start_time,
		  (end_time - start_time), type, create_by, 
		  unix_timestamp(timestamp), end_time, repeat_id
		  from mrbs_entry where id='$id'";

$res = mysql_query($sql);

if(mysql_num_rows($res) < 1)
{
  echo "Invalid entry id.";
  exit;
}

$row = mysql_fetch_row($res);

$display_time = strftime('%X',$row[3]) != "00:00:00";

$duration    = $row[4];
$type        = $row[5];
$name        = htmlspecialchars($row[0]);
$description = htmlspecialchars($row[1]);
$create_by   = htmlspecialchars($row[6]);
$updated     = strftime('%X - %A %d %B %Y', $row[7]);

if($display_time)
{
	$start_date = strftime('%X - %A %d %B %Y', $row[2]);
	
	if($duration <= (60 * 60 * 12))
		$end_date = strftime('%X', $row[8]);
	else
		$end_date = strftime('%X - %A %d %B %Y', $row[8]);
}
else
{
	$start_date = strftime('%A %d %B %Y', $row[2]);
	$end_date   = strftime('%A %d %B %Y', $row[8]);
}

$repeat_id   = $row[9];
$rep_type    = 0;

if($repeat_id != 0)
{
	$res = mysql_query("SELECT rep_type, end_date, rep_opt
	                    FROM mrbs_repeat WHERE id='$repeat_id'");
	
	if(mysql_num_rows($res) > 0)
	{
		$row = mysql_fetch_row($res);
		
		$rep_type     = $row[0];
		$rep_end_date = strftime('%A %d %B %Y',$row[1]);
		$rep_opt      = $row[2];
	}
}

toTimeString($duration, $dur_units);

# Make a nice little array so we can write the type in english easily
$typel[I] = "Internal";
$typel[E] = "External";

#now that we know all the data we start drawing it
echo "<H3>$name</H3>\n";

#keep everything nicely formatted by slipping a table in here
echo "<table border=0>\n";

echo "<tr><td><b>$lang[description]</b></td><td>" . nl2br($description) . "</td></tr>\n";
echo "<tr><td><b>$lang[start_date]</b></td><td>$start_date</td></tr>\n";
echo "<tr><td><b>$lang[duration]</b></td><td>$duration $dur_units</td></tr>\n";
echo "<tr><td><b>$lang[end_date]</b></td><td>$end_date</td></tr>\n";

echo "<tr><td><b>$lang[type]</b></td><td>$typel[$type]</td></tr>\n";
echo "<tr><td><b>$lang[createdby]</b></td><td>$create_by</td></tr>\n";
echo "<tr><td><b>$lang[lastupdate]</b></td><td>$updated</td></tr>\n";

$key = "rep_type_".$rep_type;
echo "<tr><td><b>$lang[rep_type]</b></td><td>$lang[$key]</td></tr>\n";

if($rep_type != 0)
{
	switch($rep_type)
	{
		case 2:
			$opt .= $rep_opt[0] ? "Sunday " : "";
			$opt  = $rep_opt[1] ? "Monday " : "";
			$opt .= $rep_opt[2] ? "Tuesday " : "";
			$opt .= $rep_opt[3] ? "Wednesday " : "";
			$opt .= $rep_opt[4] ? "Thursday " : "";
			$opt .= $rep_opt[5] ? "Friday " : "";
			$opt .= $rep_opt[6] ? "Saturday " : "";
			break;
		
		default:
			$opt = "";
	}
	
	if($opt)
		echo "<tr><td><b>$lang[rep_rep_day]</b></td><td>$opt</td></tr>\n";
	
	echo "<tr><td><b>$lang[rep_end_date]</b></td><td>$rep_end_date</td></tr>\n";
}

echo "</table><br><p>\n\n";

echo "<a href=\"edit_entry.php3?id=$id&day=$day&month=$month&year=$year\">$lang[editentry]</a>";
if($repeat_id)
	echo " - <a href=\"edit_entry.php3?id=$id&edit_type=series&day=$day&month=$month&year=$year\">$lang[editseries]</a>";
echo "<BR>\n";

echo "<A HREF=\"del_entry.php3?id=$id&series=0&day=$day&month=$month&year=$year\" onClick=\"return confirm('$lang[confirmdel]');\">$lang[deleteentry]</A>";
if($repeat_id)
	echo " - <A HREF=\"del_entry.php3?id=$id&series=1&day=$day&month=$month&year=$year\" onClick=\"return confirm('$lang[confirmdel]');\">$lang[deleteseries]</A>";
echo "<BR>\n";

echo "<a href=$HTTP_REFERER>$lang[returnprev]</a>";

include "trailer.inc"; ?>

</BODY>
</HTML>
