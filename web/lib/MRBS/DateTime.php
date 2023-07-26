<?php
declare(strict_types=1);
namespace MRBS;

use UnexpectedValueException;

class DateTime extends \DateTime
{
  private static $isHoliday = array();
  private static $validHolidays = array();

  private const HOLIDAY_RANGE_OPERATOR = '..';


  public function convertToAbsolute(string $relative) : bool
  {
    $clone = clone $this;

    // Get the ordinal number and the day of the week
    list($ord, $dow) = byday_split($relative);
    // Get the starting day of the month
    //TODO: Set the date and then advance/retreat
    $start_dom = ($ord > 0) ? 1 : $clone->format('t');
    // Get the starting day of the week
    $start_dow = date('w', mktime(0, 0, 0, $month, $start_dom, $year));


  }


  // Adds $n (which can be negative) months to this date, without overflowing
  // into the next month.  For example modifying 2023-01-31 by +1 month gives
  // 2023-02-28 rather than 2023-03-03.
  public function modifyMonthsNoOverflow(int $n, bool $allow_hidden_days = false) : void
  {
    if ($n == 0)
    {
      return;
    }

    $modifier = (abs($n) == 1) ? "$n month" : "$n months";
    $day = $this->format('j');
    $this->modify('first day of this month');
    $this->modify($modifier);
    $this->modify('+' . (min($day, $this->format('t')) - 1) . ' days');

    if (!$allow_hidden_days)
    {
      $this->findNearestUnhiddenDayInMonth();
    }
  }


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


  // Set the day to $day
  public function setDay(int $day) : void
  {
    $date = getdate($this->getTimestamp());
    $this->setDate($date['year'], $date['mon'], $day);
  }


  // Sets the day to $day, but not past the
  public function setDayNoOverflow(int $day) : void
  {
    $this->setDay(min($day, $this->format('t')));
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


  // Move the date to the nearest unhidden day in this month.
  private function findNearestUnhiddenDayInMonth() : void
  {
    // Trivial case: it's already unhidden
    if (!$this->isHiddenDay())
    {
      return;
    }

    // Keep track of whether we've already tried looking beyond the ends
    // of the month, to avoid doing it again unnecessarily.
    $end_of_month_reached = false;
    $start_of_month_reached =false;

    // Create a series of modifiers going progressively +1, -1, +2, -2 ..
    // +6, -6 days away from this day and test each one to check that the
    // modified day is both in the same month as the original date and is
    // not hidden.
    for ($i=1; $i<DAYS_PER_WEEK; $i++)
    {
      $unsigned_modifier = "$i day";
      if ($i > 1)
      {
        $unsigned_modifier .= 's';  // plural
      }
      foreach (['+', '-'] as $sign)
      {
        // Check whether we've already been past the end/start of the
        // month, and if so try the next modifier.
        if ((($sign == '+') && $end_of_month_reached) ||
            (($sign == '-') && $start_of_month_reached))
        {
          continue;
        }
        // Otherwise, create a clone and test it with this modifier
        $clone = clone $this;
        $modifier = $sign . $unsigned_modifier;
        $clone->modify($modifier);
        if ($clone->getMonth() == $this->getMonth())
        {
          if (!$clone->isHiddenDay())
          {
            // Success! The clone is in the same month and not hidden,
            // so apply the same modifier to the original.
            $this->modify($modifier);
            return;
          }
        }
        else
        {
          if ($sign == '+')
          {
            $end_of_month_reached = true;
          }
          else
          {
            $start_of_month_reached = true;
          }
        }
        unset($clone);
      }
    }
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


  // Determines whether a given date is supposed to be hidden in the display
  public function isHiddenDay() : bool
  {
    global $hidden_days;

    return (isset($hidden_days) && in_array($this->format('w'), $hidden_days));
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
