<?php
namespace MRBS\Intl;

// A class provides a basic emulation of PHP's IntlDateFormatter class.
//
// The emulation uses the deprecated function strftime() and is only necessary for older
// PHP systems where the Intl extension isn't available.  Eventually the emulation can be
// dispensed with.
//
// Note that some servers have out of date versions of the ICU library that can't be updated
// easily.  In those cases better results can sometimes be achieved by using strftime() and
// this can be forced by explicitly using this class.

use MRBS\Exception;
use MRBS\System;
use function MRBS\convert_to_BCP47;
use function MRBS\get_mrbs_locale;
use function MRBS\set_mrbs_locale;
use function MRBS\utf8_strlen;

// We need to check that the 'intl' extension is loaded because earlier versions of
// MRBS had the IntlDateFormatter emulation class at the top level in lib.  If users
// have upgraded by just overwriting files without deleting that file, then it will
// be picked up by the class_exists() test and used instead of the more up-to-date
// emulation below.

// Note that there is a polyfill for IntlDateFormatter available at
// https://github.com/symfony/polyfill-intl-icu, but it is limited to the 'en' locale.
// There are also backwards compatibility versions of strftime() available, but
// IntlDateFormatter is a more powerful solution.
class IntlDateFormatter
{
  const FULL = 0;
  const LONG = 1;
  const MEDIUM = 2;
  const SHORT = 3;
  const NONE = -1;
  const RELATIVE_FULL = 128; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_LONG = 129; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_MEDIUM = 130; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_SHORT = 131; // Available as of PHP 8.0.0, for dateType only
  const GREGORIAN = 1;
  const TRADITIONAL = 0;


  private const TYPE_NAMES = array(
    self::FULL => 'full',
    self::LONG => 'long',
    self::MEDIUM => 'medium',
    self::SHORT => 'short',
    self::NONE => 'none'
  );

  private const DEFAULT_LOCALE = 'en';
  private const QUOTE_CHAR = "'";

  private $locale;
  private $dateType;
  private $timeType;
  private $timezone;
  private $calendar;
  private $pattern;

  public function __construct(
    ?string $locale,
    int     $dateType = self::FULL,
    int     $timeType = self::FULL,
            $timezone = null,
            $calendar = null,
    ?string $pattern = null)
  {
    if (!function_exists('strftime'))
    {
      throw new Exception("Neither the IntlDateFormatter class nor the strftime() function exist on this server");
    }
    $this->locale = $locale;
    $this->dateType = $dateType;
    $this->timeType = $timeType;
    $this->timezone = $timezone;
    $this->calendar = $calendar ?? self::GREGORIAN;

    if (!isset($pattern)) {
      $file = MRBS_ROOT . "/intl/types/" .
        self::TYPE_NAMES[$this->dateType] . "_" . self::TYPE_NAMES[$this->timeType] . ".ini";
      if (is_readable($file)) {
        $patterns = parse_ini_file($file);
        if (!empty($patterns)) {
          $pattern = $patterns[convert_to_BCP47($this->locale)] ?? $patterns[self::DEFAULT_LOCALE] ?? null;
        }
      }
    }

    if (!isset($pattern)) {
      throw new Exception("Could not get pattern");
    }

    $this->setPattern($pattern);
  }


  public function format($datetime)
  {
    // $datetime can be many types
    // TODO: Handle the remaining possible types
    if ($datetime instanceof DateTimeInterface) {
      $timestamp = $datetime->getTimestamp();
    }
    else {
      $timestamp = (int)$datetime;
    }

    // Parse the pattern
    // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/
    // "Note: Any characters in the pattern that are not in the ranges of [‘a’..’z’] and
    // [‘A’..’Z’] will be treated as quoted text. For instance, characters like ':', '.',
    // ' ', '#' and '@' will appear in the resulting time text even they are not enclosed
    // within single quotes. The single quote is used to ‘escape’ letters. Two single
    // quotes in a row, whether inside or outside a quoted sequence, represent a ‘real’
    // single quote."
    $format = '';
    $token_char = null;
    $in_quotes = false;
    // Split the string into an array of multibyte characters
    $chars = preg_split("//u", $this->pattern, 0, PREG_SPLIT_NO_EMPTY);

    while (null !== ($char = array_shift($chars))) {
      $is_token_char = !$in_quotes && preg_match("/^[a-z]$/i", $char);
      if ($is_token_char) {
        // The start of a token
        if (!isset($token_char)) {
          $token_char = $char;
          $token = $char;
        }
        // The continuation of a token
        elseif ($char === $token_char) {
          $token .= $char;
        }
      }
      // The end of a token
      if (isset($token_char) && (($char !== $token_char) || empty($chars))) {
        $converted_token = self::convertFormatToken($token);
        if ($converted_token === false) {
          throw new \MRBS\Exception("Could not convert '$token'");
        }
        $format .= $converted_token;
        if ($is_token_char) {
          // And the start of a new token
          $token_char = $char;
          $token = $char;
        }
        else {
          $token_char = null;
        }
      }

      // Quoted text
      if (!$is_token_char) {
        // If it's not a quote just add the character to the format
        if ($char !== self::QUOTE_CHAR) {
          $format .= self::escapeForStrftime($char);
        }
        // Otherwise we have to work out whether the quote is the start or end of a
        // quoted sequence, or part of an escaped quote
        else {
          // Get the next character
          $char = array_shift($chars);
          if (isset($char)) {
            // If it is a quote then it's an escaped quote and add it to the format
            if ($char === self::QUOTE_CHAR) {
              $format .= self::escapeForStrftime($char);
            }
            // Otherwise it's either the start or end of a quoted section.
            // Toggle $in_quotes and add the character to the format if we're in quotes,
            // or else replace it so that it gets handled properly next time round.
            else {
              $in_quotes = !$in_quotes;
              if ($in_quotes) {
                $format .= self::escapeForStrftime($char);
              }
              else {
                array_unshift($chars, $char);
              }
            }
          }
        }
      }
    }

    return $this->strftimePlus($format, $timestamp);
  }


