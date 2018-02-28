<?php

namespace MRBS;

// Note MRBS has its own extension of the Date class, so no need for a 'use \Date;'
use \DateInterval;


class MiniCalendar
{
  private $date;      // The DateTime object representing this calendar
  private $calendar;  // An array of days
  
  // These properties are all concerned with the current context
  private $view;
  private $year;
  private $month;
  private $day;
  private $area;
  private $room;
  private $date_view;            // The DateTime object for the current context date
  private $date_view_weekstart;  // The DateTime object for the start of the week for the curent context date
  
  
  public function __construct($view, $year, $month, $day, $area, $room, $mincal=null)
  {
    global $weekstarts;
    
    if (isset($mincal))
    {
      $this->date = DateTime::createFromFormat('Y-m', $mincal);
    }
    else
    {
      $this->date = new DateTime();
      $this->date->setDate($year, $month, $day);
    }
    
    $this->calendar = $this->getCalendar();
    
    $this->view  = $view;
    $this->year  = $year;
    $this->month = $month;
    $this->day   = $day;
    $this->area  = $area;
    $this->room  = $room;
    
    $this->date_view = new DateTime();
    $this->date_view->setDate($year, $month, $day);
    // Get the start of the week
    $this->date_view_weekstart = new DateTime();
    $n = ($this->date_view->format('w') + 7 - $weekstarts) % 7;
    $this->date_view_weekstart->setDate($year, $month, $day - $n);
  }
  
  
  public function add($interval_spec)
  {
    $interval = new DateInterval($interval_spec);
    $this->date->add($interval);
    $this->calendar = $this->getCalendar();
    return $this;
  }
  
  
  public function sub($interval_spec)
  {
    $interval = new DateInterval($interval_spec);
    $this->date->sub($interval);
    $this->calendar = $this->getCalendar();
    return $this;
  }
  
  
  // $page is the page to link to
  public function toHTML($hidden=false, $page=null)
  {
    if (!isset($page))
    {
      $page = this_page();
    }
    
    $html = '';
    
    $html .= '<table class="minicalendar"' ;
    $html .= ' data-month="' . $this->date->format('Y-m') . '"';
    $html .= ($hidden) ? ' style="display: none"' : '';
    $html .= ">\n";
    
    $html .= $this->toHTMLHead($page);
    $html .= $this->toHTMLBody();
    
    $html .= "</table>\n";
    
    return $html;
  }
  
  
  // Produce the table head
  private function toHTMLHead($page)
  {
    global $weekstarts, $strftime_format;
    
    $html = '';
    
    $html .= "<thead>\n";
    
    // The first row: month and year
    $interval = new DateInterval('P1M');  // one month
    
    $date = clone $this->date;
    $month_prev = $date->sub($interval)->format('Y-m');
    $date = clone $this->date;
    $month_next = $date->add($interval)->format('Y-m');
    
    $vars = array('view'  => $this->view,
                  'year'  => $this->year,
                  'month' => $this->month,
                  'day'   => $this->day,
                  'area'  => $this->area,
                  'room'  => $this->room);
    
    $vars['mincal'] = $month_prev;
    $query_prev = http_build_query($vars, '', '&');
    
    $vars['mincal'] = $month_next;
    $query_next = http_build_query($vars, '', '&');
    
    $html .= "<tr>";
    
    // content for the prev and next links will be completed by CSS
    $html .= '<th><a class="arrow prev" href="' . htmlspecialchars("$page?$query_prev") . '"></a></th>';
    $month_string = utf8_strftime($strftime_format['minical_monthname'], $this->date->getTimestamp());
    $html .= '<th colspan="5"><span>' . htmlspecialchars($month_string) . '</span></th>';
    $html .= '<th><a class="arrow next" href="' . htmlspecialchars("$page?$query_next") . '"></a></th>';
    $html .= "</tr>";
    
    // Next row: day name
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
    $date_today = new DateTime();
    $today = $date_today->format('Y-m-d');
    
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
      
      $classes = $this->getClasses($date);
      
      $html .= '<td';
      if (!empty($classes))
      {
        $html .= ' class="' . implode(' ', $classes) . '"';
      }
      $html .= '>';
      $html .= '<a href="index.php?' . htmlspecialchars($query) . '">' . $this->calendar[$i] . '</a>';
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
  
  
  private function getClasses(DateTime $date)
  {
    $classes = array();
    
    // Check whether it's today
    $today = new DateTime();
    if ($date->format('Y-m-d') == $today->format('Y-m-d'))
    {
      $classes[] = 'today';
    }
    
    // Check whether it's part of the view
    switch ($this->view)
    {
      case 'day':
        if ($date->format('Y-m-d') == $this->date_view->format('Y-m-d'))
        {
          $classes[] = 'view';
        }
        break;
      case 'week':
        $interval = $this->date_view_weekstart->diff($date)->format('%r%a');
        if (($interval >=0) && ($interval < 7))
        {
          $classes[] = 'view';
        }
        break;
      case 'month':
        if ($date->format('Y-m') == $this->date_view->format('Y-m'))
        {
          $classes[] = 'view';
        }
        break;
      default:
        throw new \Exception("Unknown view '$view'");
        break;
    }
    
    return $classes;
  }
  
}