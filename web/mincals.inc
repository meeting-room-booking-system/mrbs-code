<?php
// $Id$

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
    var $month;
    var $year;
    var $day;
    var $h;
    var $area;
    var $room;
    var $dmy;
    
    function Calendar($day, $month, $year, $h, $area, $room, $dmy)
    {
        $this->day   = $day;
        $this->month = $month;
        $this->year  = $year;
        $this->h     = $h;
        $this->area  = $area;
        $this->room  = $room;
        $this->dmy   = $dmy;
    }
   
    
    function getCalendarLink($month, $year)
    {
        return "";
    }
    
   function getDateLink($day, $month, $year)
   {
      if( empty($this->room) )
         return $this->dmy.".php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area;
      else
         return $this->dmy.".php?year=$year&amp;month=$month&amp;day=$day&amp;area=".$this->area."&amp;room=".$this->room;
   }
    

    function getDaysInMonth($month, $year)
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

    function getFirstDays()
    {
      global $weekstarts;

      $basetime = mktime(12,0,0,6,11+$weekstarts,2000);
      for ($i = 0, $s = ""; $i < 7; $i++)
      {
        $show = $basetime + ($i * 24 * 60 * 60);
        $fl = utf8_strftime('%a',$show);
        // add a class if it's a hidden day so that we can apply special styling
        if (is_hidden_day(($i + $weekstarts) % 7))
        {
          $s .= "<th class=\"hidden\">$fl</th>\n";
        }
        else
        {
          $s .= "<th>$fl</th>\n";
        }
      }
      return $s;
    }

    function getHTML()
    {
      global $weekstarts;
      global $PHP_SELF;
      global $day;
      global $month;
      global $year;
      global $mincals_week_numbers;

      if (!isset($weekstarts)) $weekstarts = 0;
      $s = "";
      
      // For the week view we will need to know the start of the week that should be highlighted
      if (preg_match("/week/i", basename($PHP_SELF)))
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
        $start_highlight_gmtime = $sticky_time - ($skipback * 24 * 60 * 60);
      }
      
      $daysInMonth = $this->getDaysInMonth($this->month, $this->year);
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
      $daysInPrevMonth = $this->getDaysInMonth($prevMonth, $prevYear);
      $date = mktime(12, 0, 0, $this->month, 1, $this->year);
      
      $first = (strftime("%w",$date) + 7 - $weekstarts) % 7;  // day number of the first day of the month
      $monthName = utf8_strftime("%B",$date);
      
      //$prevMonth = $this->getCalendarLink($this->month - 1 >   0 ? $this->month - 1 : 12, $this->month - 1 >   0 ? $this->year : $this->year - 1);
      //$nextMonth = $this->getCalendarLink($this->month + 1 <= 12 ? $this->month + 1 :  1, $this->month + 1 <= 12 ? $this->year : $this->year + 1);
      
      $s .= "<table class=\"calendar\">\n";
      $s .= "<thead>\n";
      // prints month name and year
      
      $cols = 7;
      if ($mincals_week_numbers)
      {
        $cols++;
      }
      $s .= "<tr>\n";   
      $s .= "<th colspan=\"$cols\">$monthName&nbsp;$this->year</th>\n"; 
      $s .= "</tr>\n";
      
      $s .= "<tr>\n";
      if ($mincals_week_numbers)
      {
          $s .= "<th></th>\n";
      }
      // gets days of week
      $s .= $this->getFirstDays();
      $s .= "</tr>\n";
      $s .= "</thead>\n";

      $s .= "<tbody>\n";
      $d = 1 - $first;   // this is the date of the top left hand cell in the calendar (which may of course
                         // be negative and therefore not displayed)
          
      while ($d <= $daysInMonth)
      {
        $s .= "<tr>\n";

        if ($mincals_week_numbers)
        {
            $s .= "<td valign=\"middle\" class=\"mincals_week_number\">".date("W",gmmktime (12, 0, 0, $this->month, $d, $this->year))."</td>\n";
        }
        for ($i = 0; $i < 7; $i++)
        {
          $hide_this_day = is_hidden_day(($i + $weekstarts) % 7);
          $s .= "<td";

          if ($hide_this_day)
          {
            $s .= " class=\"hidden\"";
          }
          
          // If this cell is the "sticky day" (ie the day passed through in GET parameters)
          // then assign an id so that we can apply some special styling
          if (($d == $day) && ($this->month == $month) && ($this->year == $year))
          {
            $s .= " id=\"sticky_day\"";
          }
          $s .= ">";
          
          if ($d > 0 && $d <= $daysInMonth)   // valid days of the month
          {
            $link = $this->getDateLink($d, $this->month, $this->year);

            if ($link == "")
            {
              $s .= $d;
            }     
            
            else   // we have a valid link
            {
              // start the anchor or span, depending on whether it's a hidden day
              if ($hide_this_day)
              {
                $s .= "<span";
              }
              else
              {
                $s .= "<a";
              }
              
              // then work out whether to mark it as the current day/week/month

              if (preg_match("/day/i", basename($PHP_SELF)))              // DAY VIEW
              {
                if (($d == $this->day) and ($this->h))
                {
                  $s .= " class=\"current\"";
                }
              }             

              elseif (preg_match("/week/i", basename($PHP_SELF)))         // WEEK VIEW
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
                  $s .= " class=\"current\"";
                }         
              }             

              elseif (preg_match("/month/i", basename($PHP_SELF)))        // MONTH VIEW
              {
                if ($this->h)
                {
                  $s .= " class=\"current\"";
                }
              }
              
              // Now finish the anchor or span, depending on whether it's a hidden day
              if ($hide_this_day)
              {
                $s .= ">$d</span>";
              }
              else
              {
                $s .= " href=\"$link\">$d</a>";
              } 
            }
          }
          
          else    // invalid days of the month
          {
            $s .= "&nbsp;";
          }
          $s .= "</td>\n";
          $d++;
        }  // end for days in the week
        $s .= "</tr>\n";
      } // end while days in the month
      
      $s .= "</tbody>\n";
      $s .= "</table>\n";
      
      return $s;
    }
}
        
$lastmonth = mktime(12, 0, 0, $month-1, 1, $year);
$thismonth = mktime(12, 0, 0, $month,   $day, $year);
$nextmonth = mktime(12, 0, 0, $month+1, 1, $year);

echo "<div id=\"cals\">\n";

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

echo "</div>\n";
}
?>
