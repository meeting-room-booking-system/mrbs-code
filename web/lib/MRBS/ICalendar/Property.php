<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use DateTimeZone;
use MRBS\DateTime;
use MRBS\Utf8\Utf8String;

class Property
{
  private const DATETIME_FORMAT = 'Ymd\THis';  // Format for expressing iCalendar dates
  private const VALUE_TYPE_BINARY = 'BINARY';
  private const VALUE_TYPE_BOOLEAN = 'BOOLEAN';
  private const VALUE_TYPE_CAL_ADDRESS = 'CAL-ADDRESS';
  private const VALUE_TYPE_DATE = 'DATE';
  private const VALUE_TYPE_DATE_TIME = 'DATE-TIME';
  private const VALUE_TYPE_DURATION = 'DURATION';
  private const VALUE_TYPE_FLOAT = 'FLOAT';
  private const VALUE_TYPE_INTEGER = 'INTEGER';
  private const VALUE_TYPE_PERIOD = 'PERIOD';
  private const VALUE_TYPE_RECUR = 'RECUR';
  private const VALUE_TYPE_TEXT = 'TEXT';
  private const VALUE_TYPE_TIME = 'TIME';
  private const VALUE_TYPE_URI = 'URI';
  private const VALUE_TYPE_UTC_OFFSET = 'UTC-OFFSET';

  private $name;
  private $params = [];
  private $values = [];
  private $value_type;

  // TODO: Rewrite import.php to use these classes.

  /**
   * @param string|string[] $values
   */
  public function __construct(string $name, $values)
  {
    // Property names are case-insensitive, but by convention we use uppercase.
    $this->name = mb_strtoupper($name);
    $this->values = (array) $values;
    $this->setImplicitValueType();
  }


  /**
   * Create a property instance given a string.
   *
   * @param string $string An unfolded property line
   */
  public static function createFromString(string $string) : self
  {
    $parsed_string = self::parseLine($string);
    $property = new self($parsed_string['name'], $parsed_string['values']);
    foreach ($parsed_string['params'] as $name => $values)
    {
      $property->addParameter($name, $values);
    }
    return $property;
  }


  /**
   * Create a property instance of type DATE-TIME from UNIX timestamps.
   *
   * @param int|int[] $timestamps
   */
  public static function createFromTimestamps(string $name, $timestamps, ?string $tzid=null) : self
  {
    $result = new self($name, self::convertTimestamps($timestamps, $tzid));

    if (isset($tzid))
    {
      $result->addParameter('TZID', $tzid);
    }

    return $result;
  }


  /**
   * Convert an array of UNIX timestamps to DATE-TIME values.
   *
   * @param int|int[] $timestamps
   * @return string[]
   */
  public static function convertTimestamps($timestamps, ?string $tzid=null) : array
  {
    $values = [];
    $timestamps = (array) $timestamps;
    $format = self::DATETIME_FORMAT;

    if (!isset($tzid))
    {
      $tzid = 'UTC';
      $format .= '\Z';
    }

    foreach ($timestamps as $timestamp)
    {
      $date = new DateTime('now', new DateTimeZone($tzid));
      $date->setTimestamp($timestamp);
      $values[] = $date->format($format);
    }

    return $values;
  }


  /**
   * Adds a parameter/parameters to the property.
   *
   * @param string|string[] $values
   */
  public function addParameter(string $name, $values) : void
  {
    // Parameter names are case-insensitive, but by convention we use uppercase.

    // Parameters can have multiple values [param         = param-name "=" param-value *("," param-value)].
    // See, for example, DELEGATED-FROM and DELEGATED-TO in RFC 5545.
    $uc_name = mb_strtoupper($name);
    $this->params[$uc_name] = array_merge($this->params[$uc_name] ?? [], (array) $values);

    // If the value type has been set explicitly using a VALUE parameter then update the value type.
    if ($uc_name == 'VALUE')
    {
      $this->value_type = mb_strtoupper($values);
    }
  }


  /*
   * Get the property name.
   */
  public function getName() : string
  {
    return $this->name;
  }


  /**
   * Get the property values.
   */
  public function getValues() : array
  {
    return $this->values;
  }


  public function getParamValues(string $name) : array
  {
    return $this->params[$name] ?? [];
  }


