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
		$area = sql_query1("SELECT area_id from $tbl_room where id=$room");
	}
	Header("Location: admin.php?day=$day&month=$month&year=$year&area=$area");
	exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

?>

<h2><?php echo get_vocab("editroomarea") ?></h2>

<table border=1>

<?php
if(!empty($room)) {
	if (isset($change_room))
	{
		if (empty($capacity)) $capacity = 0;
		$sql = "UPDATE $tbl_room SET room_name='" . slashes($room_name)
			. "', description='" . slashes($description)
			. "', capacity=$capacity WHERE id=$room";
		if (sql_command($sql) < 0)
			fatal_error(0, get_vocab("update_room_failed") . sql_error());
	}

	$res = sql_query("SELECT * FROM $tbl_room WHERE id=$room");
	if (! $res) fatal_error(0, get_vocab("error_room") . $room . get_vocab("not_found"));
	$row = sql_row_keyed($res, 0);
	sql_free($res);
?>
<h3 ALIGN=CENTER><?php echo get_vocab("editroom") ?></h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="room" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD><?php echo get_vocab("name") ?>:       </TD><TD><input type=text name="room_name" value="<?php
echo htmlspecialchars($row["room_name"]); ?>"></TD></TR>
<TR><TD><?php echo get_vocab("description") ?></TD><TD><input type=text name=description value="<?php
echo htmlspecialchars($row["description"]); ?>"></TD></TR>
<TR><TD><?php echo get_vocab("capacity") ?>:   </TD><TD><input type=text name=capacity value="<?php
echo $row["capacity"]; ?>"></TD></TR>
</TABLE>
<input type=submit name="change_room"
value="<?php echo get_vocab("change") ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="<?php echo get_vocab("backadmin") ?>">
</CENTER>
</form>
<?php } ?>

<?php
if(!empty($area))
{
	if (isset($change_area))
	{
		$sql = "UPDATE $tbl_area SET area_name='" . slashes($area_name)
			. "' WHERE id=$area";
		if (sql_command($sql) < 0)
			fatal_error(0, get_vocab("update_area_failed") . sql_error());
	}

	$res = sql_query("SELECT * FROM $tbl_area WHERE id=$area");
	if (! $res) fatal_error(0, get_vocab("error_area") . $area . get_vocab("not_found"));
	$row = sql_row_keyed($res, 0);
	sql_free($res);
?>
<h3 ALIGN=CENTER><?php echo get_vocab("editarea") ?></h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="area" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD><?php echo get_vocab("name") ?>:       </TD><TD><input type=text name="area_name" value="<?php
echo htmlspecialchars($row["area_name"]); ?>"></TD></TR>
</TABLE>
<input type=submit name="change_area"
value="<?php echo get_vocab("change") ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="<?php echo get_vocab("backadmin") ?>">
</CENTER>
</form>
<?php } ?>
</TABLE>
<?php include "trailer.inc" ?>
