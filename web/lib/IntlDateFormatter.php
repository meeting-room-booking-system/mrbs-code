<?php

// A basic emulation of PHP's IntlDateFormatter class.  This will only be loaded if the
// standard PHP class does not exist.

class IntlDateFormatter
{
  const FULL    = 0;
  const LONG    = 1;
  const MEDIUM  = 2;
  const SHORT   = 3;
  const NONE    = -1;
  const RELATIVE_FULL   = 128; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_LONG   = 129; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_MEDIUM = 130; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_SHORT  = 131; // Available as of PHP 8.0.0, for dateType only
  const GREGORIAN   = 1;
  const TRADITIONAL = 0;

  private $locale;
  private $dateType;
  private $timeType;
  private $timezone;
  private $calendar;
  private $pattern;

  public function __construct(
    ?string $locale,
    int $dateType = self::FULL,
    int $timeType = self::FULL,
    $timezone = null,
    $calendar = null,
    ?string $pattern = null)
  {
    $this->$locale = $locale;
    $this->dateType = $dateType;
    $this->timeType = $timeType;
    $this->timezone = $timezone;
    $this->calendar = $calendar ?? self::GREGORIAN;
    $this->pattern = $pattern;
  }


  public function format($datetime)
  {
    // $datetime can be many types
    // TODO: Handle the remaining possible types
    if ($datetime instanceof DateTimeInterface)
    {
      $t = $datetime->getTimestamp();
    }
    else
    {
      $t = $datetime;
    }

    // Parse the pattern
    $format = '';
    $token_char = null;
    $chars = preg_split("//u", $this->pattern, 0, PREG_SPLIT_NO_EMPTY);

    while (null !== ($char = array_shift($chars)))
    {
      $is_token_char = preg_match("/^[a-z]$/i", $char);
      if ($is_token_char)
      {
        // The start of a token
        if (!isset($token_char))
        {
          $token_char = $char;
          $token = $char;
        }
        // The continuation of a token
        elseif ($char === $token_char)
        {
          $token .= $char;
        }
      }
      // The end of a token
      if (($char !== $token_char) || empty($chars))
      {
        $converted_token = self::convertFormatToken($token);
        if ($converted_token === false)
        {
          throw new \MRBS\Exception("Could not convert '$token'");
        }
        $format .= $converted_token;
        $token_char = null;
      }
      // Quoted text
      if (!$is_token_char)
      {
        $format .= $char;
      }
      // TODO: handle single quotes
    }
    
    return \MRBS\date_formatter_strftime($format, $t, $this->locale);
  }


  // Converts an IntlDateFormatter token to a strftime token
  private static function convertFormatToken(string $token)
  {
    switch ($token)
    {
      // stand-alone local day of week
      case 'cccc':     // Tuesday
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
        $format = '%e';   // Day of the month, with a space preceding single digits, eg 1 to 31
        // Not implemented as described on Windows. MRBS compensates.
        break;

      // day in month
      case 'dd':      // 02
        $format = '%d';   // Two-digit day of the month (with leading zeros), eg 01 to 31
        break;

      // month in year
      case 'MMMM':    // September
        $format = '%B';   // Full month name, based on the locale, eg January through December
        break;

      // month in year
      case 'y':       // 1996
      case 'yyyy':    // 1996
        $format = '%Y';   // Four digit representation for the year, eg 2038
        break;

      // month in year
      case 'yy':      // 96
        $format = '%y';   // Two digit representation of the year, eg 09 for 2009, 79 for 1979
        break;

      default:
        $format = false;
        break;
    }

    return $format;
  }


}
