<?php
# $Id$

if ( $pview != 1 ) {

echo "<P><HR><B>$lang[viewday]:</B>\n";

if(!isset($year))
	$year = strftime("%Y");

if(!isset($month))
	$month = strftime("%m");

if(!isset($day))
	$day = strftime("%d");

if (empty($area))
	$params = "";
else
	$params = "&area=$area";

for($i = -6; $i <= 7; $i++)
{
	$ctime = mktime(0, 0, 0, $month, $day + $i, $year);

	$str = strftime(empty($dateformat)? "%b %d" : "%d %b", $ctime);

	$cyear  = date("Y", $ctime);
	$cmonth = date("m", $ctime);
	$cday   = date("d", $ctime);
	if ($i != -6) echo " | ";
	echo "<a href=\"day.php?year=$cyear&month=$cmonth&day=$cday$params\">$str</a>\n";
}

echo "<BR><B>$lang[viewweek]:</B>\n";

if (!empty($room)) $params .= "&room=$room";

$ctime = mktime(0, 0, 0, $month, $day, $year);
# How many days to skip back to first day of week:
$skipback = (date("w", $ctime) - $weekstarts + 7) % 7;
	
for ($i = -4; $i <= 4; $i++)
{
	$ctime = mktime(0, 0, 0, $month, $day + 7 * $i - $skipback, $year);
	$str = strftime(empty($dateformat)? "%b %d" : "%d %b", $ctime);
	
	$cday   = date("d", $ctime);
	$cmonth = date("m", $ctime);
	$cyear  = date("Y", $ctime);
	if ($i != -4) echo " | ";
	echo "<a href=\"week.php?year=$cyear&month=$cmonth&day=$cday$params\">$str</a>\n";
}

echo "<BR><B>$lang[viewmonth]:</B>\n";
for ($i = -2; $i <= 6; $i++)
{
	$ctime = mktime(0, 0, 0, $month + $i, 1, $year);
	$str = strftime("%b %Y", $ctime);
	
	$cmonth = date("m", $ctime);
	$cyear  = date("Y", $ctime);
	if ($i != -2) echo " | ";
	echo "<a href=\"month.php?year=$cyear&month=$cmonth$params\">$str</a>\n";
}

echo "<HR>";
echo '<p><center><a href="' . $REQUEST_URI . '&pview=1">Print Preview</a></center><p>';

}
?>

<HR>
</BODY>
</HTML>
