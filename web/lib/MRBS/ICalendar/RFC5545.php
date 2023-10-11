<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use DateTimeZone;
use MRBS\DateTime;
use function MRBS\get_charset;
use function MRBS\get_mail_charset;
use function MRBS\get_vocab;
use function MRBS\utf8_seq;

class RFC5545
{
  public const DATETIME_FORMAT = 'Ymd\THis';  // Format for expressing iCalendar dates
  // An array which can be used to map day of the week numbers (0..6)
  // onto days of the week as defined in RFC 5545
  public const DAYS = ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'];
  public const EOL  = "\r\n";  // Lines must be terminated by CRLF

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

    $split_pos = strlen($byday) -2;
    $result['ordinal'] = (int) substr($byday, 0, $split_pos);
    $result['day'] = substr($byday, $split_pos, 2);

    return $result;
  }



  // Parse a content line which is a property (ie is inside a component).   Returns
  // an associative array:
  //   'name'       the property name
  //   'params'     an associative array of parameters indexed by parameter name
  //   'value'      the property value.  The value will have escaping reversed
  public static function parseProperty(string $line) : array
  {
    $result = array();
    // First of all get the string up to the first colon or semicolon.   This will
    // be the property name.   We also want to get the delimiter so that we know
    // whether there are any parameters to come.   The split will return an array
    // with three elements:  0 - the string before the delimiter, 1 - the delimiter
    // and 2 the rest of the string
    $tmp = self::split($line);
    $result['name'] = $tmp[0];
    $params = array();
    if ($tmp[1] != ':')
    {
      // Get all the property parameters
      do
      {
        $tmp = self::split($tmp[2]);
        list($param_name, $param_value) = explode('=', $tmp[0], 2);
        // The parameter value can be a quoted string, so get rid of any double quotes
        $params[$param_name] = self::unescapeQuotedString($param_value);
      }
      while ($tmp[1] != ':');
    }
    $result['params'] = $params;
    $result['value'] = self::unescapeText($tmp[2]);
    return $result;
  }


  // Extracts a VTIMEZONE component from a VCALENDAR.
  public static function extractVtimezone(string $vcalendar) : string
  {
    // The VTIMEZONE components are enclosed in a VCALENDAR, so we want to
    // extract the VTIMEZONE component.
    preg_match('/BEGIN:VTIMEZONE[\s\S]*?END:VTIMEZONE/', $vcalendar, $matches);
    return $matches[0] ?? '';
  }


  // Create a comma separated list of dates in an EXDATE property.
  public static function createExdateProperty(array $timestamps, ?string $timezone) : string
  {
    $result = "EXDATE";
    $dates = array();

    if (isset($timezone))
    {
      $result .= ";TZID=$timezone";
    }

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

    return "$result:" . implode(',', $dates);
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


  // Escape text for use in an iCalendar text value
  public static function escapeText(string $str) : string
  {
    // Escape '\'
    $str = str_replace("\\", "\\\\", $str);
    // Escape ';'
    $str = str_replace(";", "\;", $str);
    // Escape ','
    $str = str_replace(",", "\,", $str);
    // EOL can only be \n
    $str = str_replace("\r\n", "\n", $str);
    // Escape '\n'
    $str = str_replace("\n", "\\n", $str);
    // Escape '\N'
    $str = str_replace("\N", "\\N", $str);

    return $str;
  }


  // Reverses RFC 5545 escaping of text
  private static function unescapeText(string $str) : string
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


  // "Folds" lines longer than 75 octets. Multibyte safe.
  //
  // "Lines of text SHOULD NOT be longer than 75 octets, excluding the line
  // break.  Long content lines SHOULD be split into a multiple line
  // representations using a line "folding" technique.  That is, a long
  // line can be split between any two characters by inserting a CRLF
  // immediately followed by a single linear white-space character (i.e.,
  // SPACE or HTAB).  Any sequence of CRLF followed immediately by a
  // single linear white-space character is ignored (i.e., removed) when
  // processing the content type."  (RFC 5545)
  public static function fold(string $str) : string
  {
    // Deal with the trivial case
    if ($str === '')
    {
      return $str;
    }

    $line_split = "\r\n ";  // The RFC also allows a tab instead of a space

    // We assume that we are using UTF-8 and therefore that a space character
    // is one octet long.  If we ever switched for some reason to using for
    // example UTF-16 this assumption would be invalid.
    if ((get_charset() != 'utf-8') || (get_mail_charset() != 'utf-8'))
    {
      trigger_error("MRBS: internal error - using unsupported character set", E_USER_WARNING);
    }
    $space_octets = 1;

    $octets_max = 75;

    $result = '';
    $octets = 0;
    $byte_index = 0;

    while (isset($byte_index))
    {
      // Get the next character
      $prev_byte_index = $byte_index;
      $char = utf8_seq($str, $byte_index);

      $char_octets = $byte_index - $prev_byte_index;
      // If it's a CR then look ahead to the following character, if there is one
      if (($char == "\r") && isset($byte_index))
      {
        $this_byte_index = $byte_index;
        $next_char = utf8_seq($str, $byte_index);
        // If that's a LF then take the CR, and advance by one character
        if ($next_char == "\n")
        {
          $result .= $char;    // take the CR
          $char = $next_char;  // advance by one character
          $octets = 0;         // reset the octet counter to the beginning of the line
          $char_octets = 0;    // and pretend the LF is zero octets long so that after
          // we've added it in we're still at the beginning of the line
        }
        // otherwise stay where we were
        else
        {
          $byte_index = $this_byte_index;
        }
      }
      // otherwise if this character will take us over the octet limit for the line,
      // fold the line and set the octet count to however many octets a space takes
      // (the folding involves adding a CRLF followed by one character, a space or a tab)
      //
      // [Note:  It's not entirely clear from the RFC whether the octet that is introduced
      // when folding counts towards the 75 octets.   Some implementations (eg Google
      // Calendar as of Jan 2011) do not count it.  However, it can do no harm to err on
      // the safe side and include the initial whitespace in the count.]
      elseif (($octets + $char_octets) > $octets_max)
      {
        $result .= $line_split;
        $octets = $space_octets;
      }
      // finally add the character to the result string and up the octet count
      $result .= $char;
      $octets += $char_octets;
    }
    return $result;
  }


  // Reverse the RFC 5545 folding process, which splits lines into groups
  // of max 75 octets separated by 'CRLFspace' or 'CRLFtab'
  public static function unfold(string $str) : string
  {
    // Deal with the trivial case
    if ($str === '')
    {
      return $str;
    }

    // We've got a non-zero length string
    $result = '';
    $byte_index = 0;

    while (isset($byte_index))
    {
      // Get the next character
      $char = utf8_seq($str, $byte_index);
      // If it's a CR then look ahead to the following character, if there is one
      if (($char == "\r") && isset($byte_index))
      {
        $char = utf8_seq($str, $byte_index);
        // If that's a LF then look ahead to the next character, if there is one
        if (($char == "\n") && isset($byte_index))
        {
          $char = utf8_seq($str, $byte_index);
          // If that's a space or a tab then ignore it because we've just had a fold
          // sequence.  Otherwise, add the characters into the result string.
          if (($char != " ") && ($char != "\t"))
          {
            $result .= "\r\n" . $char;
          }
        }
        else
        {
          $result .= "\r" . $char;
        }
      }
      else
      {
        $result .= $char;
      }
    }

    return $result;
  }


  // Splits a string at the first colon or semicolon (the delimiter) unless the delimiter
  // is inside a quoted string.  Used for parsing iCalendar lines to get property parameters
  // It assumes the string will always have at least one more delimiter to come, so can
  // only be used when you know you've still got the colon to come.
  //
  // Returns an array of three elements (the second is the delimiter)
  // or just one element if the delimiter is not found
  private static function split(string $string) : array
  {
    // We want to split the string up to the first delimiter which isn't inside a quoted
    // string.   So the look ahead must not contain exactly one double quote before the next
    // delimiter.   Note that (a) you cannot escape double quotes inside a quoted string, so
    // we don't have to worry about that complication (b) we assume there will always be a
    // second delimiter
    return preg_split('/([:;](?![^"]*"{1}[:;]))/', $string, 2, PREG_SPLIT_DELIM_CAPTURE);
  }
}
