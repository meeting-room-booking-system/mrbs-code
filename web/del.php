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
if (empty($area))
	$area = get_default_area();

if(!getAuthorised(getUserName(), getUserPassword(), 2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

# This is gonna blast away something. We want them to be really
# really sure that this is what they want to do.

if($type == "room")
{
	# We are supposed to delete a room
	if(isset($confirm))
	{
		# They have confirmed it already, so go blast!
		sql_begin();
		# First take out all appointments for this room
		sql_command("delete from mrbs_entry where room_id=$room");
		
		# Now take out the room itself
		sql_command("delete from mrbs_room where id=$room");
		sql_commit();
		
		# Go back to the admin page
		Header("Location: admin.php");
	}
	else
	{
		print_header($day, $month, $year, $area);
		
		# We tell them how bad what theyre about to do is
		# Find out how many appointments would be deleted
		
		$sql = "select name, start_time, end_time from mrbs_entry where room_id=$room";
		$res = sql_query($sql);
		if (! $res) echo sql_error();
		elseif (sql_count($res) > 0)
		{
			echo $vocab["deletefollowing"] . ":<ul>";
			
			for ($i = 0; ($row = sql_row($res, $i)); $i++)
			{
				echo "<li>$row[0] (";
				echo strftime("%I:%H %a %d %b %Y",  $row[1]) . " -> ";
				echo strftime("%I:%H %A %d %B %Y",  $row[2]) . ")";
			}
			
			echo "</ul>";
		}
		
		echo "<center>";
		echo "<H1>" .  $vocab["sure"] . "</h1>";
		echo "<H1><a href=\"del.php?type=room&room=$room&confirm=Y\">" . $vocab["YES"] . "!</a> &nbsp;&nbsp;&nbsp; <a href=admin.php>" . $vocab["NO"] . "!</a></h1>";
		echo "</center>";
		include "trailer.inc";
	}
}

if($type == "area")
{
	# We are only going to let them delete an area if there are
	# no rooms. its easier
    $n = sql_query1("select count(*) from mrbs_room where area_id=$area");
	if ($n == 0)
	{
		# OK, nothing there, lets blast it away
		sql_command("delete from mrbs_area where id=$area");
		
		# Redirect back to the admin page
		header("Location: admin.php");
	}
	else
	{
		# There are rooms left in the area
		print_header($day, $month, $year, $area);
		
		echo "You must delete all rooms in this area before you can delete it<p>";
		echo "<a href=admin.php>Go back to Admin page</a>";
		include "trailer.inc";
	}
}
