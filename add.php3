<?
include "config.inc";
include "functions.inc";
include "connect.inc";

# This file is for adding new areas/rooms

# we need to do different things depending on if its a room
# or an area

if ($type == "area") {
	$area_name_q = addslashes($name);
	$sql = "insert into mrbs_area (area_name) values ('$area_name_q')";
	mysql_query($sql);
	echo mysql_error();
}

if ($type == "room") {
	$room_name_q = addslashes($name);
	$description_q = addslashes($description);
	$sql = "insert into mrbs_room (room_name, area_id, description, capacity)
	        values
			  ('$room_name_q',$area, '$description_q',$capacity)";
	mysql_query($sql);
	echo mysql_error();
}
	
	
header("Location: admin.php3?area=$area");
