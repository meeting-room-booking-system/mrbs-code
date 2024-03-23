<?php
declare(strict_types=1);
namespace MRBS\Intl;

class FormatterStrftime implements Formatter
{

  // Convert an ICU pattern token into the nearest equivalent token.
  // Returns FALSE if there is no equivalent.
  public function convert(string $token)
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
        $format = '%P';  // lower-case 'am' or 'pm' based on the given time
        break;

      // stand-alone local day of week
      case 'cccc':    // Tuesday
        // day of week
      case 'EEEE':    // Tuesday
        // local day of week
      case 'eeee':    // Tuesday
        $format = '%A';  // A full textual representation of the day, eg Sunday through Saturday
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
        $format = '%a';   // An abbreviated textual representation of the day, eg Sun through Sat
        break;

      // day in month
      case 'd':       // 2
        $format = '%i';   // One/two digit day of the month, eg 1 to 31
        break;

      // day in month
      case 'dd':      // 02
        $format = '%d';   // Two-digit day of the month (with leading zeros), eg 01 to 31
        break;

      // day of year
      case 'D':       // 189
        $format = '%E';   // Day of the year without leading zeroes
        break;

      // hour in day (0~23)
      case 'H':       // 0
        $format = '%k';   // Hour in 24-hour format, with a space preceding single digits, eg 0 through 23
        break;

      // hour in day (0~23)
      case 'HH':      // 00
        $format = '%H';   // Two digit representation of the hour in 24-hour format, eg 00 through 23
        break;

      // hour in am/pm (1~12)
      case 'h':       // 7
        $format = '%o';   // Hour in 12-hour format, with no space preceding single digits
        break;

      // hour in am/pm (1~12)
      case 'hh':      // 07
        $format = '%I';   // Two digit representation of the hour in 12-hour format, eg 01 through 12
        break;

      // stand-alone month in year
      case 'L':       // 9
        // month in year
      case 'M':       // 9
        $format = '%f';   // One/two digit representation of the month, eg 1 (for January) through 12 (for December)
        break;

      // stand-alone month in year
      case 'LL':      // 09
        // month in year
      case 'MM':      // 09
        $format = '%m';   // Two digit representation of the month, eg 01 (for January) through 12 (for December)
        break;

      // stand-alone month in year
      case 'LLL':     // Sep
        // month in year
      case 'MMM':     // Sep
        $format = '%b';   // Abbreviated month name, based on the locale, eg Jan through Dec
        break;

      // stand-alone month in year
      case 'LLLL':    // September
        // month in year
      case 'MMMM':    // September
        $format = '%B';   // Full month name, based on the locale, eg January through December
        break;

      // minute in hour
      case 'm':       // 4
        $format = '%q';   // Minute in the hour, with no leading zero
        break;

      // minute in hour
      case 'mm':      // 04
        $format = '%M';   // Minute in the hour, with leading zeroes
        break;

      // second in minute
      case 's':       // 5
        $format = '%v';   // Seconds, with no leading zeroes
        break;

      // second in minute
      case 'ss':      // 05
        $format = '%S';   // Two digit representation of the second, eg 00 through 59
        break;

      // week of year
      // The ICU documentation isn't very clear what is meant by "week of year", but it seems to be locale
      // dependent. In many locales it is the ISO week number, but in some locales it isn't.  It (partly?)
      // depends on the locale's first day of the week, which can be got from IntlCalendar::getFirstDayOfWeek().
      case 'w':       // 7
        $format = '%J';   // ISO-8601:1988 week number of the given year without leading zeroes, eg 1 through 53
        break;

      case 'ww':      // 07
        $format = '%V';   // ISO-8601:1988 week number of the given year, eg 01 through 53
        break;

      // year
      case 'y':       // 1996
      case 'yyyy':    // 1996
        $format = '%Y';   // Four digit representation for the year, eg 2038
        break;

      // year
      case 'yy':      // 96
        $format = '%y';   // Two digit representation of the year, eg 09 for 2009, 79 for 1979
        break;

      // Time Zone: specific non-location
      case 'z':       // PDT
      case 'zz':      // PDT
      case 'zzz':     // PDT
      case 'zzzz':    // Pacific Daylight Time
        $format = '%Z';   // The time zone abbreviation, eg EST for Eastern Time
        break;            // Windows: The %z and %Z modifiers both return the time zone name instead of the offset or abbreviation

      default:
        $format = false;
        break;
    }

    return $format;
  }


  public function escape(string $char): string
  {
    switch ($char)
    {
      case "\n":
        return '%n';
        break;
      case "\t":
        return '%t';
        break;
      case "%":
        return '%%';
        break;
      default:
        return $char;
        break;
    }
  }
}