  /**
   * Convert the property to an unfolded string.
   */
  public function toString() : string
  {
    $result = $this->name;

    foreach ($this->params as $name => $values)
    {
      $result .= ';' . $name . '=' . implode(',', array_map([self::class, 'escapeParamValue'], $values));
    }

    if ($this->value_type == self::VALUE_TYPE_TEXT)
    {
      $value_string = implode(',', array_map([self::class, 'escapeText'], $this->values));
    }
    else
    {
      $value_string = implode(',', $this->values);
    }

    return "$result:$value_string" . Calendar::EOL;
  }


  /**
   * Converts a property of value type DATE-TIME to UNIX timestamps.
   *
   * @see https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.5
   *
   * @return int[]
   */
  public function toTimestamps() : array
  {
    if ($this->value_type !== self::VALUE_TYPE_DATE_TIME)
    {
      throw new \Exception("Property '$this->name' is not of type DATE-TIME");
    }

    $result = [];

    foreach ($this->values as $value)
    {
      if (!isset($this->params['TZID']))
      {
        // FORM #1: DATE WITH LOCAL TIME
        if (!str_ends_with($value, 'Z'))
        {
          throw new \Exception("Floating times not supported");
        }

        // FORM #2: DATE WITH UTC TIME
        $value = rtrim($value, 'Z');
        $tzid = 'UTC';
      }
      else
      {
        // FORM #3: DATE WITH LOCAL TIME AND TIME ZONE REFERENCE
        if (!str_ends_with($value, 'Z'))
        {
          $tzid = $this->params['TZID'][0];
        }
        else
        {
          throw new \Exception("Both a TZID parameter and a Z suffix are not supported (see RFC 5545 section 3.3.5");
        }
      }

      $datetime = DateTime::createFromFormat('Ymd\THis', $value, new DateTimeZone($tzid));
      $result[] = $datetime->getTimestamp();
    }

    return $result;
  }


  /**
   * Parse a property content line
   *
   * @param string $line An unfolded property line
   * @return array{'name': string, 'params': array<string, string[]>, 'values': string[]}
   */
  private static function parseLine(string $line) : array
  {
    $result = [];
    $params = [];

    // Get the property name, which will be the part before the first colon or semicolon.
    $split = preg_split('/([:;])/', $line, 2, PREG_SPLIT_DELIM_CAPTURE);
    $result['name'] = $split[0];

    // Get any parameters, which come after a semicolon that isn't in a double-quoted string.
    while ($split[1] == ';')
    {
      $split = preg_split('/([:;](?![^"]*"{1}[:;]))/', $split[2], 2, PREG_SPLIT_DELIM_CAPTURE);
      $param = self::parseParam($split[0]);
      $params[$param['name']] = $param['values'];
    }
    $result['params'] = $params;

    // Finally, get the property values, which come after a colon that isn't in a double-quoted string.
    $result['values'] = self::parsePropertyValues($split[2]);

    return $result;
  }


  private static function parseParam(string $param) : array
  {
    $result = [];
    $split = preg_split('/(=)/', $param, 2, PREG_SPLIT_DELIM_CAPTURE);
    $result['name'] = $split[0];
    $result['values'] = self::parseParamValues($split[2]);
    return $result;
  }


  /**
   * Parse a parameter value string.
   */
  private static function parseParamValues(string $value_string) : array
  {
    // Property parameters can have multiple values (see https://datatracker.ietf.org/doc/html/rfc5545#section-3.2.4).
    // Split the sting by unescaped commas.
    $result = preg_split('/(,(?![^"]*"{1},))/', $value_string);
    // Unescape the values
    return array_map([self::class, 'unescapeParamValue'], $result);
  }


  /**
   * Parse a property value string.
   */
  private static function parsePropertyValues(string $value_string) : array
  {
    // Properties can have multiple values, separated by commas. These are difficult
    // to parse using a regex as look-behinds need to be fixed width, so we can't look
    // for an odd or even number of backslashes.  Instead, we just iterate through the
    // characters in the string.
    $result = [];
    $value = '';
    $in_escape = false;

    $iterator = new Utf8String($value_string);
    while (null !== ($current_char = $iterator->current()))
    {
      if ($in_escape)
      {
        if (!in_array($current_char, ["\\", ";", ",", "\n", "\N"]))
        {
          $message = "Invalid escape sequence '\\$current_char' in value string '$value_string'.";
          trigger_error($message, E_USER_WARNING);
        }
        $value .= $current_char;
        $in_escape = false;
      }
      elseif ($current_char == "\\")
      {
        $in_escape = true;
      }
      elseif ($current_char == ",")
      {
        $result[] = $value;
        $value = '';
      }
      else
      {
        $value .= $current_char;
      }
      $iterator->next();
    }

    $result[] = $value;
    return $result;
  }


