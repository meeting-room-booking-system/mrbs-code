<?php
// $Id$


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
  if (!empty($room))
{
  $params .= "&amp;room=$room";
}


if ($simple_trailer) {
  echo "<div class=\"screenonly\" id=\"simple_trailer\">\n";
  echo "<div id=\"simple\">\n";
  echo "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day$params\">";
  echo get_vocab("viewday")."</a>|";
  echo "<a href=\"week.php?year=$year&amp;month=$month&amp;day=$day$params\">";
  echo get_vocab("viewweek")."</a>|";
  echo "<a href=\"month.php?year=$year&amp;month=$month&amp;day=$day$params\">";
  echo get_vocab("viewmonth")."</a>\n";
  echo "</div>\n";
  echo "</div>\n";
}

else
{
  echo "<div class=\"screenonly\" id=\"trailer\">\n";
  
  // DAYS
  echo "<div id=\"viewday\">\n";
  echo "<div class=\"trailer_label\">\n";
  echo "<span>" . get_vocab("viewday") .":</span>\n";
  echo "</div>\n";
  
  echo "<div class=\"trailer_links\">\n";
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
      echo "<span class=\"current\">\n[ ";
    }
    echo "  <a href=\"day.php?year=$cyear&amp;month=$cmonth&amp;day=$cday$params\">$str</a>\n";
    if ($i == 0)
    {
      echo "]</span>\n";
    }
  }
  echo "</div>\n";
  
  echo "</div>\n";
  
  
  // WEEKS
  echo "<div id=\"viewweek\">\n";
  echo "<div class=\"trailer_label\">\n";
  echo "  <span>".get_vocab("viewweek").":</span>\n";
  echo "</div>\n";
 
  $ctime = mktime(0, 0, 0, $month, $day, $year);
  // How many days to skip back to first day of week:
  $skipback = (date("w", $ctime) - $weekstarts + 7) % 7;
  
  echo "<div class=\"trailer_links\">\n";
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
      echo "<span class=\"current\">\n[ ";
    }
    echo "  <a href=\"week.php?year=$cyear&amp;month=$cmonth&amp;day=$cday$params\">$str</a>\n";
    if ($i == 0)
    {
      echo "]</span>\n";
    }
  }
  echo "</div>\n";
  
  echo "</div>\n";
  
  
  // MONTHS
  echo "<div id=\"viewmonth\">\n";
  echo "<div class=\"trailer_label\">\n";
  echo "  <span>".get_vocab("viewmonth").":</span>\n";
  echo "</div>\n";
  
  echo "<div class=\"trailer_links\">\n";
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
      echo "<span class=\"current\">\n[ ";
    }
    echo "  <a href=\"month.php?year=$cyear&amp;month=$cmonth$params\">$str</a>\n";
    if ($i == 0)
    {
      echo "]</span>\n";
    }
  }
  echo "</div>\n";  // end of links div
  echo "</div>\n";  // end of viewmonth
  echo "</div>\n";  // end of "classic" trailer
}
?>

</body>
</html>
