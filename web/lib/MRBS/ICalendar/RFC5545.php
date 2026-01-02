<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use DateTimeZone;
use MRBS\DateTime;
use function MRBS\get_vocab;

class RFC5545
{
  public const DATETIME_FORMAT = 'Ymd\THis';  // Format for expressing iCalendar dates
  // An array which can be used to map day of the week numbers (0..6)
  // onto days of the week as defined in RFC 5545
  public const DAYS = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];


  // Convert an RFC 5545 day to an ordinal number representing the day of the week,
  // eg "MO" returns "1"
  public static function convertDayToOrd($day) : int
  {
    $tmp = array_keys(self::DAYS, $day);

    if (count($tmp) === 0)
    {
      throw new RFC5545Exception(
        get_vocab('invalid_RFC5545_day', $day),
        RFC5545Exception::INVALID_DAY
      );
    }

    return $tmp[0];
  }


  // Splits a BYDAY string into its ordinal and day parts, returned as a simple array.
  // For example "-1SU" is returned an array indexed by 'ordinal' and 'day' keys, eg
  // array('ordinal' => -1, 'day' => 'SU');
  public static function parseByday(string $byday) : array
  {
    $result = array();

    $split_pos = mb_strlen($byday) -2;
    $result['ordinal'] = (int) mb_substr($byday, 0, $split_pos);
    $result['day'] = mb_substr($byday, $split_pos, 2);

    return $result;
  }


  // Create a comma separated list of dates in an EXDATE property.
  public static function createExdateProperty(array $timestamps, ?string $timezone) : string
  {
    $result = "EXDATE";

    if (isset($timezone))
    {
      $result .= ";TZID=$timezone";
    }

    $dates = self::createExdateList($timestamps, $timezone);

    return "$result:" . implode(',', $dates);
  }


  public static function createExdateList(array $timestamps, ?string $timezone) : array
  {
    $dates = array();

    foreach ($timestamps as $timestamp)
    {
      if (isset($timezone))
      {
        $dates[] = date(self::DATETIME_FORMAT, $timestamp);
      }
      else
      {
        $dates[] = gmdate(self::DATETIME_FORMAT . '\Z', $timestamp);
      }
    }

    return $dates;
  }

  // Returns a UNIX timestamp given an RFC5545 date or date-time
  // $params is an optional second argument and is an array of property parameters
  public static function getTimestamp(string $value, ?array $params=null) : int
  {
    // If we haven't got any parameters default to "UTC".   Not strictly correct,
    // but in most cases it will be true.  Need to do something better.
    if (empty($params))
    {
      $event_timezone = 'UTC';
    }

    $value_type = 'DATE-TIME';  // the default

    // Work out which, if any, parameters have been set
    if (isset($params))
    {
      foreach ($params as $param_name => $param_value)
      {
        switch ($param_name)
        {
          case 'VALUE':
            $value_type = $param_value;
            break;
          case 'TZID':
            $event_timezone = $param_value;
            break;
        }
      }
    }

    if (str_ends_with($value, 'Z'))
    {
      $value = rtrim($value, 'Z');
      $event_timezone = 'UTC';
    }

    if (!isset($event_timezone))
    {
      $event_timezone = date_default_timezone_get();
      if ($value_type == 'DATE-TIME')
      {
        trigger_error("Floating times not supported", E_USER_NOTICE);
      }
    }

    if ($value_type == 'DATE')
    {
      $value .= 'T000000';
    }

    $datetime = DateTime::createFromFormat('Ymd\THis', $value, new DateTimeZone($event_timezone));
    return $datetime->getTimestamp();
  }


  // Escape text for use as an iCalendar quoted string
  public static function escapeQuotedString(string $str) : string
  {
    // From RFC 5545:
    //    quoted-string = DQUOTE *QSAFE-CHAR DQUOTE

    //    QSAFE-CHAR    = WSP / %x21 / %x23-7E / NON-US-ASCII
    //    ; Any character except CONTROL and DQUOTE

    // We'll just get rid of any double quotes, replacing them with a space.
    // (There is no way of escaping double quotes)
    return '"' . str_replace('"', ' ', $str) . '"';
  }


  public static function unescapeQuotedString(string $str) : string
  {
    return trim($str, '"');
  }


  // Reverses RFC 5545 escaping of text
  public static function unescapeText(string $str) : string
  {
    // Unescape '\N'
    $str = str_replace("\\N", "\N", $str);
    // Unescape '\n'
    $str = str_replace("\\n", "\n", $str);
    // Unescape ','
    $str = str_replace("\,", ",", $str);
    // Unescape ';'
    $str = str_replace("\;", ";", $str);
    // Unescape '\'
    $str = str_replace("\\\\", "\\", $str);

    return $str;
  }

}
