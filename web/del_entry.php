<?php
# $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

if(getAuthorised(1) && ($info = mrbsGetEntryInfo($id)))
{
	$day   = strftime("%d", $info["start_time"]);
	$month = strftime("%m", $info["start_time"]);
	$year  = strftime("%Y", $info["start_time"]);
	$area  = mrbsGetRoomArea($info["room_id"]);

    if (MAIL_ADMIN_ON_DELETE)
    {
        include_once "functions_mail.inc";
        // Gather all fields values for use in emails.
        $mail_previous = getPreviousEntryData($id, $series);
    }
    sql_begin();
	$result = mrbsDelEntry(getUserName(), $id, $series, 1);
	sql_commit();
	if ($result)
	{
        // Send a mail to the Administrator
        (MAIL_ADMIN_ON_DELETE) ? $result = notifyAdminOnDelete($mail_previous) : '';
        Header("Location: day.php?day=$day&month=$month&year=$year&area=$area");
		exit();
	}
}

// If you got this far then we got an access denied.
showAccessDenied($day, $month, $year, $area);
?>