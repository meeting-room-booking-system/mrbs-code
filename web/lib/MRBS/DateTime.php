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
    $iso_date = $this->format('Y-m-d');

    if (!empty($holidays[$year]))
    {
      foreach ($holidays[$year] as $holiday)
      {
        $limits = explode('..', $holiday);
        if (count($limits) == 1)
        {
          // It's a single date of the form '2022-01-01'
          if ($iso_date == $limits[0])
          {
            return true;
          }
        }
        elseif (count($limits) == 2)
        {
          // It's a range of the form '2022-07-01..2022-07-31'
          if (($iso_date >= $limits[0]) && ($iso_date <= $limits[1]))
          {
            return true;
          }
        }
      }
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
