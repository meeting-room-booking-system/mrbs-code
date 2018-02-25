<?php

namespace MRBS;

// Note MRBS has its own extension of the Date class, so no need for a 'use \Date;'
use \DateInterval;


class MiniCalendar
{
  private $calendar;
  private $view;
  private $year;
  private $month;
  private $day;
  private $area;
  private $room;
  
  
  public function __construct($view, $year, $month, $day, $area, $room)
  {
    $this->calendar = self::getCalendar($year, $month, $day);
  }
  
  
  public function toHTML()
  {
    global $weekstarts, $strftime_format;
    
    $html = '';
    
    $html .= "<table>\n";
    
    // Produce the table head
    $html .= "<thead>\n";
    $html .= "<tr>";
    
    for ($i=$weekstarts; $i<$weekstarts+7; $i++)
    {
      // Sunday is Day 0
      $day_name = utf8_strftime($strftime_format['dayname_cal'],
                                strtotime("next sunday + $i days"));
      $html .= '<th>' . htmlspecialchars($day_name) . '</th>';
    }
    
    $html .= "</tr>\n";       
    $html .= "</thead>\n";
    
    // Now the table body
    $html .= "<tbody>\n";
    for ($i=0; $i<count($this->calendar); $i++)
    {
      if ($i == 0)
      {
        $html .= "<tr>";
      }
      elseif ($i%7 == 0)
      {
        $html .= "</tr><tr>\n";
      }
      $html .= "<td>" . $this->calendar[$i] . "</td>";
    }
    $html .= "</tr>\n";
    
    $html .= "</tbody>\n";
    $html .= "</table>\n";
    
    return $html;
  }
  
  
  // Gets a month calendar for the given $year, $month, $day.   (We need to specify
  // $day as the year-month-day may not be a valid day, eg 2020-12-32 which will be
  // interpreted as 2021-01-01).  Returns a six week calendar which is just an array
  // of dates, starting on $weekstarts.  It's always six weeks so that tables produced
  // from it are always the same height.
  private static function getCalendar($year, $month, $day)
  {
    global $weekstarts;
    
    $calendar = array();
    
    $date = new DateTime();
    $date->setDate($year, $month, $day);
    $d = $date->format('t');  // the last day of the month
    // Go to the last day of the month
    $date->setDate($year, $month, $d);
    $day_of_week = $date->format('w');
    
    $interval = new DateInterval('P1D');  // one day
    
    // Work backwards through the month from the last day until the first
    while ($d > 0)
    {
      array_unshift($calendar, $d);
      $d--;
      $day_of_week = ($day_of_week + 6) % 7;  // in other words -1 mod 7
      $date->sub($interval);
    }
    
    // Then fill in any days of the week before the start of the month
    // We use a do while loop rather than a while loop because we want to
    // have at least some of the previous month (in order to ensure we have
    // a six week calendar).
    do
    {
      array_unshift($calendar, $date->format('j'));
      $day_of_week = ($day_of_week + 6) % 7;  // in other words -1 mod 7
      $date->sub($interval);
    }
    while ($weekstarts != ($day_of_week+1)%7);
    
    // Now fill in at the end of the calendar to make it up to six weeks
    for ($i=count($calendar), $d=1; $i<42; $i++, $d++)
    {
      array_push($calendar, $d);
    }
    
    return $calendar;
  }
  
}