<?php
namespace MRBS;

class DateTime extends \DateTime
{

  public function getDay()
  {
    return intval($this->format('j'));
  }


  public function getMonth()
  {
    return intval($this->format('n'));
  }


  public function getYear()
  {
    return intval($this->format('Y'));
  }


  // Determines whether the date is a holiday, as defined
  // in the config variable $holidays.
  private function isHolidayConfig() : bool
  {
    global $holidays;

    $year = $this->format('Y');

    if (in_array($this->format('Y-m-d'), $holidays[$year]))
    {
      return true;
    }

    return false;
  }


  // Determines whether the date is a holiday.
  public function isHoliday() : bool
  {
    if ($this->isHolidayConfig())
    {
      return true;
    }

    // This method can be extended to check other sources, eg a .ics
    // file or a database table.
    return false;
  }


  public function isWeekend() : bool
  {
    return is_weekend($this->format('w'));
  }

}
