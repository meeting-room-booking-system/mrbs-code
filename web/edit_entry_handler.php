<?php
// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
require_once("database.inc.php");
MDB::loadFile("Date"); 
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

if( $enable_periods ) {
	$resolution = 60;
	$hour = 12;
	$minute = $period;
        $max_periods = count($periods);
        if( $dur_units == "periods" && ($minute + $duration) > $max_periods )
        {
            $duration = (24*60*floor($duration/$max_periods)) + ($duration%$max_periods);
        }
        if( $dur_units == "days" && $minute == 0 )
        {
		$dur_units = "periods";
                $duration = $max_periods + ($duration-1)*60*24;
        }
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
    case "periods":
    case "minutes":
        $units *= 60;
    case "seconds":
        break;
}

// Units are now in "$dur_units" numbers of seconds


if(isset($all_day) && ($all_day == "yes"))
{
    if( $enable_periods )
    {
        $starttime = mktime(12, 0, 0, $month, $day, $year);
        $endtime   = mktime(12, $max_periods, 0, $month, $day, $year);
    }
    else
    {
        $starttime = mktime(0, 0, 0, $month, $day  , $year, is_dst($month, $day  , $year));
        $endtime   = mktime(0, 0, 0, $month, $day+1, $year, is_dst($month, $day+1, $year));
    }
}
else
{
    if (!$twentyfourhour_format)
    {
      if (isset($ampm) && ($ampm == "pm") && ($hour<12))
      {
        $hour += 12;
      }
      if (isset($ampm) && ($ampm == "am") && ($hour>11))
      {
        $hour -= 12;
      }
    }

    $starttime = mktime($hour, $minute, 0, $month, $day, $year, is_dst($month, $day, $year, $hour));
    $endtime   = mktime($hour, $minute, 0, $month, $day, $year, is_dst($month, $day, $year, $hour)) + ($units * $duration);

    # Round up the duration to the next whole resolution unit.
    # If they asked for 0 minutes, push that up to 1 resolution unit.
    $diff = $endtime - $starttime;
    if (($tmp = $diff % $resolution) != 0 || $diff == 0)
        $endtime += $resolution - $tmp;

    $endtime += cross_dst( $starttime, $endtime );
}

if(isset($rep_type) && isset($rep_end_month) && isset($rep_end_day) && isset($rep_end_year))
{
    // Get the repeat entry settings
    $rep_enddate = mktime($hour, $minute, 0, $rep_end_month, $rep_end_day, $rep_end_year);
}
else
    $rep_type = 0;

if(!isset($rep_day))
    $rep_day = array();

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
    $res = $mdb->query("SELECT  repeat_id 
                        FROM    mrbs_entry 
                        WHERE   id=$id", 'integer');
    if ( (MDB::isError($res)) or (1 <> $mdb->numRows($res)) )
    {
        $repeat_id = 0;
        $mdb->freeResult($res);
    }
    else
    {
        $repeat_id = $mdb->fetchOne($res);
    }
}
else
    $ignore_id = 0;

# Acquire mutex to lock out others trying to book the same slot(s).
if (!sql_mutex_lock('mrbs_entry'))
    fatal_error(1, get_vocab("failed_to_acquire"));
    
# Check for any schedule conflicts in each room we're going to try and
# book in
$err = "";
foreach ( $rooms as $room_id ) {
  if ($rep_type != 0 && !empty($reps))
  {
    if(count($reps) < $max_rep_entrys)
    {
        
        for($i = 0; $i < count($reps); $i++)
        {
	    # calculate diff each time and correct where events
	    # cross DST
            $diff = $endtime - $starttime;
            $diff += cross_dst($reps[$i], $reps[$i] + $diff);
	    
	    $tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $ignore_id, $repeat_id);

            if(!empty($tmp))
                $err = $err . $tmp;
        }
    }
    else
    {
        $err        .= get_vocab("too_may_entrys") . "<P>";
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
                                      $room_id,   $create_by, $name,     $type,        $description,
                                      isset($rep_num_weeks) ? $rep_num_weeks : 0);
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
    
    echo "<H2>" . get_vocab("sched_conflict") . "</H2>";
    if(!isset($hide_title))
    {
        echo get_vocab("conflict");
        echo "<UL>";
    }
    
    echo $err;
    
    if(!isset($hide_title))
        echo "</UL>";
}

echo "<a href=\"$returl\">".get_vocab("returncal")."</a><p>";

include "trailer.inc"; ?>