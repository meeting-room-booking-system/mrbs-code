<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class Property
{
  private $name;
  private $params = [];
  private $values = [];


  /**
   * @param string|string[] $values
   */
  public function __construct(string $name, $values)
  {
    // Property names are case-insensitive, but by convention we use uppercase.
    $this->name = mb_strtoupper($name);
    $this->values = (array) $values;
  }


  public static function createFromString(string $string) : self
  {
    $parsed_string = self::parseLine($string);
    $property = new self($parsed_string['name'], $parsed_string['value']);
    foreach ($parsed_string['params'] as $name => $value)
    {
      $property->addParameter($name, $value);
    }
    return $property;
  }


  /**
   * Adds a parameter to the property
   */
  public function addParameter(string $name, string $value) : void
  {
    $this->params[$name] = $value;
  }


  public function getName() : string
  {
    return $this->name;
  }


  public function toString() : string
  {
    $result = $this->name;

    foreach ($this->params as $name => $value)
    {
      $result .= ';' . $name . '=' . self::escapeParamValue($value);
    }

    $result .= ':' . implode(',', array_map([self::class, 'escapeText'], $this->values));
    return $result . RFC5545::EOL;
  }

  // TODO:
  // 1. Can a parameter have multiple values?
  // 2. Add support for parsing multiple valuees
  // 3. Rewrite import to use this class

  // Parse a content line which is a property (ie is inside a component).   Returns
  // an associative array:
  //   'name'       the property name
  //   'params'     an associative array of parameters indexed by parameter name
  //   'value'      the property value.  The value will have escaping reversed
  private static function parseLine(string $line) : array
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
        $params[$param_name] = self::unescapeParamValue($param_value);
      }
      while ($tmp[1] != ':');
    }
    $result['params'] = $params;
    $result['value'] = self::unescapeText($tmp[2]);
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

}
