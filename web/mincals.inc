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
         return $this->dmy.".php?year=$year&month=$month&day=$day&area=".$this->area;
      else
         return $this->dmy.".php?year=$year&month=$month&day=$day&area=".$this->area."&room=".$this->room;
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
         $s .= "<td align=center valign=top class=\"calendarHeader\">$fl</td>\n";
      }
      return $s;
    }

    function getHTML()
    {
        global $weekstarts;
        global $PHP_SELF;
        global $day;
        global $month;

        if (!isset($weekstarts)) $weekstarts = 0;
        $s = "";
        
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
        
        $first = (strftime("%w",$date) + 7 - $weekstarts) % 7;
        $monthName = utf8_strftime("%B",$date);
        
        //$prevMonth = $this->getCalendarLink($this->month - 1 >   0 ? $this->month - 1 : 12, $this->month - 1 >   0 ? $this->year : $this->year - 1);
        //$nextMonth = $this->getCalendarLink($this->month + 1 <= 12 ? $this->month + 1 :  1, $this->month + 1 <= 12 ? $this->year : $this->year + 1);
        
        $s .= "<table class=\"calendar\">\n";
        // prints month name and year
        $s .= "<tr>\n";
        //$s .= "<td align=center valign=top>" . (($prevMonth == "") ? "&nbsp;" : "<a href=\"$prevMonth\">&lt;&lt;</a>")  . "</td>\n";
        $s .= "<td align=center valign=top class=\"calendarHeader\" colspan=7>$monthName&nbsp;$this->year</td>\n"; 
        //$s .= "<td align=center valign=top>" . (($nextMonth == "") ? "&nbsp;" : "<a href=\"$nextMonth\">&gt;&gt;</a>")  . "</td>\n";
        $s .= "</tr>\n";
        
        $s .= "<tr>\n";
        // gets days of week
        $s .= $this->getFirstDays();
        $s .= "</tr>\n";

        $d = 1 - $first;
            
        # this is used to highlight days in upcoming month
        $days_to_highlight = ($d + 7);

        while ($d <= $daysInMonth)
        {
            $s .= "<tr>\n";

            for ($i = 0; $i < 7; $i++)
            {
                $s .= "<td class=\"calendar\" align=\"center\" valign=\"top\">";
                if ($d > 0 && $d <= $daysInMonth)
                {
                    $link = $this->getDateLink($d, $this->month, $this->year);
                    $d_week = ($d - 7);

                    if ($link == "")
                        $s .= $d;
                    elseif (preg_match("/day/i", basename($PHP_SELF)))
                    {
                        if (($d == $this->day) and ($this->h))
                            $s .= "<a href=\"$link\"><font class=\"calendarHighlight\">$d</font></a>";
                        else
                            $s .= "<a href=\"$link\">$d</a>";
                    }
                    elseif (preg_match("/week/i", basename($PHP_SELF)))
                    {
		    #echo "((".$this->day." < $days_to_highlight) && ($d < $days_to_highlight) && (($day - $daysInMonth) > (-6)) && (".$this->month." == ($month + 1)) && ($first != 0))<br>";
                        if (($this->day <= $d) && ($this->day > $d_week) && ($this->h))
                            $s .= "<a href=\"$link\"><font class=\"calendarHighlight\">$d</font></a>";
                        elseif (($this->day < $days_to_highlight) && ($d < $days_to_highlight) && (($day - $daysInPrevMonth) > (-6)) && ($this->month == (($month + 1)%12)) && ($first != 0))
                            $s .= "<a href=\"$link\"><font class=\"calendarHighlight\">$d</font></a>";
                        else
                            $s .= "<a href=\"$link\">$d</a>";
                    }
                    elseif (preg_match("/month/i", basename($PHP_SELF)))
                        if ( $this->h )
                            $s .= "<a href=\"$link\"><font class=\"calendarHighlight\">$d</font></a>";
                        else
                            $s .= "<a href=\"$link\">$d</a>";
                }
                else
                {
                    $s .= "&nbsp;";
                }
                $s .= "</td>\n";
                $d++;
            }
            $s .= "</tr>\n";
        }
        
        $s .= "</table>\n";
        
        return $s;
    }
}

$lastmonth = mktime(12, 0, 0, $month-1, 1, $year);
$thismonth = mktime(12, 0, 0, $month,   $day, $year);
$nextmonth = mktime(12, 0, 0, $month+1, 1, $year);

echo "<td>";
$cal = new Calendar(date("d",$lastmonth), date("m",$lastmonth), date("Y",$lastmonth), 0, $area, $room, $dmy);
echo $cal->getHTML();
echo "</td>";

echo "<td>";
$cal = new Calendar(date("d",$thismonth), date("m",$thismonth), date("Y",$thismonth), 1, $area, $room, $dmy);
echo $cal->getHTML();
echo "</td>";

echo "<td>";
$cal = new Calendar(date("d",$nextmonth), date("m",$nextmonth), date("Y",$nextmonth), 0, $area, $room, $dmy);
echo $cal->getHTML();
echo "</td>";
}
?>
