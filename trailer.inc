<P>
<HR CLEAR="all">
<B><?echo $lang[viewday]?></B>
<?php

if(!isset($year))
	$year = strftime("%Y");

if(!isset($month))
	$year = strftime("%m");

if(!isset($day))
	$year = strftime("%d");

for($i = -7; $i <= 7; $i++)
{
	$ctime = mktime(0, 0, 0, $month, $day + $i, $year);
	
	$str = strftime("%b %d", $ctime);
	
	$cyear  = date("Y", $ctime);
	$cmonth = date("m", $ctime);
	$cday   = date("d", $ctime);
	
	echo "<a href=day.php3?year=$cyear&month=$cmonth&day=$cday>$str</a>, \n";
}

?>
<HR>
</P>
