<?php
declare(strict_types=1);
namespace MRBS;

class RepeatRule
{
  // Repeat types
  public const NONE = 0;
  public const DAILY = 1;
  public const WEEKLY = 2;
  public const MONTHLY = 3;
  public const YEARLY = 4;
  public const REPEAT_TYPES = [
    self::NONE,
    self::DAILY,
    self::WEEKLY,
    self::MONTHLY,
    self::YEARLY
  ];

  // Monthly repeat types
  public const MONTHLY_ABSOLUTE = 0;
  public const MONTHLY_RELATIVE = 1;
  public const MONTHLY_TYPES = [
    self::MONTHLY_ABSOLUTE,
    self::MONTHLY_RELATIVE
  ];

  private $days;  // An array of days for weekly repeats; 0 for Sunday, etc.
  private $end_date;  // The repeat end date.  A DateTime object
  private $interval;  // The repeat interval
  private $monthly_absolute;  // The absolute day of the month to repeat on for monthly repeats
  private $monthly_relative;  // The relative day of the month to repeat on for monthly repeats
  private $monthly_type;  // The monthly repeat type (Absolute or relative).  An int.
  private $type;  // The repeat type (NONE, DAILY, etc.).  An int.

  public function __construct()
  {
    $this->setDays([]);
  }


  public function getDays() : array
  {
    return $this->days;
  }

  public function getEndDate() : ?DateTime
  {
    return $this->end_date ?? null;
  }

  public function getInterval() : int
  {
    return $this->interval;
  }

  public function getMonthlyAbsolute() : ?int
  {
    return $this->monthly_absolute ?? null;
  }

  public function getMonthlyRelative() : ?string
  {
    return $this->monthly_relative ?? null;
  }

  public function getMonthlyType() : ?int
  {
    return $this->monthly_type ?? null;
  }

  public function getType() : int
  {
    return $this->type;
  }

  public function setDays(array $days) : void
  {
    foreach ($days as $day)
    {
      // Cast to int
      $this->days[] = (int) $day;
      // Check it's a valid day
      if (($day < 0) || ($day > 6))
      {
        throw new \InvalidArgumentException("Invalid day of the week '$day'");
      }
    }
  }

  public function setEndDate(?DateTime $end_date) : void
  {
    $this->days = $end_date;
  }

  public function setInterval(int $interval) : void
  {
    $this->interval = $interval;
  }

  public function setMonthlyAbsolute(?int $absolute) : void
  {
    $this->monthly_absolute = $absolute;
  }

  public function setMonthlyRelative(?string $relative) : void
  {
    $this->monthly_relative = $relative;
  }

  public function setMonthlyType(?int $monthly_type) : void
  {
    if (!in_array($monthly_type, self::MONTHLY_TYPES))
    {
      throw new \InvalidArgumentException("Invalid monthly type '$monthly_type'");
    }
    $this->monthly_type = $monthly_type;
  }

  public function setType(int $type) : void
  {
    if (!in_array($type, self::REPEAT_TYPES))
    {
      throw new \InvalidArgumentException("Invalid repeat type '$type'");
    }
    $this->type = $type;
  }


  // Returns an array of start times for the entries in this series given a start time
  // for the beginning of the series.  Optionally limited to $limit entries.
  public function getRepeatStartTimes(int $start_time, ?int $limit) : array
  {
    $entries = array();

    $start_date = new DateTime();
    $start_date->setTimestamp($start_time);

    $date = getdate($start_time);

    $sec         = $date['seconds'];
    $min         = $date['minutes'];
    $hour        = $date['hours'];
    $day         = $date['mday'];
    $month       = $date['mon'];
    $year        = $date['year'];
    $start_day   = $date['wday'];
    $start_dom   = $day;  // the starting day of the month
    $start_month = $month;

    // Make sure that the first date is a member of the series
    switch($this->getType())
    {
      case self::WEEKLY:
        $repeat_days = $this->getDays();
        if (count($repeat_days) == 0)
        {
          throw new Exception("No weekly repeat days specified in repeat rule");
        }
        while (!in_array($start_date->format('w'), $repeat_days))
        {
          // The hour will be preserved across DST transitions
          $start_date->modify('+1 day');
        }
        break;
      case self::MONTHLY:
        if ($this->getMonthlyType() == self::MONTHLY_ABSOLUTE)
        {
          if ($start_date->getDay() != $this->getMonthlyAbsolute())
          {
            if ($start_date->getDay() > $this->getMonthlyAbsolute())
            {
              $start_date->modify('+1 month');
            }
            $start_date->setDayNoOverflow($this->getMonthlyAbsolute());
          }
        }
        // TODO: continue converting from here !!!!!!!!!!!!!!!!!!!!!!!!!!!!
        // TODO: implement convertRelativeToAbsolute (base on byday_to_day)
        else // must be relative
        {
          // Advance to a month that has this relative date. For example, not
          // every month will have a '5SU' (fifth Sunday)
          while (false === $start_date->setRelativeDay($this->getMonthlyRelative()))
          {
            $start_date->modify('+1 month');
          }
          // Set the day (possibly having to advance it)
        }

        elseif (isset($rep_details['month_relative']))
        {
          $day = byday_to_day($year, $month, $rep_details['month_relative']);
          while (($day === FALSE) || (($day < $start_dom) && ($month == $start_month)))
          {
            $month++;
            $day = byday_to_day($year, $month, $rep_details['month_relative']);
          }
        }
        else
        {
          trigger_error("No monthly repeat type, E_USER_WARNING");
        }
        trimToEndOfMonth($month, $day, $year);
        break;
      default:
        break;
    }

    for ($i = 0; $i < $n; $i++)
    {
      $time = mktime($hour, $min, $sec, $month, $day, $year);

      if ($time > $rep_end_time)
      {
        break;
      }

      $entries[] = $time;

      switch($rep_details['rep_type'])
      {
        case REP_DAILY:
          $day = $day + $rep_details['rep_interval'];
          break;

        case REP_WEEKLY:
          $j = $cur_day = date("w", $time);
          // Skip over days of the week which are not enabled:
          do
          {
            $day++;
            $j = ($j + 1) % DAYS_PER_WEEK;
            // If we've got back to the beginning of the week, then skip
            // over the weeks we've got to miss out (eg miss out one week
            // if we're repeating every two weeks)
            if ($j == $start_day)
            {
              $day += DAYS_PER_WEEK * ($rep_details['rep_interval'] - 1);
            }
          }
          while (($j != $cur_day) && !$rep_details['rep_opt'][$j]);
          break;

        case REP_MONTHLY:
          do
          {
            $month = $month + $rep_details['rep_interval'];
            // Get the day of the month back to where it should be (in case we
            // decremented it to make it a valid date last time round)
            $day = $rep_details['month_absolute'] ?? byday_to_day($year, $month, $rep_details['month_relative']);
          } while ($day === false);
          trimToEndOfMonth($month, $day, $year);
          break;

        case REP_YEARLY:
          // Get the day of the month back to where it should be (in case we
          // decremented it to make it a valid date last time round)
          $day = $start_dom;
          $year = $year + $rep_details['rep_interval'];
          trimToEndOfMonth($month, $day, $year);
          break;

        // Unknown repeat option
        default:
          trigger_error("Unknown repeat type, E_USER_NOTICE");
          break;
      }
    }

    return $entries;
  }


  public function

}
