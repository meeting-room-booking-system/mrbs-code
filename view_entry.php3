<?php

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
        $day   = date("d");
        $month = date("m");
        $year  = date("Y");
}

if(!isset($area))
        $area = 0;

print_header($day, $month, $year, $area);

# Find all the data about our booking
$sql = "
SELECT mrbs_entry.name,
       mrbs_entry.description,
       mrbs_entry.create_by,
       mrbs_room.room_name,
       mrbs_area.area_name,
       mrbs_entry.type,
       mrbs_entry.room_id,
       mrbs_entry.repeat_id,
       unix_timestamp(mrbs_entry.timestamp),
       (mrbs_entry.end_time - mrbs_entry.start_time),
       mrbs_entry.start_time,
       mrbs_entry.end_time

FROM mrbs_entry
  LEFT JOIN mrbs_room ON (mrbs_entry.room_id = mrbs_room.id)
  LEFT JOIN mrbs_area ON (mrbs_room.area_id  = mrbs_area.id)

WHERE mrbs_entry.id='$id'
";

$res = mysql_query($sql);

if(mysql_num_rows($res) < 1)
{
  echo "Invalid entry id.";
  exit;
}

$row = mysql_fetch_row($res);

$name         = htmlspecialchars(stripslashes($row[0]));
$description  = htmlspecialchars(stripslashes($row[1]));
$create_by    = htmlspecialchars($row[2]);
$room_name    = htmlspecialchars($row[3]);
$area_name    = htmlspecialchars($row[4]);
$type         = $row[5];
$room_id      = $row[6];
$repeat_id    = $row[7];
$updated      = strftime('%X - %A %d %B %Y', $row[8]);
$duration     = $row[9];

if($display_time = strftime('%X', $duration) != "00:00:00")
{
	$start_date = strftime('%X - %A %d %B %Y', $row[10]);
	
	if($duration <= (60 * 60 * 12))
		$end_date = strftime('%X', $row[11]);
	else
		$end_date = strftime('%X - %A %d %B %Y', $row[11]);
}
else
{
	$start_date = strftime('%A %d %B %Y', $row[10]);
	$end_date   = strftime('%A %d %B %Y', $row[11]);
}

$rep_type = 0;

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
$typel["I"] = $lang["internal"];
$typel["E"] = $lang["external"];

$repeat_key = "rep_type_" . $rep_type;

# Now that we know all the data we start drawing it

?>

<H3><? echo $name ?></H3>
 <table border=0>
   <tr>
    <td><b><? echo $lang["description"] ?></b></td>
    <td><?    echo nl2br($description)  ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["room"]                           ?></b></td>
    <td><?    echo  nl2br($area_name . " - " . $room_name) ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["start_date"] ?></b></td>
    <td><?    echo $start_date         ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["duration"]            ?></b></td>
    <td><?    echo $duration . " " . $lang[$dur_units] ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["end_date"] ?></b></td>
    <td><?    echo $end_date         ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["type"]   ?></b></td>
    <td><?    echo $typel[$type]   ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["createdby"] ?></b></td>
    <td><?    echo $create_by         ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["lastupdate"] ?></b></td>
    <td><?    echo $updated            ?></td>
   </tr>
   <tr>
    <td><b><? echo $lang["rep_type"]  ?></b></td>
    <td><?    echo $lang[$repeat_key] ?></td>
   </tr>
<?

if($rep_type != 0)
{
	switch($rep_type)
	{
		case 2:
//			$opt  = $rep_opt[0] ? "Sunday " : "";
//			$opt .= $rep_opt[1] ? "Monday " : "";
//			$opt .= $rep_opt[2] ? "Tuesday " : "";
//			$opt .= $rep_opt[3] ? "Wednesday " : "";
//			$opt .= $rep_opt[4] ? "Thursday " : "";
//			$opt .= $rep_opt[5] ? "Friday " : "";
//			$opt .= $rep_opt[6] ? "Saturday " : "";
// Display day names according to language and use preferred weekday to start week
			for ($opt = "", $i = 0 + ($weekstarts == 1); $i <= 6 + ($weekstarts == 1); $i++) {
				$opt .= $rep_opt[$i%7] ? strftime("%A", mktime(0,0,0,1,2+$i))." " : "";
			}
			break;
		
		default:
			$opt = "";
	}
	
	if($opt)
		echo "<tr><td><b>$lang[rep_rep_day]</b></td><td>$opt</td></tr>\n";
	
	echo "<tr><td><b>$lang[rep_end_date]</b></td><td>$rep_end_date</td></tr>\n";
}

?>
</table>
<br>
<p>
<a href="edit_entry.php3?id=<? echo $id ?>"><? echo $lang["editentry"] ?></a>
<?

if($repeat_id)
	echo " - <a href=\"edit_entry.php3?id=$id&edit_type=series&day=$day&month=$month&year=$year\">$lang[editseries]</a>";

?>
<BR>
<A HREF="del_entry.php3?id=<? echo $id ?>&series=0" onClick="return confirm('<? echo $lang["confirmdel"] ?>');"><? echo $lang["deleteentry"] ?></A>
<?

if($repeat_id)
	echo " - <A HREF=\"del_entry.php3?id=$id&series=1&day=$day&month=$month&year=$year\" onClick=\"return confirm('$lang[confirmdel]');\">$lang[deleteseries]</A>";

?>
<BR>
<a href=<? echo $HTTP_REFERER ?>><? echo $lang["returnprev"] ?></a>
<? include "trailer.inc"; ?>
</BODY>
</HTML>
