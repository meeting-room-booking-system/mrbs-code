<?php
include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
        $day   = date("d");
        $month = date("m");
        $year  = date("Y");
}

if(!isset($area))
        $area = 0;

if(!getAuthorised(getUserName(), getUserPassword(), 1))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

if(!getWritable($create_by, getUserName()))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

// Units start in seconds
$units = 1.0;

switch($dur_units)
{
	case "years":
		$units *= 52;
	case "weeks":
		$units *= 7;
	case "days":
		$units *= 24;
	case "hours":
		$units *= 60;
	case "minutes":
		$units *= 60;
	case "seconds":
		break;
}

// Units are now in "$dur_units" numbers of seconds

if(isset($all_day) && ($all_day == "yes"))
{
	$starttime = mktime(0, 0, 0, $month, $day  , $year);
	$endtime   = mktime(0, 0, 0, $month, $day+1, $year);
}
else
{
	$starttime = mktime($hour, $minute, 0, $month, $day, $year);
	$endtime   = mktime($hour, $minute, 0, $month, $day, $year) + ($units * $duration);
	
	$round_up = 30 * 60;
	$diff     = $endtime - $starttime;
	
	if($tmp = $diff % $round_up)
		$endtime += $round_up - $tmp;
}

if(isset($rep_type) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
	// Get the repeat entry settings
	$rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
}
else
	$rep_type = 0;

if(!isset($rep_day))
	$rep_day = "";

switch($rep_type)
{
	case 2:
		$rep_opt  = $rep_day[0] ? "1" : "0";
		$rep_opt .= $rep_day[1] ? "1" : "0";
		$rep_opt .= $rep_day[2] ? "1" : "0";
		$rep_opt .= $rep_day[3] ? "1" : "0";
		$rep_opt .= $rep_day[4] ? "1" : "0";
		$rep_opt .= $rep_day[5] ? "1" : "0";
		$rep_opt .= $rep_day[6] ? "1" : "0";
		break;
	
	default:
		$rep_opt = "";
}

# first check for any schedule conflicts
# we ask the db if there is anything which
#   starts before this and ends after the start
#   or starts between the times this starts and ends
#   where the room is the same

$reps = mrbsGetRepeatEntryList($starttime, isset($rep_enddate) ? $rep_enddate : 0, $rep_type, $rep_opt, $max_rep_entrys);
if(!empty($reps))
{
	if(count($reps) < $max_rep_entrys)
	{
		$diff = $endtime - $starttime;
		
		for($i = 0; $i < count($reps); $i++)
		{
			$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, isset($id) ? $id : -1);
			
			if(!empty($tmp))
				$err = $err . $tmp;
		}
	}
	else
	{
		$err        = $lang[too_may_entrys] . "<P>";
		$hide_title = 1;
	}
}
else
	$err = mrbsCheckFree($room_id, $starttime, $endtime-1, isset($id) ? $id : -1);

if(empty($err))
{
	if($edit_type == "series")
	{
		mrbsCreateRepeatingEntrys($starttime, $endtime,   $rep_type, $rep_enddate, $rep_opt, 
		                          $room_id,   $create_by, $name,     $type,        $description);
	}
	else
	{
		$res = mysql_query("SELECT repeat_id FROM mrbs_entry WHERE id='$id'");
		$repeat_id = $entry_type = 0; // preset repeat_id and entry_type to 0
		if(mysql_num_rows($res) > 0)
		{
			$row = mysql_fetch_array($res);
			if (($repeat_id = $row["repeat_id"]) > 0)
				$entry_type = 2;  // This is a changed repeat booking entry
		}
//		else
//			$repeat_id = $entry_type = 0;
		
		// Create the entrys, ignoring any errors at the moment
		if(mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
		                         $create_by, $name, $type, $description))
		{
			
		}
	}
	
	# Delete the original entry
	if(isset($id))
		mrbsDelEntry(getUserName(), $id, ($edit_type == "series"), 0);
	
	$area = mrbsGetRoomArea($room_id);
	
	# Now its all done go back to the day view
	Header("Location: day.php3?year=$year&month=$month&day=$day&area=$area");
	exit;
}

if(strlen($err))
{
	print_header($day, $month, $year, $area);
	
	echo "<H2>" . $lang["sched_conflict"] . "</H2>";
	if(!isset($hide_title))
	{
		echo $lang["conflict"];
		echo "<UL>";
	}
	
	echo $err;
	
	if(!isset($hide_title))
		echo "</UL>";
}

echo "<a href=$returl>$lang[returncal]</a><p>";

include "trailer.inc"; ?>

</BODY>
</HTML>
