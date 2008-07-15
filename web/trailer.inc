<?php
// $Id$
?>

<div class="screenonly">
  <hr>
  <b><?php echo get_vocab("viewday") ?></b>
<?php

if(!isset($year))
{
  $year = strftime("%Y");
}

if (!isset($month))
{
  $month = strftime("%m");
}

if (!isset($day))
{
  $day = strftime("%d");
}

if (empty($area))
{
  $params = "";
}
else
{
  $params = "&amp;area=$area";
}

for ($i = -6; $i <= 7; $i++)
{
  $ctime = mktime(0, 0, 0, $month, $day + $i, $year);

  $str = utf8_strftime(empty($dateformat)? "%b %d" : "%d %b", $ctime);

  $cyear  = date("Y", $ctime);
  $cmonth = date("m", $ctime);
  $cday   = date("d", $ctime);
  if ($i != -6)
  {
    echo " | ";
  }
  if ($i == 0)
  {
    echo '<b>[ ';
  }
  echo "  <a href=\"day.php?year=$cyear&amp;month=$cmonth&amp;day=$cday$params\">$str</a>\n";
  if ($i == 0)
  {
    echo ']</b> ';
  }
}

echo "  <br><b>".get_vocab("viewweek").":</b>\n";

if (!empty($room))
{
  $params .= "&amp;room=$room";
}

$ctime = mktime(0, 0, 0, $month, $day, $year);
// How many days to skip back to first day of week:
$skipback = (date("w", $ctime) - $weekstarts + 7) % 7;
   
for ($i = -4; $i <= 4; $i++)
{
  $ctime = mktime(0, 0, 0, $month, $day + 7 * $i - $skipback, $year);

  $cweek  = date("W", $ctime);
  $cday   = date("d", $ctime);
  $cmonth = date("m", $ctime);
  $cyear  = date("Y", $ctime);
  if ($i != -4)
  {
    echo " | ";
  }

  if ($view_week_number)
  {
    $str = $cweek;
  }
  else
  {
    $str = utf8_strftime(empty($dateformat)? "%b %d" : "%d %b", $ctime);
  }
  if ($i == 0)
  {
    echo '<b>[ ';
  }
  echo "  <a href=\"week.php?year=$cyear&amp;month=$cmonth&amp;day=$cday$params\">$str</a>\n";
  if ($i == 0)
  {
    echo ']</b> ';
  }
}

echo "  <br><b>".get_vocab("viewmonth").":</b>\n";
for ($i = -2; $i <= 6; $i++)
{
  $ctime = mktime(0, 0, 0, $month + $i, 1, $year);
  $str = utf8_strftime("%b %Y", $ctime);
   
  $cmonth = date("m", $ctime);
  $cyear  = date("Y", $ctime);
  if ($i != -2)
  {
    echo " | ";
  }
  if ($i == 0)
  {
    echo '<b>[ ';
  }
  echo "  <a href=\"month.php?year=$cyear&amp;month=$cmonth$params\">$str</a>\n";
  if ($i == 0)
  {
    echo ']</b> ';
  }
}

?>
  <hr>

</div><!-- end of "screenonly" div -->
</body>
</html>
