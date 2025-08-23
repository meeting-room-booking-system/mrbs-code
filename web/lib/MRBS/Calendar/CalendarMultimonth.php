<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use MRBS\Exception;

abstract class CalendarMultimonth extends Calendar
{
  protected $n_months;

  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id)
  {
    global $year_start;

    parent::__construct($view, $view_all, $year, $month, $day, $area_id, $room_id);

    if ($view === 'year')
    {
      $this->n_months = MONTHS_PER_YEAR;
    }
    else
    {
      throw new Exception("Invalid view: '$view'");
    }

    // Get the start and end dates
    $this->start_date = (new DateTime())->setDate($this->year, $this->month, 1);
    $this->start_date->setMonthYearStart($year_start);
    $this->start_date->setStartFirstSlot();

    $this->end_date = clone $this->start_date;
    $this->end_date->modify('+' . $this->n_months . ' month');
    $this->end_date->modify('-1 day');
    $this->end_date->setEndLastSlot();
  }

}
