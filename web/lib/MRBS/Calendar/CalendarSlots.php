<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use function MRBS\hm_before;
use function MRBS\period_index_nominal;

abstract class CalendarSlots extends Calendar
{
  protected $day;
  protected $month;
  protected $year;
  protected $area_id;
  protected $room_id;
  protected $view;
  protected $timetohighlight;


  // $s is nominal seconds
  protected function getQueryVars(int $room, int $month, int $day, int $year, int $s) : array
  {
    global $morningstarts, $morningstarts_minutes;

    $result = [];

    // check to see if the time is really on the next day
    $date = getdate(mktime(0, 0, $s, $month, $day, $year));
    if (hm_before($date, ['hours' => $morningstarts, 'minutes' => $morningstarts_minutes]))
    {
      $date['hours'] += 24;
    }
    $hour = $date['hours'];
    $minute = $date['minutes'];
    $period = period_index_nominal($s);

    $vars = [
      'view'  => $this->view,
      'year'  => $year,
      'month' => $month,
      'day'   => $day,
      'area'  => $this->area_id
    ];

    $result['booking']     = $vars;
    $result['new_periods'] = array_merge($vars, ['room' => $room, 'period' => $period]);
    $result['new_times']   = array_merge($vars, ['room' => $room, 'hour' => $hour, 'minute' => $minute]);

    return $result;
  }

}
