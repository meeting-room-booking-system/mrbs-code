<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use InvalidArgumentException;

class CalendarFactory
{
  public static function create(
    string $view,
    int $view_all,
    int $year,
    int $month,
    int $day,
    int $area_id,
    int $room_id,
    ?int $timetohighlight=null,
    ?string $kiosk=null) : Calendar
  {
    switch ($view)
    {
      case 'day':
        return new CalendarMultislotDay($view, $view_all, $year, $month, $day, $area_id, $room_id, $timetohighlight, $kiosk);
        break;
      case 'week':
        if ($view_all)
        {
          return new CalendarMultidayMultiroom($view, $view_all, $year, $month, $day, $area_id, $room_id);
        }
        return new CalendarMultislotWeek($view, $view_all, $year, $month, $day, $area_id, $room_id, $timetohighlight);
        break;
      case 'month':
        if ($view_all)
        {
          return new CalendarMultidayMultiroom($view, $view_all, $year, $month, $day, $area_id, $room_id);
        }
        return new CalendarMonthOneRoom($view, $view_all, $year, $month, $day, $area_id, $room_id);
        break;
      case 'year':
        if ($view_all)
        {
          return new CalendarMultimonthMultiroom($view, $view_all, $year, $month, $day, $area_id, $room_id);
        }
        return new CalendarMultimonthOneRoom($view, $view_all, $year, $month, $day, $area_id, $room_id);
        break;
      default:
        throw new InvalidArgumentException("Invalid view: $view");
        break;
    }
  }
}
