<?php
// $Id$

include "config.inc";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if(empty($area))
	$area = get_default_area();

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
	
	# Round up the duration to the next whole resolution unit.
	# If they asked for 0 minutes, push that up to 1 resolution unit.
	$diff = $endtime - $starttime;
	if (($tmp = $diff % $resolution) != 0 || $diff == 0)
		$endtime += $resolution - $tmp;
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

# For weekly repeat(2), build string of weekdays to repeat on:
$rep_opt = "";
if (($rep_type == 2) || ($rep_type == 6))
	for ($i = 0; $i < 7; $i++) $rep_opt .= empty($rep_day[$i]) ? "0" : "1";


# Expand a series into a list of start times:
if ($rep_type != 0)
	$reps = mrbsGetRepeatEntryList($starttime, isset($rep_enddate) ? $rep_enddate : 0,
		$rep_type, $rep_opt, $max_rep_entrys, $rep_num_weeks);

# When checking for overlaps, for Edit (not New), ignore this entry and series:
$repeat_id = 0;
if (isset($id))
{
	$ignore_id = $id;
	$repeat_id = sql_query1("SELECT repeat_id FROM mrbs_entry WHERE id=$id");
	if ($repeat_id < 0)
		$repeat_id = 0;
}
else
	$ignore_id = 0;

# Acquire mutex to lock out others trying to book the same slot(s).
if (!sql_mutex_lock('mrbs_entry'))
	fatal_error(1, "Failed to acquire exclusive database access");

# Check for any schedule conflicts in each room we're going to try and
# book in
$err = "";
foreach ( $rooms as $room_id ) {
  if ($rep_type != 0 && !empty($reps))
  {
	if(count($reps) < $max_rep_entrys)
	{
		$diff = $endtime - $starttime;
		
		for($i = 0; $i < count($reps); $i++)
		{
			$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);

			if(!empty($tmp))
				$err = $err . $tmp;
		}
	}
	else
	{
		$err        .= $vocab["too_may_entrys"] . "<P>";
		$hide_title  = 1;
	}
  }
  else
	$err .= mrbsCheckFree($room_id, $starttime, $endtime-1, $ignore_id, 0);

} # end foreach rooms

if(empty($err))
{
	foreach ( $rooms as $room_id ) {
		if($edit_type == "series")
		{
			mrbsCreateRepeatingEntrys($starttime, $endtime,   $rep_type, $rep_enddate, $rep_opt, 
			                          $room_id,   $create_by, $name,     $type,        $description, $rep_num_weeks);
		}
		else
		{
			# Mark changed entry in a series with entry_type 2:
			if ($repeat_id > 0)
				$entry_type = 2;
			else
				$entry_type = 0;
			
			# Create the entry:
			mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
			                         $create_by, $name, $type, $description);
		}
	} # end foreach $rooms

	# Delete the original entry
	if(isset($id))
		mrbsDelEntry(getUserName(), $id, ($edit_type == "series"), 1);

	sql_mutex_unlock('mrbs_entry');
	
	$area = mrbsGetRoomArea($room_id);
	
	# Now its all done go back to the day view
	Header("Location: day.php?year=$year&month=$month&day=$day&area=$area");
	exit;
}

# The room was not free.
sql_mutex_unlock('mrbs_entry');

if(strlen($err))
{
	print_header($day, $month, $year, $area);
	
	echo "<H2>" . $vocab["sched_conflict"] . "</H2>";
	if(!isset($hide_title))
	{
		echo $vocab["conflict"];
		echo "<UL>";
	}
	
	echo $err;
	
	if(!isset($hide_title))
		echo "</UL>";
}

echo "<a href=\"$returl\">$vocab[returncal]</a><p>";

include "trailer.inc"; ?>
