<?php
namespace MRBS;

function minicals($year, $month, $day, $area, $room, $dmy) {

// PHP Calendar Class
//  
// Copyright David Wilkinson 2000. All Rights reserved.
// 
// This software may be used, modified and distributed freely
// providing this copyright notice remains intact at the head 
// of the file.
//
// This software is freeware. The author accepts no liability for
// any loss or damages whatsoever incurred directly or indirectly 
// from the use of this script.
//
// URL:   http://www.cascade.org.uk/software/php/calendar/
// Email: davidw@cascade.org.uk

class Calendar
{
  private $month;
  private $year;
  private $day;
  private $h;
  private $area;
  private $room;
  private $dmy;
    
  public function __construct($day, $month, $year, $h, $area, $room, $dmy)
  {
      $this->day   = $day;
      $this->month = $month;
      $this->year  = $year;
      $this->h     = $h;
      $this->area  = $area;
      $this->room  = $room;
      $this->dmy   = $dmy;
  }
   
  public function getHTML()
  {
    global $weekstarts;
    global $PHP_SELF;
    global $day, $month, $year;
    global $mincals_week_numbers;
    global $strftime_format;

    if (!isset($weekstarts)) $weekstarts = 0;
    $html = '';

    $page = this_page();
      
    // For the week view we will need to know the start of the week that should be highlighted
    if (preg_match("/week/i", $page))
    {
        
      // First of all work out how many days we have to skip back to the
      // start of the week
      $sticky_dow = date("w", mktime(12, 0, 0, $month, $day, $year));
      $skipback = ($sticky_dow - $weekstarts + 7) % 7;                // How many days to skip back to first day of week:
      // Then work out the time of the "sticky day"
      // We use gmmktime() here rather than mktime() as gmmktime() is independent of DST.   This avoids 
      // problems when we come to look at the difference ($diff) further down if the two ends of the
      // time period straddle a DST boundary.
      $sticky_time = gmmktime(12, 0, 0, $month, $day, $year);         // Use gm to avoid DST problems
      $start_highlight_gmtime = $sticky_time - ($skipback * SECONDS_PER_DAY);
    }
      
    $daysInMonth = $this->getMonthDays($this->month, $this->year);
    // $prevYear is the current year unless the previous month is
    // December then you need to decrement the year
    if( $this->month - 1 > 0 )
    {
      $prevMonth = $this->month - 1;
      $prevYear = $this->year;
    }
    else
    {
      $prevMonth = 12;
      $prevYear = $this->year -1;
    }
    $daysInPrevMonth = $this->getMonthDays($prevMonth, $prevYear);
    $date = mktime(12, 0, 0, $this->month, 1, $this->year);
      
    $first = (strftime("%w",$date) + 7 - $weekstarts) % 7;  // day number of the first day of the month
    $monthName = utf8_strftime($strftime_format['month_cal'], $date);
      
    $html .= "<table class=\"calendar\">\n";
    $html .= "<thead>\n";
    // prints month name and year
      
    $cols = 7;
    if ($mincals_week_numbers)
    {
      $cols++;
    }
    $html .= "<tr>\n";   
    $html .= "<th colspan=\"$cols\">";
    $link_day = $day;
    // decrement day until it's a valid one for the month, in case you're moving to a month with fewer
    // days than the current one
    while (!checkdate($this->month, $link_day, $this->year) && ($link_day > 1))
    {
      $link_day--;
    }
    $link = $this->getDateLink($link_day, $this->month, $this->year, 'month');
    $html .= "<a href=\"$link\">$monthName&nbsp;$this->year</a>";
    $html .= "</th>\n"; 
    $html .= "</tr>\n";
      
    $html .= "<tr>\n";
    if ($mincals_week_numbers)
    {
        $html .= "<th></th>\n";
    }
    // gets days of week
    $html .= $this->getDayNames();
    $html .= "</tr>\n";
    $html .= "</thead>\n";

    $html .= "<tbody>\n";
    $d = 1 - $first;   // this is the date of the top left hand cell in the calendar (which may of course
                       // be negative and therefore not displayed)
          
    while ($d <= $daysInMonth)
    {
      $html .= "<tr>\n";

      if ($mincals_week_numbers)
      {
          $html .= "<td class=\"mincals_week_number\">";
          $link = $this->getDateLink($d, $this->month, $this->year, 'week');
          $html .= "<a href=\"$link\">";
          $html .= date("W",gmmktime (12, 0, 0, $this->month, $d, $this->year));
          $html .= "</a>";
          $html .= "</td>\n";
      }
      for ($i = 0; $i < 7; $i++)
      {
        $day_of_week = ($i + $weekstarts) % 7;
        $class = is_weekend($day_of_week) ? "day_weekend" : "day_weekday";
        $hide_this_day = is_hidden_day($day_of_week);

        if ($hide_this_day)
        {
          $class .= " hidden";
        }
        if (($d < 0) || ($d > $daysInMonth))
        {
          $class .= " day_blank";
        }
        $html .= "<td class=\"$class\"";
          
        // If this cell is the "sticky day" (ie the day passed through in GET parameters)
        // then assign an id so that we can apply some special styling
        if (($d == $day) && ($this->month == $month) && ($this->year == $year))
        {
          $html .= " id=\"sticky_day\"";
        }
        $html .= ">";
          
        if ($d > 0 && $d <= $daysInMonth)   // valid days of the month
        {
          $link = $this->getDateLink($d, $this->month, $this->year);

          if ($link == "")
          {
            $html .= $d;
          }     
            
          else   // we have a valid link
          {
            // start the anchor or span, depending on whether it's a hidden day
            if ($hide_this_day)
            {
              $html .= "<span";
            }
            else
            {
              $html .= "<a";
            }
              
            // then work out whether to mark it as the current day/week/month

            if (preg_match("/day/i", $page))              // DAY VIEW
            {
              if (($d == $this->day) and ($this->h))
              {
                $html .= " class=\"current\"";
              }
            }             

            elseif (preg_match("/week/i", $page))         // WEEK VIEW
            {
              // work out current time and if we're up to 7 days after the
              // start of the week to be highlighted then mark it as the
              // current week.    We are using gmmktime() rather than mktime() because
              // gmmktime() is independent of DST and we do not want the difference
              // to be affected if the time period straddles a DST boundary.    See 
              // also the comment further up.
              $this_time = gmmktime (12, 0, 0, $this->month, $d, $this->year);  // Use gm to avoid DST problems
              $diff = $this_time - $start_highlight_gmtime;  // seconds
              $diff = $diff/86400;                           // days
              if (($diff >= 0) && ($diff < 7))             
              {
                $html .= " class=\"current\"";
              }         
            }             

            elseif (preg_match("/month/i", $page))        // MONTH VIEW
            {
              if ($this->h)
              {
                $html .= " class=\"current\"";
              }
            }
              
            // Now finish the anchor or span, depending on whether it's a hidden day
            if ($hide_this_day)
            {
              $html .= ">$d</span>";
            }
            else
            {
              $html .= " href=\"$link\">$d</a>";
            } 
          }
        }
        
        $html .= "</td>\n";
        $d++;
      }  // end for days in the week
      $html .= "</tr>\n";
    } // end while days in the month
      
    $html .= "</tbody>\n";
    $html .= "</table>\n";
      
    return $html;
  }
  
  
  // Takes an optional fourth argument $page (which can be 'day', 'week' or 'month')
  private function getDateLink($day, $month, $year)
  {
    $page = (func_num_args() > 3) ? func_get_arg(3) : $this->dmy;
    
    // Sanitize the date: the day may be negative
    $date = getdate(mktime(12, 0, 0, $month, $day, $year));
    
    $result = "$page.php?" .
              "year="  . $date['year'] . "&amp;" .
              "month=" . $date['mon']  . "&amp;" .
              "day="   . $date['mday'] . "&amp;" .
              "area="  . $this->area;
    
    if (!empty($this->room))
    {
      $result .= "&amp;room=".$this->room;
    }
    
    return $result;
  }
    

  private function getMonthDays($month, $year)
  {
      if ($month < 1 || $month > 12)
      {
          return 0;
      }
    
      $days = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
   
      $d = $days[$month - 1];
   
      if ($month == 2)
      {
          // Check for leap year
          // Forget the 4000 rule, I doubt I'll be around then...
        
          if ($year%4 == 0)
          {
              if ($year%100 == 0)
              {
                  if ($year%400 == 0)
                  {
                      $d = 29;
                  }
              }
              else
              {
                  $d = 29;
              }
          }
      }
    
      return $d;
  }

  
  private function getDayNames()
  {
    global $weekstarts, $strftime_format;
    
    static $html = null;
    
    if (isset($html))
    {
      return $html;
    }
    
    $html = '';
    
    for ($i=$weekstarts; $i<$weekstarts+7; $i++)
    {
      // Sunday is Day 0
      $day_name = utf8_strftime($strftime_format['dayname_cal'],
                                strtotime("next sunday + $i days"));
      $day_of_week = $i % 7;
      $class = is_weekend($day_of_week) ? "day_weekend" : "day_weekday";
      
      // add a class if it's a hidden day so that we can apply special styling
      if (is_hidden_day($day_of_week))
      {
        $class .= " hidden";
      }
      
      $html .= "<th class=\"$class\">$day_name</th>\n";
    }
    
    return $html;
  }
  
}


$lastmonth = mktime(12, 0, 0, $month-1, 1, $year);
$thismonth = mktime(12, 0, 0, $month,   $day, $year);
$nextmonth = mktime(12, 0, 0, $month+1, 1, $year);

echo "<nav id=\"cals\">\n";

echo "<div id=\"cal_last\">\n";
$cal = new Calendar(date("d",$lastmonth), date("m",$lastmonth), date("Y",$lastmonth), 0, $area, $room, $dmy);
echo $cal->getHTML();
echo "</div>\n";

echo "<div id=\"cal_this\">\n";
$cal = new Calendar(date("d",$thismonth), date("m",$thismonth), date("Y",$thismonth), 1, $area, $room, $dmy);
echo $cal->getHTML();
echo "</div>\n";

echo "<div id=\"cal_next\">\n";
$cal = new Calendar(date("d",$nextmonth), date("m",$nextmonth), date("Y",$nextmonth), 0, $area, $room, $dmy);
echo $cal->getHTML();
echo "</div>\n";

echo "</nav>\n";
}

