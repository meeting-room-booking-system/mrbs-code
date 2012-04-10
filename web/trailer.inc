<?php

// $Id$

function generate_trailer()
{
  global $year, $month, $day, $area, $room;
  global $strftime_format, $weekstarts, $simple_trailer, $view_week_number;

  $output = "";

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

  $output .= "</div>";  // Close of the contents div

  if ($simple_trailer)
  {
    $output .= "<div class=\"screenonly trailer\" id=\"simple_trailer\">\n";
    $output .= "<div id=\"simple\">\n";
    $output .= "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day$params\">";
    $output .= get_vocab("viewday")."</a>|";
    $output .= "<a href=\"week.php?year=$year&amp;month=$month&amp;day=$day$params\">";
    $output .= get_vocab("viewweek")."</a>|";
    $output .= "<a href=\"month.php?year=$year&amp;month=$month&amp;day=$day$params\">";
    $output .= get_vocab("viewmonth")."</a>\n";
    $output .= "</div>\n";
    $output .= "</div>\n";
  }
  else
  {
    $output .= "<div class=\"screenonly trailer\" id=\"trailer\">\n";
  
    // DAYS
    $output .= "<div id=\"viewday\">\n";
    $output .= "<div class=\"trailer_label\">\n";
    $output .= "<a href=\"day.php?year=$year&amp;month=$month&amp;day=$day$params\">" . get_vocab("viewday") .":</a>\n";
    $output .= "</div>\n";
  
    $output .= "<div class=\"trailer_links\">\n";
    for ($i = -6; $i <= 7; $i++)
    {
      $ctime = mktime(0, 0, 0, $month, $day + $i, $year);
  
      $str = utf8_strftime($strftime_format['daymonth'], $ctime);
      $class = "";
  
      $cyear  = date("Y", $ctime);
      $cmonth = date("m", $ctime);
      $cday   = date("d", $ctime);
    
      // put in a separator, unless we are at the beginning
      if ($i != -6)
      {
        $output .= " | ";
      }
    
      // if it's a hidden day set the class; otherwise construct the anchor
      // (don't offer a link if the day is a hidden day)
      if (is_hidden_day(date("w", $ctime)))
      { 
        $class .= "hidden";      
      }
      else
      {
        $str = "<a href=\"day.php?year=$cyear&amp;month=$cmonth&amp;day=$cday$params\">" . $str . "</a>";
      }
    
      // and if it's the current day, add square brackets and set the class
      if ($i == 0)
      {
        $str = "[ " . $str . " ]";
        if (empty($class))
        {
          $class = "current";
        }
        else
        {
          $class .= " current";
        }
      } 
      $output .= "<span" . ((empty($class)) ? '' : " class=\"$class\"") . ">$str</span>\n";
    }
    $output .= "</div>\n";
  
    $output .= "</div>\n";
  
  
    // WEEKS
    $output .= "<div id=\"viewweek\">\n";
    $output .= "<div class=\"trailer_label\">\n";
    $output .= "<a href=\"week.php?year=$year&amp;month=$month&amp;day=$day$params\">".get_vocab("viewweek").":</a>\n";
    $output .= "</div>\n";
 
    $ctime = mktime(0, 0, 0, $month, $day, $year);
    // How many days to skip back to first day of week:
    $skipback = (date("w", $ctime) - $weekstarts + 7) % 7;
  
    $output .= "<div class=\"trailer_links\">\n";
    for ($i = -4; $i <= 4; $i++)
    {
      $ctime = mktime(0, 0, 0, $month, $day + 7 * $i - $skipback, $year);
  
      $cweek  = date("W", $ctime);
      $cday   = date("d", $ctime);
      $cmonth = date("m", $ctime);
      $cyear  = date("Y", $ctime);
    
      // $htime gives you the d/m/y to use in the href link.    This is the
      // real day that we want to look at, whereas $ctime is the start of that week
      $htime = mktime(0, 0, 0, $month, $day + 7 * $i, $year);
      $hday   = date("d", $htime);
      $hmonth = date("m", $htime);
      $hyear  = date("Y", $htime);
    
      if ($i != -4)
      {
        $output .= " | ";
      }
  
      if ($view_week_number)
      {
        $str = $cweek;
      }
      else
      {
        $str = utf8_strftime($strftime_format['daymonth'], $ctime);
      }
      if ($i == 0)
      {
        $output .= "<span class=\"current\">\n[ ";
      }
      $output .= "  <a href=\"week.php?year=$hyear&amp;month=$hmonth&amp;day=$hday$params\">$str</a>\n";
      if ($i == 0)
      {
        $output .= "]</span>\n";
      }
    }
    $output .= "</div>\n";
  
    $output .= "</div>\n";
  
  
    // MONTHS
    $output .= "<div id=\"viewmonth\">\n";
    $output .= "<div class=\"trailer_label\">\n";
    $output .= "<a href=\"month.php?year=$year&amp;month=$month&amp;day=$day$params\">".get_vocab("viewmonth").":</a>\n";
    $output .= "</div>\n";
  
    $output .= "<div class=\"trailer_links\">\n";
    for ($i = -2; $i <= 6; $i++)
    {
      $ctime = mktime(0, 0, 0, $month + $i, 1, $year);
      $str = utf8_strftime($strftime_format['monyear'], $ctime);
     
      $cmonth = date("m", $ctime);
      $cyear  = date("Y", $ctime);
      $cday   = $day;
      // decrement day until it's a valid one for the month, in case you're moving to a month with fewer
      // days than the current one
      while (!checkdate($cmonth, $cday, $cyear) && ($cday > 1))
      {
        $cday--;
      }
      if ($i != -2)
      {
        $output .= " | ";
      }
      if ($i == 0)
      {
        $output .= "<span class=\"current\">\n[ ";
      }
      $output .= "  <a href=\"month.php?year=$cyear&amp;month=$cmonth&amp;day=$cday$params\">$str</a>\n";
      if ($i == 0)
      {
        $output .= "]</span>\n";
      }
    }
    $output .= "</div>\n";  // end of links div
    $output .= "</div>\n";  // end of viewmonth
    $output .= "</div>\n";  // end of "classic" trailer
  }
  return $output;
}


// Output the trailer for the bottom of the page, including the footer
function output_trailer()
{
  echo generate_trailer();

  // Print footer
  print_footer(FALSE);
}

?>
