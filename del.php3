<?
include "config.inc";
include "functions.inc";
include "connect.inc";

function do_header() { ?>
<HTML>
<HEAD><TITLE>WebCalendar</TITLE>
<?include "style.inc"?>
</HEAD>
<BODY>
<? };


# This is gonna blast away something. We want them to be really
# really sure that this is what they want to do.

if ($type == "room") {
	# We are sposed to delete a room
	if ($confirm) {
		# They have confirmed it already, so go blast!
		# First take out all appointments for this room
		mysql_query("delete from mrbs_entry where room_id=$room");
		# Now take out the room itself
		mysql_query("delete from mrbs_room where id =$room");
		# Go back to the admin page
		Header("Location: admin.php3");
	} else {
		do_header();
		# We tell them how bad what theyre about to do is

		# Find out how many appointments would be deleted

		echo "This will delete the following bookings:<ul>";
		$sql = "select name, start_time, end_time from mrbs_entry where room_id=$room";
		$res = mysql_query($sql);
		echo mysql_error();
		while ($row=mysql_fetch_row($res)) {
			echo "<li>$row[0] ($row[1] -> $row[2])";
		}
		echo "</ul>";
		
		echo "<center>";
		echo "<H1>Are you sure?</h1>";
		echo "<H1><a href=del.php3?type=room&room=$room&confirm=Y>YES!</a> &nbsp;&nbsp;&nbsp; <a href=admin.php3>NO!</a></h1>";
		echo "</center>";
		include "trailer.inc";
	}
}

if ($type == "area") {
	# We are only going to let them delete an area if there are
	# no rooms. its easier
	$res = mysql_query("select count(*) from mrbs_room where area_id=$area");
	$row = mysql_fetch_row($res);
	if ($row[0] == 0) {
		#OK, nothing there, lets blast it away
		mysql_query("delete from mrbs_area where id=$area");
		#redirect back to the admin page
		header("Location: admin.php3");
	} else {
		# There are rooms left in the area
		do_header();
		echo "You must delete all rooms in this area before you can delete it<p>";
		echo "<a href=admin.php3>Go back to Admin page</a>";
		include "trailer.inc";
	}
}
