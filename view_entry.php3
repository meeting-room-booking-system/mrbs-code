<?php

include "config.inc";
include "functions.inc";
include "connect.inc";

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


#We do all of our date formatting with mySQL, because its nice like that
$sql = "select name, description, date_format(start_time, '%W, %M %D, %Y'), date_format(start_time, '%k:%i'),
		  sec_to_time((unix_timestamp(end_time) - unix_timestamp(start_time))), type, create_by, 
		  date_format(timestamp, '%W, %M %D, %Y')
		  from mrbs_entry where id='$id'";

$res = mysql_query($sql);
$row = mysql_fetch_row($res);

if (mysql_error()) {
	echo mysql_error();
	exit;
}

$name        = $row[0];
$description = $row[1];
$start_date  = $row[2];
$start_time  = $row[3];
$duration    = $row[4];
$type        = $row[5];
$create_by   = $row[6];
$updated     = $row[7];

#make a nice little array so we can write the type in english easily
$typel[I] = "Internal";
$typel[E] = "External";


#now that we know all the data we start drawing it
echo "<h3>$name</h3>";

#keep everything nicely formatted by slipping a table in here
echo "<table>";

echo "<tr><td><b>$lang[description]</b></td><td>" . nl2br($description) . "</td></tr>";
echo "<tr><td><b>$lang[date]</b></td><td>$start_date</td></tr>";
echo "<tr><td><b>$lang[time]</b></td><td>$start_time</td></tr>";
echo "<tr><td><b>$lang[duration]</b></td><td>$duration</td></tr>";
echo "<tr><td><b>$lang[type]</b></td><td>$typel[$type]</td></tr>";
echo "<tr><td><b>$lang[createdby]</b></td><td>".gethostbyaddr($create_by)."</td></tr>";
echo "<tr><td><b>$lang[lastupdate]</b></td><td>$updated</td></tr>";

echo "</table><br><p>";


# We only want the person who originally created the booking to be able to change it,
# so check $REMOTE_ADDR against $create_by and allow modification if they match

if ($REMOTE_ADDR == $create_by) {
	echo "<a href=\"edit_entry.php3?id=$id\">$lang[editentry]</a><br>";
	echo "<A HREF=\"del_entry.php3?id=$id\" onClick=\"return confirm('$lang[confirmdel]');\">$lang[deleteentry]</A><BR>\n";

}

echo "<a href=$HTTP_REFERER>$lang[returnprev]</a>";


include "trailer.inc"; ?>

</BODY>
</HTML>
