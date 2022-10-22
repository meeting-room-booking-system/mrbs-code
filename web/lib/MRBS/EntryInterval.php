<?php
declare(strict_types=1);
namespace MRBS;

use DateInterval;

class EntryInterval
{
  private $start_date;
  private $end_date;


  // $start_time and $end_time are Unix timestamps
  public function __construct(int $start_time, int $end_time)
  {
    $this->start_date = new DateTime();
    $this->start_date->setTimestamp($start_time);
    $this->end_date = new DateTime();
    $this->end_date->setTimestamp($end_time);
  }


  // Checks whether the interval overlaps a holiday.  Returns FALSE if it doesn't,
  // or the first overlapped holiday as an MRBS\DateTime object if it does.
  public function overlapsHoliday()
  {
    // Zero the $date and $end times so that the while condition works.
    $date = clone $this->start_date;
    $date->setTime(0,0);
    $end = clone $this->end_date;
    $end->setTime(0, 0);

    while ($date <= $end)
    {
      if ($date->isHoliday())
      {
        return $date;
      }
      $date->add(new DateInterval('P1D'));
    }

    return false;
  }


  // Checks whether the interval overlaps a weekend.  Returns FALSE if it doesn't,
  // or the first overlapped weekend day as an MRBS\DateTime object if it does.
  public function overlapsWeekend()
  {
    // Zero the $date and $end times so that the while condition works.
    $date = clone $this->start_date;
    $date->setTime(0,0);
    $end = clone $this->end_date;
    $end->setTime(0, 0);
    $i = 0;

    // Don't check more than a week's worth of days in case no weekend days have been defined
    while (($date <= $end) && ($i<DAYS_PER_WEEK))
    {
      if ($date->isWeekend())
      {
        return $date;
      }
      $date->add(new DateInterval('P1D'));
      $i++;
    }

    return false;
  }

}
