<?php
declare(strict_types=1);
namespace MRBS;

use Countable;
use Iterator;
use MRBS\DB\DB;

// Holds information about table columns
// Implemented as a singleton class for performance reasons: it is
// expensive getting the field info in the constructor.
class Columns implements Countable, Iterator
{

  private static $instances = array();
  private $data;
  private $index = 0;
  private $connection;
  private $table_name;


  private function __construct($table_name, DB $connection)
  {
    assert(version_compare(MRBS_MIN_PHP_VERSION, '7.4.0', '<'), "The __wakeup() method is now redundant.");
    $this->table_name = $table_name;
    $this->connection = $connection;
    // Get the column info
    $this->data = $this->connection->field_info($table_name);
  }


  private function __clone()
  {
  }


  public function __unserialize(array $data) : void
  {
    // __unserialize() must have public visibility
    throw new \Exception("Cannot unserialize a singleton.");
  }


  // __wakeup() is deprecated from PHP 8.5.
  // "The __wakeup() serialization magic method has been deprecated. Implement __unserialize()
  // instead (or in addition, if support for old PHP versions is necessary)".
  // __unserialize() is only available from PHP 7.4.0
  public function __wakeup()
  {
    // __wakeup() must have public visibility
    throw new \Exception("Cannot unserialize a singleton.");
  }


  /**
   * Get the singleton instance for the specified table and database connection.
   *
   * @param DB|null $connection  If null, the default database connection is used.
   */
  public static function getInstance(string $table_name, ?DB $connection=null) : Columns
  {
    if (!isset($connection))
    {
      $connection = db();
    }

    if (!isset(self::$instances[$connection->dsn][$table_name]))
    {
      self::$instances[$connection->dsn][$table_name] = new self($table_name, $connection);
    }

    return self::$instances[$connection->dsn][$table_name];
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
      case 'json':
        $column->setNature(Column::NATURE_JSON);
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
