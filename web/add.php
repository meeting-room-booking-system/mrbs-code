<?php
include "config.inc";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

if(!getAuthorised(getUserName(), getUserPassword(), 2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

# This file is for adding new areas/rooms

# we need to do different things depending on if its a room
# or an area

if ($type == "area")
{
	$area_name_q = slashes($name);
	$sql = "insert into mrbs_area (area_name) values ('$area_name_q')";
	if (sql_command($sql) < 0) fatal_error(1, "<p>" . sql_error());
	$area = sql_insert_id("mrbs_area", "id");
}

if ($type == "room")
{
	$room_name_q = slashes($name);
	$description_q = slashes($description);
	if (empty($capacity)) $capacity = 0;
	$sql = "insert into mrbs_room (room_name, area_id, description, capacity)
	        values ('$room_name_q',$area, '$description_q',$capacity)";
	if (sql_command($sql) < 0) fatal_error(1, "<p>" . sql_error());
}

header("Location: admin.php?area=$area");
