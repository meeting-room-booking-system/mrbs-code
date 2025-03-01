<?php
declare(strict_types=1);
namespace MRBS;

use Countable;
use Iterator;

// Holds information about table columns
// Implemented as a singleton class for performance reasons: it is
// expensive getting the field info in the constructor.
class Columns implements Countable, Iterator
{

  private static $instances = array();
  private $data;
  private $index = 0;
  private $table_name;


  private function __construct($table_name)
  {
    $this->table_name = $table_name;
    // Get the column info
    $this->data = db()->field_info($table_name);
  }

  private function __clone()
  {
  }

  public function __wakeup()
  {
    // __wakeup() must have public visibility
    throw new \Exception("Cannot unserialize a singleton.");
  }

  public static function getInstance(string $table_name) : Columns
  {
    if (!isset(self::$instances[$table_name]))
    {
      self::$instances[$table_name] = new self($table_name);
    }

    return self::$instances[$table_name];
  }

  public function getNames() : array
  {
    $result = array();

    foreach ($this as $column)
    {
      $result[] = $column->name;
    }

    return $result;
  }


  public function hasIdColumn() : bool
  {
    $column = $this->getColumnByName('id');
    return isset($column);
  }


  public function getColumnByName(string $name) : ?Column
  {
    foreach ($this as $column)
    {
      if ($column->name == $name)
      {
        return $column;
      }
    }

    return null;
  }


  public function current() : Column
  {
    $info = $this->data[$this->index];
    $column = new Column($this->table_name, $info['name']);
    $column->setLength($info['length']);
    $column->setDefault($info['default']);
    $column->setIsNullable($info['is_nullable']);
    $column->setType($info['type']);

    switch ($info['nature'])
    {
      case 'binary':
        $column->setNature(Column::NATURE_BINARY);
        break;
      case 'boolean':
        $column->setNature(Column::NATURE_BOOLEAN);
        break;
      case 'character':
        $column->setNature(Column::NATURE_CHARACTER);
        break;
      case 'decimal':
        $column->setNature(Column::NATURE_DECIMAL);
        break;
      case 'integer':
        $column->setNature(Column::NATURE_INTEGER);
        break;
      case 'real':
        $column->setNature(Column::NATURE_REAL);
        break;
      case 'timestamp':
        $column->setNature(Column::NATURE_TIMESTAMP);
        break;
      default:
        throw new \Exception("Unknown nature '" . $info['nature'] . "'");
        break;
    }

    return $column;
  }


  public function next() : void
  {
    $this->index++;
  }

  public function key() : int
  {
    return $this->index;
  }


  public function valid() : bool
  {
    return isset($this->data[$this->key()]);
  }


  public function rewind() : void
  {
    $this->index = 0;
  }


  public function count() : int
  {
    return count($this->data);
  }
}
