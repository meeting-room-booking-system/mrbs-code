<?php
// $Id$

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

<h2>Edit Area or Room Description</h2>

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
			fatal_error(0, "Update room failed: " . sql_error());
	}

	$res = sql_query("SELECT * FROM mrbs_room WHERE id=$room");
	if (! $res) fatal_error(0, "Error: room $room not found");
	$row = sql_row_keyed($res, 0);
	sql_free($res);
?>
<h3 ALIGN=CENTER>Edit Room</h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="room" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD>Name:       </TD><TD><input type=text name="room_name" value="<?php
echo htmlspecialchars($row["room_name"]); ?>"></TD></TR>
<TR><TD>Description:</TD><TD><input type=text name=description value="<?php
echo htmlspecialchars($row["description"]); ?>"></TD></TR>
<TR><TD>Capacity:   </TD><TD><input type=text name=capacity value="<?php
echo $row["capacity"]; ?>"></TD></TR>
</TABLE>
<input type=submit name="change_room"
value="Change">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="Back to Admin">
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
			fatal_error(0, "Update area failed: " . sql_error());
	}

	$res = sql_query("SELECT * FROM mrbs_area WHERE id=$area");
	if (! $res) fatal_error(0, "Error: area $area not found");
	$row = sql_row_keyed($res, 0);
	sql_free($res);
?>
<h3 ALIGN=CENTER>Edit Area</h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="area" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD>Name:       </TD><TD><input type=text name="area_name" value="<?php
echo htmlspecialchars($row["area_name"]); ?>"></TD></TR>
</TABLE>
<input type=submit name="change_area"
value="Change">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="Back to Admin">
</CENTER>
</form>
<?php } ?>
</TABLE>
<?php include "trailer.inc" ?>
