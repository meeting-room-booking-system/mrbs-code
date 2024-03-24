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

use DateTimeInterface;
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
    $this->timezone = $timezone ?? date_default_timezone_get();
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
    if ($datetime instanceof DateTimeInterface)
    {
      $timestamp = $datetime->getTimestamp();
    }
    else
    {
      $timestamp = (int)$datetime;
    }

    $converter = new IntlDatePatternConverter(new FormatterStrftime());

    return $this->strftimePlus($converter->convert($this->pattern), $timestamp);
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


