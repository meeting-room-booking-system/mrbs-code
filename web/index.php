<?php

# $Id$

# Index is just a stub to redirect to the appropriate view
# as defined in config.inc.php using the variable $default_view
# If $default_room is defined in config.inc.php then this will
# be used to redirect to a particular room.

require_once "grab_globals.inc.php";
include("config.inc.php");
include("$dbsys.inc");

$day   = date("d");
$month = date("m");
$year  = date("Y");

switch ($default_view)
{
	case "month":
		$redirect_str = "month.php?year=$year&month=$month";
		break;
	case "week":
		$redirect_str = "week.php?year=$year&month=$month&day=$day";
		break;
	default:
		$redirect_str = "day.php?day=$day&month=$month&year=$year";
}

if( ! empty($default_room) )
{

	$sql = "select area_id from $tbl_room where id=$default_room";
	$res = sql_query($sql);
	if( $res )
	{
		if( sql_count($res) == 1 )
		{
			$row = sql_row($res, 0);
			$area = $row[0];
			$room = $default_room;
			$redirect_str .= "&area=$area&room=$room";
		}
	}
}

header("Location: $redirect_str");

?>
