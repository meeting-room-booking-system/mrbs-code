<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use DateTimeZone;
use MRBS\DateTime;
use function MRBS\get_vocab;

class RFC5545
{
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
