<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use MRBS\Utf8\Utf8String;

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

    $result .= ':' . implode(',', array_map([self::class, 'escapeText'], $this->values));
    return $result . RFC5545::EOL;
  }


  // TODO: Fix @return definition for 'params'

  /**
   * Parse a property content line
   *
   * @param string $line An unfolded property line
   * @return array{'name': string, 'params': array, 'values': string[]}
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
    // TODO: take the whole array, not the first element
    $result['values'] = self::parseParamValues($split[2]);
    return $result;
  }


  private static function parseParamValues(string $value_string) : array
  {
    // Split the sting by unescaped commas.
    $result = preg_split('/(,(?![^"]*"{1},))/', $value_string);
    // Unescape the values
    return array_map([self::class, 'unescapeParamValue'], $result);
  }


  private static function parsePropertyValues(string $value_string) : array
  {
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
          $message = "Invalid escape sequence '$current_char' in value string '$value_string'.";
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
