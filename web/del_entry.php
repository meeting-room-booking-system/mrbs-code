<?php
# $Id$

include "config.inc";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

if(getAuthorised(getUserName(), getUserPassword(), 1) && ($info = mrbsGetEntryInfo($id)))
{
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);
	
	sql_begin();
	$result = mrbsDelEntry(getUserName(), $id, $series, 1);
	sql_commit();
	if ($result)
	{
		Header("Location: day.php?day=$day&month=$month&year=$year&area=$area");
		exit();
	}
}

// If you got this far then we got an access denied.
showAccessDenied($day, $month, $year, $area);
?>
