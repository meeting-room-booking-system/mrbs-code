<?php
declare(strict_types=1);
namespace MRBS;

use UnexpectedValueException;

class DateTime extends \DateTime
{
  private static $isHoliday = array();
  private static $validHolidays = array();

  private const HOLIDAY_RANGE_OPERATOR = '..';


  public function getDay() : int
  {
    return intval($this->format('j'));
  }


  public function getMonth() : int
  {
    return intval($this->format('n'));
  }


  public function getYear() : int
  {
    return intval($this->format('Y'));
  }


  // Returns a date in ISO 8601 format ('yyyy-mm-dd')
  public function getISODate() : string
  {
    return $this->format('Y-m-d');
  }


  // Checks whether the config setting of holidays for $year consists
  // of a valid set of dates.
  private static function validateHolidays(string $year) : bool
  {
    global $holidays;

    // Only need to validate a year once, so store the answer in a static property
    if (!isset(self::$validHolidays[$year]))
    {
      try
      {
        foreach ($holidays[$year] as $holiday)
        {
          $limits = explode(self::HOLIDAY_RANGE_OPERATOR, $holiday);

          foreach ($limits as $limit)
          {
            // Check that the dates are valid
            if (!validate_iso_date($limit))
            {
              throw new UnexpectedValueException("invalid holiday date '$limit'.");
            }
            // Check that the year is correct
            if ($year != split_iso_date($limit)[0])
            {
              throw new UnexpectedValueException("the holiday '$limit' does not occur in the year '$year'.");
            }
          }

          // Check that we haven't got more than two limits
          if (count($limits) > 2)
          {
            throw new UnexpectedValueException("invalid range '$holiday'; there is more than one " .
              "range operator (" . self::HOLIDAY_RANGE_OPERATOR . ").");
          }
          // Check that the end of the range isn't before the beginning
          elseif ((count($limits) == 2) && ($limits[1] < $limits[0]))
          {
            throw new UnexpectedValueException("invalid range '$holiday'; the end is before the beginning.");
          }
        }

        self::$validHolidays[$year] = true;
      }
      catch (UnexpectedValueException $e)
      {
        self::$validHolidays[$year] = false;
        trigger_error('Check the config setting of $holidays: ' . $e->getMessage(), E_USER_WARNING);
      }
    }

    return self::$validHolidays[$year];
  }


  // Determines whether the date is a holiday, as defined
  // in the config variable $holidays.
  private function isHolidayConfig() : bool
  {
    global $holidays;

    $year = $this->format('Y');
    $iso_date = $this->format('Y-m-d');

    // Only need to check if a date is a holiday once, so store the answer in a
    // static property
    if (!isset(self::$isHoliday[$iso_date]))
    {
      self::$isHoliday[$iso_date] = false;
      if (!empty($holidays[$year]) && self::validateHolidays($year))
      {
        foreach ($holidays[$year] as $holiday)
        {
          $limits = explode('..', $holiday);

          if (count($limits) == 1)
          {
            // It's a single date of the form '2022-01-01'
            if ($iso_date == $limits[0])
            {
              self::$isHoliday[$iso_date] = true;
              break;
            }
          }
          elseif (count($limits) == 2)
          {
            // It's a range of the form '2022-07-01..2022-07-31'
            if (($iso_date >= $limits[0]) && ($iso_date <= $limits[1]))
            {
              self::$isHoliday[$iso_date] = true;
              break;
            }
          }
          else
          {
            trigger_error("Invalid holiday element '$holiday'");
          }
        }
      }
    }

    return self::$isHoliday[$iso_date];
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
