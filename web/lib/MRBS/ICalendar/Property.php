<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class Property
{
  private $name;
  private $params = [];
  private $values = [];


  // TODO: Properties can have multiple values.  Add support for that in parseLine().
  // TODO: Property parameters can have multiple values (see https://datatracker.ietf.org/doc/html/rfc5545#section-3.2.4) .  Add support for that in parseLine().
  // TODO: Rewrite import.php to use these classes.

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
   * Adds a parameter to the property.
   */
  public function addParameter(string $name, string $values) : void
  {
    // Parameter names are case-insensitive, but by convention we use uppercase.

    // Parameters can have multiple values [param         = param-name "=" param-value *("," param-value)].
    // See, for example, DELEGATED-FROM and DELEGATED-TO in RFC 5545.
    $this->params[mb_strtoupper($name)] = array ($values);
  }


  public function getName() : string
  {
    return $this->name;
  }


  public function getValues() : array
  {
    return $this->values;
  }


  public function toString() : string
  {
    $result = $this->name;

    foreach ($this->params as $name => $values)
    {
      $result .= ';' . $name . '=' . implode(',', array_map([self::class, 'escapeParamValue'], $values));
    }

    $result .= ':' . implode(',', array_map([self::class, 'escapeText'], $this->values));
    return $result . RFC5545::EOL;
  }


  // Parse a content line which is a property (ie is inside a component).   Returns
  // an associative array:
  //   'name'       the property name
  //   'params'     an associative array of parameters indexed by parameter name
  //   'value'      the property value.  The value will have escaping reversed
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

    // Finally get the property values, which come after a colon that isn't in a double-quoted string.
    // TODO: change to 'values'
    $result['value'] = self::parsePropertyValues($split[2]);

    return $result;
  }


  private static function parseParam(string $param) : array
  {
    $result = [];
    $split = preg_split('/(=)/', $param, 2, PREG_SPLIT_DELIM_CAPTURE);
    $result['name'] = $split[0];
    // TODO: take the whole array, not the first element
    $result['values'] = self::parseParamValues($split[2])[0];
    return $result;
  }


  private static function parseParamValues(string $values) : array
  {
    // TODO: properly parse a list of values
    return [self::unescapeParamValue($values)];
  }


  private static function parsePropertyValues(string $values) : array
  {
    // TODO: properly parse a list of values
    return [self::unescapeText($values)];
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
