<?php
// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
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

// Done changing area or room information?
if (isset($change_done))
{
	if (!empty($room)) // Get the area the room is in
	{
		$area = sql_query1("SELECT area_id from mrbs_room where id=$room");
	}
	Header("Location: admin.php?day=$day&month=$month&year=$year&area=$area");
	exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

?>

<h2><?php echo $vocab["editroomarea"] ?></h2>

<table border=1>

<?php
if(!empty($room)) {
	if (isset($change_room))
	{
		if (empty($capacity)) $capacity = 0;
		$sql = "UPDATE mrbs_room SET room_name='" . slashes($room_name)
			. "', description='" . slashes($description)
			. "', capacity=$capacity WHERE id=$room";
		if (sql_command($sql) < 0)
			fatal_error(0, $vocab['update_room_failed'] . sql_error());
	}

	$res = sql_query("SELECT * FROM mrbs_room WHERE id=$room");
	if (! $res) fatal_error(0, $vocab['error_room'] . $room . $vocab['not_found']);
	$row = sql_row_keyed($res, 0);
	sql_free($res);
?>
<h3 ALIGN=CENTER><?php echo $vocab["editroom"] ?></h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="room" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD><?php echo $vocab["name"] ?>:       </TD><TD><input type=text name="room_name" value="<?php
echo htmlspecialchars($row["room_name"]); ?>"></TD></TR>
<TR><TD><?php echo $vocab["description"] ?></TD><TD><input type=text name=description value="<?php
echo htmlspecialchars($row["description"]); ?>"></TD></TR>
<TR><TD><?php echo $vocab["capacity"] ?>:   </TD><TD><input type=text name=capacity value="<?php
echo $row["capacity"]; ?>"></TD></TR>
</TABLE>
<input type=submit name="change_room"
value="<?php echo $vocab["change"] ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="<?php echo $vocab["backadmin"] ?>">
</CENTER>
</form>
<?php } ?>

<?php
if(!empty($area))
{
	if (isset($change_area))
	{
		$sql = "UPDATE mrbs_area SET area_name='" . slashes($area_name)
			. "' WHERE id=$area";
		if (sql_command($sql) < 0)
			fatal_error(0, $vocab['update_area_failed'] . sql_error());
	}

	$res = sql_query("SELECT * FROM mrbs_area WHERE id=$area");
	if (! $res) fatal_error(0, $vocab['error_area'] . $area . $vocab['not_found']);
	$row = sql_row_keyed($res, 0);
	sql_free($res);
?>
<h3 ALIGN=CENTER><?php echo $vocab["editarea"] ?></h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="area" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD><?php echo $vocab["name"] ?>:       </TD><TD><input type=text name="area_name" value="<?php
echo htmlspecialchars($row["area_name"]); ?>"></TD></TR>
</TABLE>
<input type=submit name="change_area"
value="<?php echo $vocab["change"] ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="<?php echo $vocab["backadmin"] ?>">
</CENTER>
</form>
<?php } ?>
</TABLE>
<?php include "trailer.inc" ?>