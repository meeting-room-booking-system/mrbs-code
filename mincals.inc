<?php
function minicals($year, $month, $day, $area) {

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
    
    function Calendar($day, $month, $year, $h, $area)
    {
        $this->day   = $day;
        $this->month = $month;
        $this->year  = $year;
        $this->h     = $h;
        $this->area  = $area;
    }
   
    
    function getCalendarLink($month, $year)
    {
        return "";
    }
    
   function getDateLink($day, $month, $year) {
      return "day.php3?year=$year&month=$month&day=$day&area=".$this->area;
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

//      $basetime = mktime(12,0,0,6,11,2000);
      $basetime = mktime(12,0,0,6,11 + ($weekstarts == 1),2000);

      for($i = 0, $s = ""; $i < 7; $i++)
      {
         $show = $basetime + ($i * 24 * 60 * 60);
         $fl = substr(strftime('%a',$show),0,1);
        	$s .= "<td align=center valign=top class=calendarHeader>$fl</td>\n";
      }
      return $s;
    }

    function getHTML()
    {
        global $weekstarts;
        $s = "";
        
    	$daysInMonth = $this->getDaysInMonth($this->month, $this->year);
    	$date = mktime(12, 0, 0, $this->month, 1, $this->year);
    	
//    	$first = strftime("%w",$date);
    	$first = strftime("%u",$date);

    	$monthName = strftime("%B",$date);
    	
    	$prevMonth = $this->getCalendarLink($this->month - 1 >   0 ? $this->month - 1 : 12, $this->month - 1 >   0 ? $this->year : $this->year - 1);
    	$nextMonth = $this->getCalendarLink($this->month + 1 <= 12 ? $this->month + 1 :  1, $this->month + 1 <= 12 ? $this->year : $this->year + 1);
    	
    	$s .= "<table class=calendar>\n";
    	$s .= "<tr>\n";
    	//$s .= "<td align=center valign=top>" . (($prevMonth == "") ? "&nbsp;" : "<a href=\"$prevMonth\">&lt;&lt;</a>")  . "</td>\n";
    	$s .= "<td align=center valign=top class=calendarHeader colspan=7>$monthName&nbsp;$this->year</td>\n"; 
    	//$s .= "<td align=center valign=top>" . (($nextMonth == "") ? "&nbsp;" : "<a href=\"$nextMonth\">&gt;&gt;</a>")  . "</td>\n";
    	$s .= "</tr>\n";
    	
    	$s .= "<tr>\n";
        $s .= $this->getFirstDays();
    	$s .= "</tr>\n";
    	
//    	$d = 1 - $first;
    	$d = ((($weekstarts == 1) ? -6 : -7 ) - $first) % 7 + 1;
    	   	
    	while ($d <= $daysInMonth)
    	{
    	    $s .= "<tr>\n";       
    	    
    	    for ($i = 0; $i < 7; $i++)
    	    {
    	        $s .= "<td class=calendar align=right valign=top>";       
    	        if ($d > 0 && $d <= $daysInMonth)
    	        {
    	            $link = $this->getDateLink($d, $this->month, $this->year);
                  $col = ((($d == $this->day) and ($this->h)) ? "<font color=red>" : "");
                  $s .= (($link == "") ? $d : "<a href=\"$link\">$col$d</a>");
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

$lastmonth = mktime(0, 0, 0, $month-1, 1, $year);
$thismonth = mktime(0, 0, 0, $month,   $day, $year);
$nextmonth = mktime(0, 0, 0, $month+1, 1, $year);

echo "<td>";
$cal = new Calendar(date("d",$lastmonth), date("m",$lastmonth), date("Y",$lastmonth), 0, $area);
echo $cal->getHTML();
echo "</td>";

echo "<td>";
$cal = new Calendar(date("d",$thismonth), date("m",$thismonth), date("Y",$thismonth), 1, $area);
echo $cal->getHTML();
echo "</td>";

echo "<td>";
$cal = new Calendar(date("d",$nextmonth), date("m",$nextmonth), date("Y",$nextmonth), 0, $area);
echo $cal->getHTML();
echo "</td>";
}
?>
