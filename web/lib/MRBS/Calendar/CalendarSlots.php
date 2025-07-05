<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use function MRBS\hm_before;
use function MRBS\period_index_nominal;

abstract class CalendarSlots extends Calendar
{
  // $s is nominal seconds
  protected function getQueryVars(string $view, int $area, int $room, int $month, int $day, int $year, int $s) : array
  {
    global $morningstarts, $morningstarts_minutes;

    $result = array();

    // check to see if the time is really on the next day
    $date = getdate(mktime(0, 0, $s, $month, $day, $year));
    if (hm_before($date,
      array('hours' => $morningstarts, 'minutes' => $morningstarts_minutes)))
    {
      $date['hours'] += 24;
    }
    $hour = $date['hours'];
    $minute = $date['minutes'];
    $period = period_index_nominal($s);

    $vars = array('view'  => $view,
      'year'  => $year,
      'month' => $month,
      'day'   => $day,
      'area'  => $area);

    $result['booking']     = $vars;
    $result['new_periods'] = array_merge($vars, array('room' => $room, 'period' => $period));
    $result['new_times']   = array_merge($vars, array('room' => $room, 'hour' => $hour, 'minute' => $minute));

    return $result;
  }

}
