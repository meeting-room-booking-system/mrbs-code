<?php
declare(strict_types=1);
namespace MRBS;

use IntlCalendar;
use MRBS\ICalendar\RFC5545;
use UnexpectedValueException;

class DateTime extends \DateTime
{
  private static $isHoliday = array();
  private static $validHolidays = array();

  public const ISO8601_DATE = 'Y-m-d';
  private const HOLIDAY_RANGE_OPERATOR = '..';


  // Before PHP 8 a child of DateTime::createFromFormat() returned an instance of the
  // parent, rather than the child.  So we have to force createFromFormat() to return
  // an instance of the child.  See https://bugs.php.net/bug.php?id=79975 and also
  // https://stackoverflow.com/questions/5450197/make-datetimecreatefromformat-return-child-class-instead-of-parent
  // This method will no longer be necessary when the minimum PHP version is > 7.
  #[\ReturnTypeWillChange]
  public static function createFromFormat($format, $datetime, ?\DateTimeZone $timezone = null)
  {
    assert(version_compare(MRBS_MIN_PHP_VERSION, '8.0.0', '<'), "This method is now redundant.");

    $parent = parent::createFromFormat($format, $datetime, $timezone);

    if ($parent === false)
    {
      return false;
    }

    return new static($parent->format('Y-m-d\TH:i:s.u'), $parent->getTimezone());
  }


  // Returns the first day of the week (0 = Sunday) for a given timezone and locale.
  // If $timezone is null, the default timezone will be used.
  // If $locale is null, the default locale will be used.
  // The method relies on the IntlCalendar class.  If it doesn't exist, or there's an error,
  // the method assumes that the week starts on a Monday.
  public static function firstDayOfWeek(?string $timezone = null, ?string $locale = null) : int
  {
    global $icu_override;

    $default = 1; // Monday

    if (!class_exists('\\IntlCalendar'))
    {
      return $default;
    }

    $calendar = IntlCalendar::createInstance($timezone, $locale);
    if (!isset($calendar))
    {
      trigger_error("Could not create IntlCalendar for timezone '$timezone' and locale '$locale'", E_USER_WARNING);
      return $default;
    }

    // If we're overriding the ICU library then use that value
    if (isset($icu_override[$locale]['first_day_of_week']))
    {
      $first_day =  $icu_override[$locale]['first_day_of_week'];
      // Check that it's a valid day
      if (!in_array($first_day, array(
          IntlCalendar::DOW_SUNDAY,
          IntlCalendar::DOW_MONDAY,
          IntlCalendar::DOW_TUESDAY,
          IntlCalendar::DOW_WEDNESDAY,
          IntlCalendar::DOW_THURSDAY,
          IntlCalendar::DOW_FRIDAY,
          IntlCalendar::DOW_SATURDAY
        )))
      {
        throw new Exception('$icu_override[' . $locale . "]['first_day_of_week'] must be in the range [1..7]");
      }
    }
    // Otherwise just get the standard value from ICU
    else
    {
      $first_day = $calendar->getFirstDayOfWeek();
      if ($first_day === false) {
        trigger_error($calendar->getErrorMessage(), E_USER_WARNING);
        return $default;
      }
    }

    return $first_day - 1;  // IntlCalendar::DOW_SUNDAY = 1, so we need to subtract 1
  }


  // Tests whether this is the first day of the week in the locale.
  // If $locale is null, the default locale will be used.
  public function isFirstDayOfWeek(?string $locale = null) : bool
  {
    return ($this->getDayOfWeek() === self::firstDayOfWeek($this->getTimezone()->getName(), $locale));
  }


  // TODO: make $relative an object?
  // Sets the day to $relative, where relative is an RFC5545 relative day,
  // eg "-2SU".  Returns FALSE if the relative day doesn't exist in this
  // month, otherwise TRUE.
  public function setRelativeDay(string $relative) : bool
  {
    $clone = clone $this;

    // Get the ordinal number and the day of the week
    list('ordinal' => $ord, 'day' => $dow) = RFC5545::parseByday($relative);
    // Set the starting day of the month, either to the first or last day of
    // the month, depending on whether we are counting forwards or backwards.
    $clone->setDay(($ord > 0) ? 1 : (int) $clone->format('t'));
    // Advance/go back to the first day of the week that is required
    // TODO: this could be optimised slightly by calculating the exact number of days required
    while ($clone->format('w') != RFC5545::convertDayToOrd($dow))
    {
      $modifier = ($ord > 0) ? '+1 day' : '-1 day';
      $clone->modify($modifier);
    }
    // Advance/go back the required number of weeks
    if (abs($ord) > 1)
    {
      $modifier = (($ord > 0) ? '+' : '-') . ($ord - 1) . 'weeks';
      $clone->modify($modifier);
    }
    // See if we are still in the same month.  If not, then the relative day doesn't
    // exist in this month and return FALSE.  If so, then set this date to be the
    // clone's and return TRUE.
    if ($clone->getMonth() !== $this->getMonth())
    {
      return false;
    }
    $this->setTimestamp($clone->getTimestamp());
    return true;
  }


