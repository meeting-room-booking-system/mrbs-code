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
    $date = $this->start_date;
    $date->setTime(0,0);
    $end = $this->end_date;
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

}
