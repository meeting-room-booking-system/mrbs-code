<?
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

load_user_preferences();

if(!getAuthorised(getUserName(), getUserPassword(), 2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

print_header($day, $month, $year, isset($area) ? $area : 1);

?>

<h2>Administration</h2>

<table border=1>
<tr>
<th><center><b>Areas</b></center></th>
<th><center><b>Rooms <? if ($area) { echo "in $area_name"; }?></b></center></th>
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
<h3 ALIGN=CENTER>Add Area</h3>
<form action=add.php3 method=post>
<input type=hidden name=type value=area>

<TABLE>
<TR><TD>Name:       </TD><TD><input type=text name=name></TD></TR>
</TABLE>
<input type=submit>
</form>
</td>

<td>
<? if ($area) { ?>
<h3 ALIGN=CENTER>Add Room</h3>
<form action=add.php3 method=post>
<input type=hidden name=type value=room>
<input type=hidden name=area value=<? echo $area; ?>>

<TABLE>
<TR><TD>Name:       </TD><TD><input type=text name=name></TD></TR>
<TR><TD>Description:</TD><TD><input type=text name=description></TD></TR>
<TR><TD>Capacity:   </TD><TD><input type=text name=capacity></TD></TR>
</TABLE>
<input type=submit>
</form>
<? } else { echo "&nbsp;"; }?>
</td>
</tr>
</table>

<br>
Your browser is set to use "<b><?echo $HTTP_ACCEPT_LANGUAGE?></b>" language.
<? include "trailer.inc" ?>

</html>
