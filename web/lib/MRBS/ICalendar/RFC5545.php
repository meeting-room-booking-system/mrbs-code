<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class RFC5545
{
  // An array which can be used to map day of the week numbers (0..6)
  // onto days of the week as defined in RFC 5545
  const DAYS = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];

  // Convert an RFC 5545 day to an ordinal number representing the day of the week,
  // eg "MO" returns "1"
  public static function convertDayToOrd($day) : int
  {
    $tmp = array_keys(self::DAYS, $day);
    if (count($tmp) === 0)
    {
      throw new \InvalidArgumentException("Invalid day '$day'");
    }
    return $tmp[0];
  }
}