  /**
   * Escapes a parameter value, if necessary, so that it can be used in an iCalendar property.
   */
  private static function escapeParamValue(string $value) : string
  {
    // From RFC 5545:
    //    quoted-string = DQUOTE *QSAFE-CHAR DQUOTE

    //    QSAFE-CHAR    = WSP / %x21 / %x23-7E / NON-US-ASCII
    //    ; Any character except CONTROL and DQUOTE

    // "Property parameter values MUST NOT contain the DQUOTE character.  The
    // DQUOTE character is used as a delimiter for parameter values that
    // contain restricted characters or URI text."
    if (str_contains($value, '"'))
    {
      $value = str_replace('"', "'", $value);
      $message = "Parameter value '$value' contains double quotes.  This is not allowed.  They have been replaced with single quotes.";
      trigger_error($message, E_USER_WARNING);
    }

    // "Property parameter values that contain the COLON, SEMICOLON, or COMMA
    // character separators MUST be specified as quoted-string text values."
    if (preg_match('/[:;,]/', $value))
    {
      $value = '"' . $value . '"';
    }

    return $value;
  }


  /**
   * Unescape a parameter value.
   */
  private static function unescapeParamValue(string $str) : string
  {
    return trim($str, '"');
  }


  /**
   * Escape text for use in a property TEXT value.
   */
  private static function escapeText(string $text) : string
  {
    // Escape '\'
    $text = str_replace("\\", "\\\\", $text);
    // Escape ';'
    $text = str_replace(";", "\;", $text);
    // Escape ','
    $text = str_replace(",", "\,", $text);
    // EOL can only be \n
    $text = str_replace("\r\n", "\n", $text);
    // Escape '\n'
    $text = str_replace("\n", "\\n", $text);
    // Escape '\N'
    $text = str_replace("\N", "\\N", $text);

    return $text;
  }


  /**
   * Reverses RFC 5545 escaping of text.
   *
   * Only suitable for a single value, not a list of values.
   */
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


  private function setImplicitValueType() : void
  {
    // See RFC 5545 for the default value types for each property.
    switch ($this->name)
    {
      case 'ATTENDEE':
      case 'ORGANIZER':
        $this->value_type = self::VALUE_TYPE_CAL_ADDRESS;
        break;

      case 'COMPLETED':
      case 'CREATED':
      case 'DTEND':
      case 'DTSTAMP':
      case 'DTSTART':
      case 'DUE':
      case 'EXDATE':
      case 'LAST-MODIFIED':
      case 'RDATE':
      case 'RECURRENCE-ID':
        $this->value_type = self::VALUE_TYPE_DATE_TIME;
        break;

      case 'DURATION':
      case 'TRIGGER':
        $this->value_type = self::VALUE_TYPE_DURATION;
        break;

      case 'GEO':
        $this->value_type = self::VALUE_TYPE_FLOAT;
        break;

      case 'PERCENT-COMPLETE':
      case 'PRIORITY':
      case 'REPEAT':
      case 'SEQUENCE':
        $this->value_type = self::VALUE_TYPE_INTEGER;
        break;

      case 'FREEBUSY':
        $this->value_type = self::VALUE_TYPE_PERIOD;
        break;

      case 'RRULE':
        $this->value_type = self::VALUE_TYPE_RECUR;
        break;

      case 'ATTACH':
      case 'TZURL':
      case 'URL':
        $this->value_type = self::VALUE_TYPE_URI;
        break;

      case 'TZOFFSETFROM':
      case 'TZOFFSETTO':
        $this->value_type = self::VALUE_TYPE_UTC_OFFSET;
        break;

      default:
        $this->value_type = self::VALUE_TYPE_TEXT;
        break;
    }
  }

}
