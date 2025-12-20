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


  public static function createFromLine(string $line) : self
  {
    // TODO: implement

  }


  /**
   * Adds a parameter to the propert
   *
   * @param string|string[] $values
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
