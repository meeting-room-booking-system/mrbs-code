<?

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

if(getAuthorised(getUserName(), getUserPassword(), 1) && ($info = mrbsGetEntryInfo($id)))
{
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	
	$area  = mrbsGetRoomArea($info["room_id"]);
	
	if(mrbsDelEntry(getUserName(), $id, $series, 1))
	{
		Header("Location: day.php3?day=$day&month=$month&year=$year&area=$area");
		exit();
	}
}

// If you got this far then we got an access denied.
showAccessDenied($day, $month, $year, $area);
?>
