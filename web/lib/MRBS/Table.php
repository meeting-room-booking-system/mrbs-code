<?php
namespace MRBS;

// A generic class for reading and writing data from tables.
abstract class Table
{
  // All sub-classes must declare the following
  // const TABLE_NAME  // the short name for the table, eg 'area'

  // protected static $unique_columns // an array of unique columns, eg array('area_name')

  protected $data;  // Will contain the column keys, but could also contain extra keys
  protected $column_info;


  public function __construct($name=null)
  {
    if (!defined('static::TABLE_NAME'))
    {
      $message = 'Constant TABLE_NAME is not defined in subclass ' . get_class($this);
      throw new \Exception($message);
    }

    if (empty(static::$unique_columns))
    {
      $message = 'Static property \'$unique_columns\' is not defined in subclass ' . get_class($this);
      throw new \Exception($message);
    }

    $this->data = array();
    $this->unique_columns = array();
    $this->column_info = db()->field_info(_tbl(static::TABLE_NAME));
  }


  public function __get($name)
  {
    return (isset($this->data) && isset($this->data[$name])) ? $this->data[$name] : null;
  }


  public function __set($name, $value)
  {
    $this->data[$name] = $value;
  }


  // Checks if this instance already exists in the table
  public function exists()
  {
    $where_condition_parts = array();
    $sql_params = array();
    foreach (static::$unique_columns as $unique_column)
    {
      $where_condition_parts[] = $unique_column . '=?';
      $sql_params[] = $this->data[$unique_column];
    }
    $where_condition = implode(' AND ', $where_condition_parts);

    $sql = "SELECT *
              FROM " . _tbl(static::TABLE_NAME) . "
             WHERE $where_condition
             LIMIT 1";
    $res = db()->query($sql, $sql_params);
    return ($res->count() > 0);
  }


  // Saves to the database
  public function save()
  {
    // Could do an INSERT ... ON DUPLICATE KEY UPDATE but there's no
    // easy equivalent in PostgreSQL until PostgreSQL 9.5.
    db()->mutex_lock(_tbl(static::TABLE_NAME));
    if ($this->exists())
    {
      $this->update();
    }
    else
    {
      $this->insert();
    }
    db()->mutex_unlock(_tbl(static::TABLE_NAME));
  }


  // Insert into the table.  Assumes that the row doesn't already exist.
  public function insert()
  {
    $columns = array();
    $values = array();
    $sql_params = array();

    // We are only interested in those elements of $this->data that have
    // a corresponding column in the table - except for 'id' which is
    // assumed to be auto-increment.
    foreach ($this->column_info as $column_info)
    {
      if ($column_info['name'] == 'id')
      {
        continue;
      }

      $key = $column_info['name'];
      $columns[] = $key;
      if (array_key_exists($key, $this->data))
      {
        $value = $this->data[$key];
        if (is_null($value))
        {
          $values[] = 'NULL';
        }
        else
        {
          $values[] = '?';
          if (is_bool($value))
          {
            // Need to convert booleans
            $sql_params[] = ($value) ? 1 : 0;
          }
          else
          {
            $sql_params[] = $value;
          }
        }
      }
    }

    $sql = "INSERT INTO " . _tbl(static::TABLE_NAME) . "
                        (" . implode(',', $columns) . ")
                 VALUES (" . implode(',', $values) . ")";

    db()->command($sql, $sql_params);
  }



  public function load(array $row)
  {
    foreach ($row as $key => $value)
    {
      $this->data[$key] = $value;
    }
  }


  public static function getById($id)
  {
    return static::getByColumn('id', $id);
  }


  public static function deleteById($id)
  {
    $sql = "DELETE FROM " . _tbl(static::TABLE_NAME) . "
                  WHERE id=:id
                  LIMIT 1";
    $sql_params = array(':id' => $id);
    db()->command($sql, $sql_params);
  }


  protected static function getByColumn($column, $value)
  {
    $sql = "SELECT *
              FROM " . _tbl(static::TABLE_NAME) . "
             WHERE $column=:value
             LIMIT 1";
    $sql_params = array(':value' => $value);
    $res = db()->query($sql, $sql_params);
    if ($res->count() == 0)
    {
      $result = null;
    }
    else
    {
      $class = get_called_class();
      $result = new $class();
      $result->load($res->next_row_keyed());
    }
    return $result;
  }
}
