<?php

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

?>
<HTML>
<HEAD>
<TITLE><?echo $lang[mrbs]?></TITLE>
<?include "style.inc"?>
</HEAD>
<BODY>

<?

if ( $id < 1 ) {
  echo "Invalid entry id.";
  exit;
}


#Find all the data about our booking
$sql = "select name, description, start_time, start_time,
		  (end_time - start_time), type, create_by, 
		  unix_timestamp(timestamp)
		  from mrbs_entry where id='$id'";

$res = mysql_query($sql);
$row = mysql_fetch_row($res);

if (mysql_error()) {
	echo mysql_error();
	exit;
}

$name        = $row[0];
$description = $row[1];
$start_date  = strftime('%A %d %B %Y',$row[2]);
$start_time  = strftime('%X',$row[3]);
$duration    = $row[4];
$type        = $row[5];
$create_by   = $row[6];
$updated     = strftime('%X - %A %d %B %Y',$row[7]);

$display_time = $start_time != "01:00:00";

toTimeString($duration, $dur_units);

#make a nice little array so we can write the type in english easily
$typel[I] = "Internal";
$typel[E] = "External";


#now that we know all the data we start drawing it
echo "<H3>$name</H3>\n";

#keep everything nicely formatted by slipping a table in here
echo "<table border=0>\n";

echo "<tr><td><b>$lang[description]</b></td><td>" . nl2br($description) . "</td></tr>\n";
echo "<tr><td><b>$lang[date]</b></td><td>$start_date</td></tr>\n";

if($display_time)
	echo "<tr><td><b>$lang[time]</b></td><td>$start_time</td></tr>\n";

echo "<tr><td><b>$lang[duration]</b></td><td>$duration $dur_units</td></tr>\n";
echo "<tr><td><b>$lang[type]</b></td><td>$typel[$type]</td></tr>\n";
echo "<tr><td><b>$lang[createdby]</b></td><td>$create_by</td></tr>\n";
echo "<tr><td><b>$lang[lastupdate]</b></td><td>$updated</td></tr>\n";

echo "</table><br><p>\n\n";

echo "<a href=\"edit_entry.php3?id=$id\">$lang[editentry]</a><br>";
echo "<A HREF=\"del_entry.php3?id=$id\" onClick=\"return confirm('$lang[confirmdel]');\">$lang[deleteentry]</A><BR>\n";

echo "<a href=$HTTP_REFERER>$lang[returnprev]</a>";

include "trailer.inc"; ?>

</BODY>
</HTML>