  //Get the calendar type used for the IntlDateFormatter
  public function getCalendar()
  {
    return $this->calendar ?? false;
  }


  // Get the datetype used for the IntlDateFormatter
  public function getDateType()
  {
    return $this->dateType ?? false;
  }


  // Get the locale used by formatter
  public function getLocale(int $type=Locale::ACTUAL_LOCALE)
  {
    switch ($type)
    {
      // TODO: Do something with $type, though it's not exactly clear what the difference is
      // TODO: between the two types.  See also https://www.php.net/manual/en/collator.getlocale.php
      case LOCALE::ACTUAL_LOCALE:
      case LOCALE::VALID_LOCALE:
        return $this->locale ?? false;
        break;
      default:
        return false;
        break;
    }
  }


  // Get the pattern used for the IntlDateFormatter
  public function getPattern()
  {
    return $this->pattern ?? false;
  }


  // Get the timetype used for the IntlDateFormatter
  public function getTimeType()
  {
    return $this->timeType ?? false;
  }

  // The standard PHP version can also return false
  public function setPattern(string $pattern): bool
  {
    $this->pattern = $pattern;
    return true;
  }


  // Converts an IntlDateFormatter token to a strftime token
  private static function convertFormatToken(string $token)
  {
    switch ($token) {
      // AM or PM
      case 'a':       // PM [abbrev]
      case 'aa':      // PM [abbrev]
      case 'aaa':     // PM [abbrev]
      case 'aaaa':    // PM [wide]
      case 'aaaaa':   // p
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


  private static function escapeForStrftime(string $char): string
  {
    switch ($char) {
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


// Format a local time/date according to locale settings, returning the
// result as a UTF-8 string.  This function is based on strftime()
// $time can be an int or a float (union type declarations not supported until PHP 8.0)
// $locale can either be a string or an array of locales.  If $locale
// is not set then the current locale is used.
//
// This method extends the standard PHP strftime() function and adds extra formats:
//
//  %f  Numeric representation of the month 	      1 (for January) through 12 (for December)
//      without leading zeroes.  Won't
//      necessarily work in locales that don't
//      use [0..9] for the month.
//
//  %i  One/two digit day of the month, with no     1 to 31
//      leading space
//
//  %o  Hour in 12-hour format, with no space       1 through 12
//      preceding single digits
//
//  %q  Minute in the hour, with no leading zero    4
//
//  %v  Seconds, with no leading zero
//
//  %E  Day of year, with no leading zeroes
  private function strftimePlus(string $format, int $timestamp): string
  {
    $server_os = System::getServerOS();

    // Set the temporary locale.  Note that $this->locale could be an array of locales,
    // so we need to find out which locale actually worked.
    if (!empty($this->locale)) {
      $old_locale = setlocale(LC_TIME, '0');
      $new_locale = setlocale(LC_TIME, System::getOSlocale($this->locale));
    }
    elseif ($server_os == "windows") {
      // If we are running Windows we have to set the locale again in case another script
      // running in the same process has changed the locale since we first set it.  See the
      // warning on the PHP manual page for setlocale():
      //
      // "The locale information is maintained per process, not per thread. If you are
      // running PHP on a multithreaded server API like IIS or Apache on Windows, you may
      // experience sudden changes in locale settings while a script is running, though
      // the script itself never called setlocale(). This happens due to other scripts
      // running in different threads of the same process at the same time, changing the
      // process-wide locale using setlocale()."
      $new_locale = get_mrbs_locale();
      set_mrbs_locale($new_locale);
    }
    else {
      $new_locale = null;
    }

    $result = self::doStrftimePlus($format, $timestamp, $new_locale);

    // Restore the original locale
    if (!empty($this->locale)) {
      setlocale(LC_TIME, $old_locale);
    }

    return $result;
  }


  // Wrapper for strftime()
  private static function doStrftime(string $format, ?int $timestamp = null)
  {
    // Temporarily suppress deprecation errors so that we are not flooded with them.
    // We have a single message in init.inc.
    $error_level = error_reporting();
    error_reporting($error_level & ~E_DEPRECATED);
    $result = strftime($format, $timestamp);
    error_reporting($error_level);

    return $result;
  }


  private static function doStrftimePlus(string $format, int $timestamp, ?string $locale): string
  {
    $server_os = System::getServerOS();

    if ($server_os == "windows") {
      // Some formats not supported on Windows.   Replace with suitable alternatives
      $format = str_replace("%R", "%H:%M", $format);
      $format = str_replace("%P", "%p", $format);
      $format = str_replace("%l", "%I", $format);
      $format = str_replace("%e", "%#d", $format);
    }

    // %p doesn't actually work in some locales, we have to patch it up ourselves
    if (preg_match('/%p/', $format)) {
      $ampm = self::doStrftime('%p', $timestamp);
      if ($ampm == '') {
        $ampm = date('a', $timestamp);
      }

      $format = preg_replace('/%p/', $ampm, $format);
    }

    $result = '';

    // Split the format into individual tokens so that we can process our extensions
    $tokens = self::parseStrftimeFormat($format);

    foreach ($tokens as $token) {
      if (utf8_strlen($token) === 1) {
        $result .= $token;
      }
      else {
        switch ($token) {
          case '%E':
            // We want the day of the year without leading zeroes.
            $formatted = self::doStrftimePlus('%j', $timestamp, $locale);
            $formatted = ltrim($formatted, '0');
            break;
          case '%f':
            // We want a month number without leading zeroes.  We can't use date('n', $time)
            // because date will return an English answer with a month made up of the characters
            // [0..9] which won't be correct for all locales.
            $formatted = self::doStrftimePlus('%m', $timestamp, $locale);
            $formatted = ($formatted === '00') ? '0' : ltrim($formatted, '0');
            break;
          case '%i':
            $formatted = ltrim(self::doStrftimePlus('%e', $timestamp, $locale));
            break;
          case '%J':
            // We want the week of the year without leading zeroes.
            $formatted = self::doStrftimePlus('%V', $timestamp, $locale);
            $formatted = ltrim($formatted, '0');
            break;
          case '%o':
            $formatted = ltrim(self::doStrftimePlus('%l', $timestamp, $locale));
            break;
          case '%q':
            // We want a minute without leading zeroes.
            $formatted = self::doStrftimePlus('%M', $timestamp, $locale);
            $formatted = ($formatted === '00') ? '0' : ltrim($formatted, '0');
            break;
          case '%v':
            // We want seconds without leading zeroes.
            $formatted = self::doStrftimePlus('%S', $timestamp, $locale);
            $formatted = ($formatted === '00') ? '0' : ltrim($formatted, '0');
            break;
          default:
            $formatted = self::doStrftime($token, $timestamp);
            break;
        }
        $result .= System::utf8ConvertFromLocale($formatted, $locale);
      }
    }

    return $result;
  }


  // Parses a strftime format into an array of strings, which will either be two or three-character
  // formats or one-character text strings.
  private static function parseStrftimeFormat(string $format): array
  {
    $result = array();

    // Split the format into an array of multibyte characters
    $chars = preg_split("//u", $format, 0, PREG_SPLIT_NO_EMPTY);

    while (null !== ($char = array_shift($chars))) {
      if ($char !== '%') {
        // It's ordinary text
        $result[] = $char;
      }
      else {
        // Get the next character which will either be a conversion specifier or an escaped character
        $char = array_shift($chars);
        switch ($char) {
          case null:
            throw new Exception("Invalid format '$format'");
            break;
          case 'n':
            $result[] = "\n";
            break;
          case 't':
            $result[] = "\t";
            break;
          case '%':
            $result[] = "%";
            break;
          case '#':
            // This covers the case of '%#d' on Windows
            $char = array_shift($chars);
            if (!isset($char)) {
              throw new Exception("Invalid format '$format'");
            }
            else {
              $result [] = "%#$char";
            }
            break;
          default:
            $result [] = "%$char";
            break;
        }
      }
    }

    return $result;
  }

}


