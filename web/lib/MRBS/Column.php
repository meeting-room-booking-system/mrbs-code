<?php
namespace MRBS;

use ReflectionClass;


class Column
{
  const NATURE_BINARY = 0;
  const NATURE_BOOLEAN = 1;
  const NATURE_CHARACTER = 2;
  const NATURE_DECIMAL = 3;
  const NATURE_INTEGER = 4;
  const NATURE_REAL = 5;
  const NATURE_TIME = 6;
  const NATURE_TIMESTAMP = 7;

  public $table;
  public $name;

  private $default;
  private $is_nullable;
  private $length;
  private $nature;


  public function __construct($table, $name)
  {
    $this->table = $table;
    $this->name = $name;
  }


  public function getDefault()
  {
    return $this->default;
  }


  public function setDefault($default)
  {
    $this->default = $default;
  }


  public function getisNullable()
  {
    return $this->is_nullable;
  }


  public function setisNullable($is_nullable)
  {
    $this->is_nullable = $is_nullable;
  }


  public function getLength()
  {
    return $this->length;
  }


  public function setLength($length)
  {
    $this->length = intval($length);
  }


  public function getNature()
  {
    return $this->nature;
  }


  public function setNature($nature)
  {
    $reflectionClass = new ReflectionClass($this);
    $constants = $reflectionClass->getConstants();
    if (!in_array($nature, array_values($constants), true))
    {
      throw new \Exception("Invalid nature '$nature'");
    }
    $this->nature = $nature;
  }


  // Gets the type ('bool', 'int' or 'string') to be used with get_form_var().
  // TODO: this method maybe doesn't belong here.
  public function getFormVarType()
  {
    switch ($this->nature)
    {
      case self::NATURE_CHARACTER:
        $var_type = 'string';
        break;
      case self::NATURE_INTEGER:
        $var_type = ($this->isBooleanLike()) ? 'bool' : 'int';
        break;
      // We can only really deal with the types above at the moment
      default:
        $var_type = 'string';
        break;
    }

    return $var_type;
  }


  // Sanitize a value ready for insertion in the database
  public function sanitizeValue($value)
  {
    // Turn the booleans into 0/1 values (necessary for PostgreSQL)
    if (is_bool($value))
    {
      $value = ($value) ? 1 : 0;
    }
    // Trim the strings and truncate them to the maximum field length
    // (necessary for PostgreSQL which doesn't truncate them itself
    // but instead will throw an error)
    elseif (is_string($value))
    {
      // Some variables, eg decimals, will also be PHP strings, so only
      // trim columns with a database nature of 'character'.
      if ($this->nature === Column::NATURE_CHARACTER)
      {
        $value = trim($value);
        $value = $this->truncate($value);
      }
    }

    return $value;
  }

  public function isBooleanLike()
  {
    // Smallints and tinyints are considered to be booleans
    return (($this->nature == self::NATURE_BOOLEAN) ||
            (($this->nature == self::NATURE_INTEGER) &&
             (isset($this->length) && ($this->length <= 2))));
  }


  // Truncate any fields that have a maximum length as a precaution.
  // Although the MAXLENGTH attribute may be used in the <input> tag, this can
  // sometimes be ignored by the browser, for example by Firefox when
  // autocompletion is used.  The user could also edit the HTML and remove
  // the MAXLENGTH attribute.    Another problem is that the <datalist> tag
  // does not accept a maxlength attribute.  Passing an oversize string to some
  // databases (eg some versions of PostgreSQL) results in an SQL error,
  // rather than silent truncation of the string.
  //
  // We truncate to a maximum number of UTF8 characters rather than bytes.
  // This is OK in current versions of MySQL and PostgreSQL, though in earlier
  // versions of MySQL (I haven't checked PostgreSQL) this could cause problems
  // as a VARCHAR(n) was n bytes long rather than n characters.
  private function truncate($value)
  {
    $result = $value;

    if (($this->nature == self::NATURE_CHARACTER) &&
        isset($this->length) &&
        ($this->length < 256))
    {
      $result = utf8_substr($value, 0, $this->length);
    }

    return $result;
  }
}