  // Sets the time to the start of the first slot of the day
  public function setStartFirstSlot() : self
  {
    global $morningstarts, $morningstarts_minutes, $enable_periods;

    if ($enable_periods)
    {
      return $this->setTime(12, 0);
    }

    return $this->setTime($morningstarts, $morningstarts_minutes);
  }


  // Sets the time to the start of the last slot of the day.
  // (Note that if the booking day extends beyond midnight, then this will
  // be on the next day.)
  public function setStartLastSlot() : self
  {
    global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes, $enable_periods, $periods;

    if ($enable_periods)
    {
      return $this->setTime(12, count($periods) - 1);
    }

    // Work out if $evening_ends is really on the next day
    if (hm_before(
      ['hours' => $eveningends, 'minutes' => $eveningends_minutes],
      ['hours' => $morningstarts, 'minutes' => $morningstarts_minutes])
    )
    {
      $this->modify('+1 day');
    }

    return $this->setTime($eveningends, $eveningends_minutes);
  }


  // Sets the time to the end of the last slot of the day.
  // (Note that if the booking day extends beyond midnight, then this will
  // be on the next day.)
  public function setEndLastSlot() : self
  {
    global $resolution;

    $this->setStartLastSlot();
    $this->modify("+$resolution seconds");

    return $this;
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

    $modifier = "$n months";
    $day = $this->format('j');
    $this->modify('first day of this month');
    $this->modify($modifier);
    $this->modify('+' . (min($day, $this->format('t')) - 1) . ' days');

    if (!$allow_hidden_days)
    {
      $this->findNearestUnhiddenDayInMonth();
    }
  }


  // Adds $n (which can be negative) years to this date, without overflowing
  // into the next month.  For example modifying 2024-02-29 by +1 year gives
  // 2025-02-28 rather than 2025-03-01.
  public function modifyYearsNoOverflow(int $n, bool $allow_hidden_days = false) : void
  {
    $this->modifyMonthsNoOverflow(12 * $n, $allow_hidden_days);
  }


  public function getHour() : int
  {
    return intval($this->format('G'));
  }


  /**
   * Get the day of the month without leading zeros
   */
  public function getDay() : int
  {
    return intval($this->format('j'));
  }


  public function getDayOfWeek() : int
  {
    return intval($this->format('w'));
  }


  public function getDaysInMonth() : int
  {
    return intval($this->format('t'));
  }


  /**
   * Get the numeric representation of a month, without leading zeros
   */
  public function getMonth() : int
  {
    return intval($this->format('n'));
  }


  /**
   * Get the full numeric representation of a year, at least 4 digits, with - for years BCE.
   */
  public function getYear() : int
  {
    return intval($this->format('Y'));
  }


  // Returns a date in ISO 8601 format ('yyyy-mm-dd')
  public function getISODate() : string
  {
    return $this->format(self::ISO8601_DATE);
  }


  // Set the day to $day
  public function setDay(int $day) : self
  {
    $date = getdate($this->getTimestamp());
    $this->setDate($date['year'], $date['mon'], $day);

    return $this;
  }


  // Sets the day to $day, but not past the end of the month
  public function setDayNoOverflow(int $day) : self
  {
    $this->setDay(min($day, (int) $this->format('t')));

    return $this;
  }


  // Winds the date back to the first $month that is at or before
  // this date.  Useful for aligning with financial or academic years.
  // If $month is 0, then no change is made.
  public function setMonthYearStart(int $month) : self
  {
    if ($month !== 0)
    {
      $this_month = $this->getMonth();
      $modification = $month - $this_month;
      if ($modification > 0)
      {
        $modification -= MONTHS_PER_YEAR;
      }
      $this->modifyMonthsNoOverflow($modification, true);
    }

    return $this;
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
      $unsigned_modifier = "$i days";
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
    $iso_date = $this->getISODate();

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


  public function isToday() : bool
  {
    $today = new DateTime();
    return ($this->getISODate() == $today->getISODate());
  }


  public function isWeekend() : bool
  {
    return is_weekend(intval($this->format('w')));
  }


  // Set the time to $s, where $s is the nominal number of
  // seconds after midnight, ignoring DST changes.
  public function setNominalSeconds(int $s) : self
  {
    $second = $s % 60;
    $s -= $second;
    $m = $s/60;
    $minute = $m % 60;
    $m -= $minute;
    $hour = $m/60;

    while ($hour > 24)
    {
      $this->modify('+1 day');
      $hour -= 24;
    }

    return $this->setTime($hour, $minute, $second);
  }
}
