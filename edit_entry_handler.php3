<?php

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";
include "mrbs_sql.inc";

function add_duration ( $time, $duration ) {
  $list = split ( ":", $time );
  $hour = $list[0];
  $min = $list[1];
  $minutes = $hour * 60 + $min + $duration;
  $h = $minutes / 60;
  $m = $minutes % 60;
  $ret = sprintf ( "%d:%02d", $h, $m );
  //echo "add_duration ( $time, $duration ) = $ret <BR>";
  return $ret;
}

if(!getAuthorised(getUserName(), getUserPassword()))
{
?>
<HTML>
 <HEAD>
  <META HTTP-EQUIV="REFRESH" CONTENT="5; URL=index.php3">
  <TITLE><?echo $lang[mrbs]?></TITLE>
  <?include "config.inc"?>
  <?include "style.inc"?>
 <BODY>
  <H1><?echo $lang[accessdenied]?></H1>
  <P>
   <?echo $lang[unandpw]?>
  </P>
  <P>
   <a href=<? echo $HTTP_REFERER; ?>><? echo $lang[returnprev]; ?></a>
  </P>
</HTML>
<?
	exit;
}

if(!getWritable($create_by, getUserName())) { ?>
<HTML>
<HEAD>
<TITLE><?echo $lang[mrbs]?></TITLE>
<?include "style.inc"?>

<H1><?echo $lang[accessdenied]?></H1>
<P>
  <?echo $lang[norights]?>
</P>
<P>
  <a href=<?echo $HTTP_REFERER?>><?echo $lang[returnprev]?></a>
</P>
</BODY>
</HTML>
<? exit; }

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

$starttime = mktime($hour, $minute, 0, $month, $day, $year);
$endtime   = mktime($hour, $minute, 0, $month, $day, $year) + ($units * $duration);

if($all_day == "yes")
	$round_up = 60 * 60 * 24;
else
	$round_up = 30 * 60;

$diff = $endtime - $starttime;

if($tmp = $diff % $round_up)
	$endtime += $round_up - $tmp;

if($all_day == "yes")
{
	$diff = $endtime - $starttime;
	
	if($tmp = $starttime % (60 * 60 * 24))
	{
		$starttime -= $tmp;
		$endtime    = $starttime + $diff;
	}	
}

// Get the repeat entry settings
$rep_enddate = mktime(0, 0, 0, $rep_end_month, $rep_end_day, $rep_end_year);

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

$reps = mrbsGetRepeatEntryList($starttime, $rep_enddate, $rep_type, $rep_opt, $max_rep_entrys);
if(!empty($reps))
{
	if(count($reps) <= $max_rep_entrys)
	{
		$diff = $endtime - $starttime;
		
		for($i = 0; $i < count($reps); $i++)
		{
			$tmp = mrbsCheckFree($room_id, $reps[$i], $reps[$i] + $diff, $id);
			
			if(!empty($tmp))
				$err = $err . $tmp;
		}
	}
	else
		$err = $lang[too_may_entrys];
}
else
	$err = mrbsCheckFree($room_id, $starttime, $endtime-1, $id);

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
		if(mysql_num_rows($res) > 0)
		{
			$row = mysql_fetch_row($res);
			$repeat_id  = $row[0];
			$entry_type = 2;
		}
		else
			$repeat_id = $entry_type = 0;
		
		// Create the entrys, ignoring any errors at the moment
		if(mrbsCreateSingleEntry($starttime, $endtime, $entry_type, $repeat_id, $room_id,
		                         $create_by, $name, $type, $description))
		{
			
		}
	}
	
	# Delete the original entry
	if($id)
		mrbsDelEntry(getUserName(), $id, ($edit_type == "series"));
	
	# Now its all done go back to the day view
	Header("Location: day.php3?year=$year&month=$month&day=$day");
	exit;
}

?>
<HTML>
<HEAD><TITLE><?echo $lang[mrbs]?></TITLE>
<?include "style.inc"?>
</HEAD>
<BODY>

<?php if ( strlen ( $overlap ) ) { ?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>">Scheduling Conflict</H2></FONT>

Your suggested time of <B>
<?php
  $time = sprintf ( "%d:%02d", $hour, $minute );
  echo display_time ( $time );
  if ( $duration > 0 )
    echo "-" . display_time ( add_duration ( $time, $duration ) );
?>
</B> conflicts with the following existing calendar entries:
<UL>
<?php echo $overlap; ?>
</UL>

<?php } else { ?>
<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?echo $lang[error]?></H2></FONT>
<BLOCKQUOTE>
<?php echo $err; ?>
</BLOCKQUOTE>

<?php }

echo "<a href=$returl>$lang[returncal]</a><p>";

include "trailer.inc"; ?>

</BODY>
</HTML>
