<?
include "config.inc";
include "functions.inc";
include "connect.inc";
?>

<HTML>
<HEAD><TITLE>WebCalendar</TITLE>
<?include "style.inc"?>
</HEAD>
<BODY>

<h2>Administration</h2>

<table border=1>
<tr>
<th><center><b>Areas</b></center></th>
<th><center><b>Rooms <? if ($area) { echo "in $area_name";}?></b></center></th>
</tr>

<tr>
<td>
<? 
# This cell has the areas
$res = mysql_query("select id, area_name from mrbs_area order by area_name");
echo mysql_error();

if (mysql_num_rows($res) == 0) {
	echo "No Areas";
} else {
	echo "<ul>";
	while ($row = mysql_fetch_row($res)) {
		$area_name_q = urlencode($row[1]);
		echo "<li><a href=admin.php3?area=$row[0]&area_name=$area_name_q>$row[1]</a> (<a href=del.php3?type=area&area=$row[0]>Delete</a>)";
	}
	echo "</ul>";
}
?>
</td>
<td>
<?
# This one has the rooms
if ($area) {
	$res = mysql_query("select id, room_name, description, capacity from mrbs_room where area_id=$area order by room_name");
	if (mysql_num_rows($res) == 0) {
		echo "No rooms";
	} else {
		echo "<ul>";
		while ($row = mysql_fetch_row($res)) {
			echo "<li>$row[1] ($row[2], $row[3]) (<a href=del.php3?type=room&room=$row[0]>Delete</a>)";
		}
		echo "</ul>";
	}
} else {
	echo "No area selected";
}

?>

</tr>
<tr>
<td>
<h3>Add Area</h3>
<form action=add.php3 method=post>
<input type=hidden name=type value=area>
<input type=text name=name><br>
<input type=submit>
</form>
</td>

<td>
<? if ($area) { ?>
<h3>Add Room</h3>
<form action=add.php3 method=post>
<input type=hidden name=type value=room>
<input type=hidden name=area value=<? echo $area; ?>>
Name:        <input type=text name=name><br>
Description: <input type=text name=description><br>
Capacity:    <input type=text name=capacity><br>
<input type=submit>
</form>
<? } else { echo "&nbsp;"; }?>
</td>
</tr>
</table>


<? include "trailer.inc" ?>

</html>
