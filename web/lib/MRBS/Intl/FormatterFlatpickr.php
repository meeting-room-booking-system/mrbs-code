<?php
declare(strict_types=1);
namespace MRBS\Intl;

class FormatterFlatpickr implements Formatter
{
  // See https://flatpickr.js.org/formatting/
  private const FORMATTING_TOKENS = [
    'd', 'D', 'l', 'j', 'J', 'w', 'W', 'F', 'm', 'n', 'M',
    'U', 'y', 'Y', 'Z', 'H', 'h', 'G', 'i', 'S', 's', 'K'];

  // Convert an ICU pattern token into the nearest equivalent token.
  // Throws an exception if the token can't be converted.
  public function convert(string $token) : string
  {
    switch ($token) {
      // AM or PM
      case 'a':       // PM [abbrev]
      case 'aa':      // PM [abbrev]
      case 'aaa':     // PM [abbrev]
      case 'aaaa':    // PM [wide]
      case 'aaaaa':   // p
        // am, pm, noon, midnight
      case 'b':       // mid.
      case 'bb':      // mid.
      case 'bbb':     // mid.
      case 'bbbb':    // midnight
      case 'bbbbb':   // md
        // flexible day periods
      case 'B':       // at night [abbrev]
      case 'BB':      // at night [abbrev]
      case 'BBB':     // at night [abbrev]
      case 'BBBB':    // at night [wide]
      case 'BBBBB':   // at night [narrow]
        $format = 'K';    // AM/PM, eg	AM or PM
        break;

      // stand-alone local day of week
      case 'cccc':    // Tuesday
        // day of week
      case 'EEEE':    // Tuesday
        // local day of week
      case 'eeee':    // Tuesday
        $format = 'l';  // A full textual representation of the day, eg Sunday through Saturday
        break;

      // stand-alone local day of week
      case 'ccc':     // Tue
      case 'ccccc':   // T
      case 'cccccc':  // Tu
        // day of week
      case 'E':       // Tue
      case 'EE':      // Tue
      case 'EEE':     // Tue
      case 'EEEEE':   // T
      case 'EEEEEE':  // Tu
        // local day of week
      case 'eee':     // Tue
      case 'eeeee':   // T
      case 'eeeeee':  // Tu
        $format = 'D';   // A textual representation of a day, eg	Mon through Sun
        break;

      // day in month
      case 'd':       // 2
        $format = 'j';   // Day of the month without leading zeros, eg1 to 31
        break;

      // day in month
      case 'dd':      // 02
        $format = 'd';   // Day of the month, 2 digits with leading zeros, eg	01 to 31
        break;

      // hour in day (0~23)
      case 'H':       // 0
      // hour in day (0~23)
      case 'HH':      // 00
        $format = 'H';   // Hours (24 hours), eg	00 to 23
        break;

      // hour in am/pm (1~12)
      case 'h':       // 7
        $format = 'h';   // Hours	1 to 12
        break;

      // hour in am/pm (1~12)
      case 'hh':      // 07
        $format = 'G';   // Hours, 2 digits with leading zeros	1 to 12
        break;

      // stand-alone month in year
      case 'L':       // 9
        // month in year
      case 'M':       // 9
        $format = 'n';   // Numeric representation of a month, without leading zeros	1 through 12
        break;

      // stand-alone month in year
      case 'LL':      // 09
        // month in year
      case 'MM':      // 09
        $format = 'm';   // Numeric representation of a month, with leading zero	01 through 12
        break;

      // stand-alone month in year
      case 'LLL':     // Sep
        // month in year
      case 'MMM':     // Sep
        $format = 'M';   // A short textual representation of a month	Jan through Dec
        break;

      // stand-alone month in year
      case 'LLLL':    // September
        // month in year
      case 'MMMM':    // September
        $format = 'F';   // A full textual representation of a month	January through December
        break;

      // minute in hour
      case 'm':       // 4
        $format = 'i';   // Minutes	00 to 59
        break;

      // minute in hour
      case 'mm':      // 04
        $format = 'i';   // Minutes	00 to 59
        break;

      // second in minute
      case 's':       // 5
        $format = 's';   // Seconds	0, 1 to 59
        break;

      // second in minute
      case 'ss':      // 05
        $format = 'S';   // Seconds, 2 digits	00 to 59
        break;

      // week of year
      // The ICU documentation isn't very clear what is meant by "week of year", but it seems to be locale
      // dependent. In many locales it is the ISO week number, but in some locales it isn't.  It (partly?)
      // depends on the locale's first day of the week, which can be got from IntlCalendar::getFirstDayOfWeek().
      case 'w':       // 7
      case 'ww':      // 07
        $format = 'W';   // Numeric representation of the week	0 (first week of the year) through 52 (last week of the year)
        break;

      // year
      case 'y':       // 1996
      case 'yyyy':    // 1996
        $format = 'Y';    // A full numeric representation of a year, 4 digits, eg 1999 or 2003
        break;

      // year
      case 'yy':      // 96
        $format = 'y';   // A two digit representation of a year	99 or 03
        break;

      default:
        throw new \MRBS\Exception("Could not convert '$token'");
        break;
    }

    return $format;
  }

  public function escape(string $char): string
  {
    if (in_array($char, self::FORMATTING_TOKENS))
    {
      return '\\\\' . $char;
    }

    return $char;
  }
}
