<?php

# $Id$

include "config.inc";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if(!getAuthorised(getUserName(), getUserPassword(), 2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

// If area is set but area name is not known, get the name.
if (isset($area))
{
	if (empty($area_name))
	{
		$res = sql_query("select area_name from mrbs_area where id=$area");
    	if (! $res) fatal_error(0, sql_error());
		if (sql_count($res) == 1)
		{
			$row = sql_row($res, 0);
			$area_name = $row[0];
		}
		sql_free($res);
	} else {
		$area_name = unslashes($area_name);
	}
}
?>

<h2>Administration</h2>

<table border=1>
<tr>
<th><center><b><?php echo $vocab['areas'] ?></b></center></th>
<th><center><b><?php echo $vocab['rooms'] ?> <?php if(isset($area)) { echo $vocab['in'] . " " .
  htmlspecialchars($area_name); }?></b></center></th>
</tr>

<tr>
<td>
<?php 
# This cell has the areas
$res = sql_query("select id, area_name from mrbs_area order by area_name");
if (! $res) fatal_error(0, sql_error());

if (sql_count($res) == 0) {
	echo $vocab['noareas'];
} else {
	echo "<ul>";
	for ($i = 0; ($row = sql_row($res, $i)); $i++) {
		$area_name_q = urlencode($row[1]);
		echo "<li><a href=\"admin.php?area=$row[0]&area_name=$area_name_q\">"
			. htmlspecialchars($row[1]) . "</a> (<a href=\"edit_area_room.php?area=$row[0]\">" . $vocab['edit'] . "</a>) (<a href=\"del.php?type=area&area=$row[0]\">" .  $vocab['delete'] . "</a>)\n";
	}
	echo "</ul>";
}
?>
</td>
<td>
<?php
# This one has the rooms
if(isset($area)) {
	$res = sql_query("select id, room_name, description, capacity from mrbs_room where area_id=$area order by room_name");
	if (! $res) fatal_error(0, sql_error());
	if (sql_count($res) == 0) {
		echo $vocab['norooms'];
	} else {
		echo "<ul>";
		for ($i = 0; ($row = sql_row($res, $i)); $i++) {
			echo "<li>" . htmlspecialchars($row[1]) . "(" . htmlspecialchars($row[2])
			. ", $row[3]) (<a href=\"edit_area_room.php?room=$row[0]\">" .  $vocab['edit'] . "</a>) (<a href=\"del.php?type=room&room=$row[0]\">" .  $vocab['delete'] . "</a>)\n";
		}
		echo "</ul>";
	}
} else {
	echo $vocab['noarea'];
}

?>

</tr>
<tr>
<td>
<h3 ALIGN=CENTER><?php echo $vocab['addarea'] ?></h3>
<form action=add.php method=post>
<input type=hidden name=type value=area>

<TABLE>
<TR><TD><?php echo $vocab['name'] ?>:       </TD><TD><input type=text name=name></TD></TR>
</TABLE>
<input type=submit value="<?php echo $vocab['addarea'] ?>">
</form>
</td>

<td>
<?php if(isset($area)) { ?>
<h3 ALIGN=CENTER><?php echo $vocab['addroom'] ?></h3>
<form action=add.php method=post>
<input type=hidden name=type value=room>
<input type=hidden name=area value=<?php echo $area; ?>>

<TABLE>
<TR><TD><?php echo $vocab['name'] ?>:       </TD><TD><input type=text name=name></TD></TR>
<TR><TD><?php echo $vocab['description'] ?></TD><TD><input type=text name=description></TD></TR>
<TR><TD><?php echo $vocab['capacity'] ?>:   </TD><TD><input type=text name=capacity></TD></TR>
</TABLE>
<input type=submit value="<?php echo $vocab['addroom'] ?>">
</form>
<?php } else { echo "&nbsp;"; }?>
</td>
</tr>
</table>

<br>
<?php echo $vocab['browserlang'] . " " . $HTTP_ACCEPT_LANGUAGE . " " . $vocab['postbrowserlang'] ; ?>

<?php include "trailer.inc" ?>
