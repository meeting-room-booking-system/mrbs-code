<?php

include "config.inc";
include "functions.inc";
include "connect.inc";


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

// check to see if two events overlap
function times_overlap ( $time1, $duration1, $time2, $duration2 ) {
  //echo "times_overlap ( $time1, $duration1, $time2, $duration2 )<BR>";
  $list1 = split ( ":", $time1 );
  $hour1 = $list1[0];
  $min1 = $list1[1];
  $list2 = split ( ":", $time2 );
  $hour2 = $list2[0];
  $min2 = $list2[1];
  // convert to minutes since midnight
  $tmins1start = ($hour1 * 60 + $min1) * 60;
  $tmins1end = $tmins1start + ($duration1 * 60) - 1;
  $tmins2start = ($hour2 * 60 + $min2) * 60;
  $tmins2end = $tmins2start + ($duration2 * 60) - 1;
  //echo "tmins1start=$tmins1start, tmins1end=$tmins1end, tmins2start=$tmins2start, tmins2end=$tmins2end<BR>";
  if ( $tmins1start >= $tmins2start && $tmins1start <= $tmins2end )
    return true;
  if ( $tmins1end >= $tmins2start && $tmins1end <= $tmins2end )
    return true;
  if ( $tmins2start >= $tmins1start && $tmins2start <= $tmins1end )
    return true;
  if ( $tmins2end >= $tmins1start && $tmins2end <= $tmins1end )
    return true;
  return false;
}


# first check for any schedule conflicts
# we ask the db if there is anything which
#   starts before this and ends after the start
#   or starts between the times this starts and ends
#   where the room is the same

# Make the start time in mysql format
if (strlen($month) == 1) { $month = "0".$month;}
if (strlen($day)   == 1) { $day   = "0".$day;  }
$starttime = "$year-$month-$day $hour:$minute";
$duration_min = $duration * 60;

$sql = "select id, name from mrbs_entry where 
(
  (start_time between '$starttime' and date_sub(date_add('$starttime',interval $duration_min minute),interval 1 second))
  or
  ('$starttime' between start_time and date_sub(end_time, interval 1 second))
)
and room_id = $room_id
";
# if this is a replacement then dont conflict with itself
if ($id) {$sql = "$sql and id <> $id";}


$res = mysql_query($sql);
echo mysql_error();

# Make sure we remember which appointments overlap the one were trying to add
if (mysql_num_rows($res) > 0) {
	$error = "There are conflicts:";
	while ($row = mysql_fetch_row($res)) {
		$error = "$error<br><a href=view_entry.php3?id=$row[0]>$row[1]</a>";
	}
}


if (strlen($error) == 0) {
	# now add the entries
	if ($id) {
		# This is to replace an existing entry
		mysql_query("delete from mrbs_entry where id=$id");
	}
	#actually do some adding
	$name_q        = addslashes($name);
	$description_q = addslashes($description);
	$sql = "insert into mrbs_entry (room_id, create_by, start_time, end_time, type, name, description) values (
	        '$room_id',
			  '$REMOTE_ADDR',
			  '$starttime',
			  date_add('$starttime', interval $duration_min minute),
			  '$type',
			  '$name_q',
			  '$description_q'
			  )";
	
#	echo "$sql<p>";
	mysql_query($sql);
	echo mysql_error();
	# Now its all done go back to the day view
	if (strlen($returl) == 0) {
		$returl = "day.php3?year=$year&month=$month";
	}
	Header ( "Location: $returl" );
	exit;
}

?>
<HTML>
<HEAD><TITLE>WebCalendar</TITLE>
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
<H2><FONT COLOR="<?php echo $H2COLOR;?>">Error</H2></FONT>
<BLOCKQUOTE>
<?php echo $error; ?>
</BLOCKQUOTE>

<?php } 

echo "<a href=$returl>Return to Calendar View</a><p>";

include "trailer.inc"; ?>

</BODY>
</HTML>
