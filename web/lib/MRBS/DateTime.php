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
    global $holidays, $debug;

    $year = $this->format('Y');
    $iso_date = $this->format('Y-m-d');

    if (!empty($holidays[$year]))
    {
      foreach ($holidays[$year] as $holiday)
      {
        $limits = explode('..', $holiday);

        // Check that the dates are valid
        foreach ($limits as $limit)
        {
          if (!validate_iso_date($limit))
          {
            // Only trigger an error if debugging, otherwise there will be thousands of error messages
            if ($debug)
            {
              trigger_error("Invalid holiday date '$limit'", E_USER_NOTICE);
            }
            continue 2;
          }
        }

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
        else
        {
          // Only trigger an error if debugging, otherwise there will be thousands of error messages
          if ($debug)
          {
            trigger_error("Invalid holiday element '$holiday'", E_USER_NOTICE);
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
