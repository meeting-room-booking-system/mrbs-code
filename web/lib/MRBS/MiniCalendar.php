<?php

namespace MRBS;

// Note MRBS has its own extension of the Date class, so no need for a 'use \Date;'
use \DateInterval;


class MiniCalendar
{
  private $calendar;
  private $view;
  private $date;
  private $area;
  private $room;
  
  
  public function __construct($view, $year, $month, $day, $area, $room)
  {
    $this->date = new DateTime();
    $this->date->setDate($year, $month, $day);
    $this->calendar = $this->getCalendar();
    $this->view = $view;
    $this->area = $area;
    $this->room = $room;
  }
  
  
  public function toHTML()
  {
    $html = '';
    
    $html .= "<table>\n";
    
    $html .= $this->toHTMLCaption();
    $html .= $this->toHTMLHead();
    $html .= $this->toHTMLBody();
    
    $html .= "</table>\n";
    
    return $html;
  }
  
  
  // Produce the table head
  private function toHTMLCaption()
  {
    global $strftime_format;
    
    $html = '';
    
    $html .= "<caption>\n";
    
    $month_string = utf8_strftime($strftime_format['minical_caption'], $this->date->getTimestamp());
    $html .= htmlspecialchars($month_string);
    
    $html .= "</caption>\n";
    
    return $html;
  }
  
  
  // Produce the table head
  private function toHTMLHead()
  {
    global $weekstarts, $strftime_format;
    
    $html = '';
    
    $html .= "<thead>\n";
    $html .= "<tr>";
    
    for ($i=$weekstarts; $i<$weekstarts+7; $i++)
    {
      // Sunday is Day 0
      $day_name = utf8_strftime($strftime_format['minical_dayname'],
                                strtotime("next sunday + $i days"));
      $html .= '<th>' . htmlspecialchars($day_name) . '</th>';
    }
    
    $html .= "</tr>\n";       
    $html .= "</thead>\n";
    
    return $html;
  }
  
  
  // Produce the table body
  private function toHTMLBody()
  {
    $html = '';
    
    $html .= "<tbody>\n";
    
    $date = clone $this->date;
    $date->setDate($date->format('Y'), $date->format('m') - 1, $this->calendar[0]);
    $interval = new DateInterval('P1D');  // one day
    
    for ($i=0; $i<count($this->calendar); $i++)
    {
      if ($i == 0)
      {
        $html .= "<tr>\n";
      }
      elseif ($i%7 == 0)
      {
        $html .= "</tr>\n<tr>\n";
      }
      
      $vars = array('view'      => $this->view,
                    'page_date' => $date->format('Y-m-d'),
                    'area'      => $this->area,
                    'room'      => $this->room);
                    
      $query = http_build_query($vars, '', '&');
      
      $html .= '<td>';
      $html .= '<a href="calendar.php?' . htmlspecialchars($query) . '">' . $this->calendar[$i] . '</a>';
      $html .= "</td>\n";
      $date->add($interval);
    }
    $html .= "</tr>\n";
    
    $html .= "</tbody>\n";
    
    return $html;
  }
  
  
  // Gets a month calendar for the given $year, $month, $day.  Returns a six week calendar
  // which is just an array of dates, starting on $weekstarts.  It's always six weeks so
  // that tables produced from it are always the same height.
  private function getCalendar()
  {
    global $weekstarts;
    
    $calendar = array();
    
    $date = clone $this->date;
    $d = $date->format('t');  // the last day of the month
    // Go to the last day of the month
    $date->setDate($date->format('Y'), $date->format('n'), $d);
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