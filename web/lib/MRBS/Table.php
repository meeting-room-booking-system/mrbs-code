<?php
namespace MRBS;

// A generic class for reading and writing data from tables.  It assumes that:
//    - if an auto increment column exists then it is called 'id'
//    - the table has one or more unique columns
abstract class Table
{
  // All sub-classes must declare the following
  // const TABLE_NAME  // the short name for the table, eg 'area'

  // protected static $unique_columns // an array of unique columns, eg array('area_name')

  private $data;  // Will contain the column keys, but could also contain extra keys


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
      $sql_params[] = $this->{$unique_column};
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
      $this->upsert('update');
    }
    else
    {
      $this->upsert('insert');
    }
    db()->mutex_unlock(_tbl(static::TABLE_NAME));
  }


  // Inserts/updates into the table depending on $action.  Assumes that the
  // row doesn't already exist for an insert and does for an update.
  private function upsert($action='update')
  {
    $columns = array();
    $values = array();
    $sql_params = array();
    $has_id_column = false;

    // Merge the accessible and inaccessible properties for the table into
    // a single array.
    $table_data = $this->data;
    $accessible_properties = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
    foreach ($accessible_properties as $property)
    {
      $table_data[$property->name] = $property->getValue($this);
    }

    $column_names = Columns::getInstance(_tbl(static::TABLE_NAME))->getNames();

    // We are only interested in those elements of $table_data that have
    // a corresponding column in the table - except for 'id' which is
    // assumed to be auto-increment.
    foreach ($column_names as $column_name)
    {
      if (($column_name == 'id') || !array_key_exists($column_name, $table_data))
      {
        $has_id_column = true;
        continue;
      }

      $columns[] = $column_name;
      $value = $table_data[$column_name];
      if (is_null($value))
      {
        if (in_array($column_name, static::$unique_columns))
        {
          throw new \Exception("Unique column '$column_name' is null");
        }
        $values[] = 'NULL';
      }
      else
      {
        $named_parameter = ":$column_name";
        $values[] = $named_parameter;
        if (is_bool($value))
        {
          // Need to convert booleans
          $sql_params[$named_parameter] = ($value) ? 1 : 0;
        }
        else
        {
          $sql_params[$named_parameter] = $value;
        }
      }
    }

    if ($action == 'insert')
    {
      $sql = "INSERT INTO " . _tbl(static::TABLE_NAME) . "
                          (" . implode(',', $columns) . ")
                   VALUES (" . implode(',', $values) . ")";
    }
    else
    {
      $sql = "UPDATE " . _tbl(static::TABLE_NAME) . " SET ";
      $assignments = array();
      $conditions = array();
      for ($i=0; $i<count($columns); $i++)
      {
        $column = $columns[$i];
        $value = $values[$i];
        if (in_array($column, static::$unique_columns))
        {
          $conditions[] = "$column=$value";
        }
        else
        {
          $assignments[] = "$column=$value";
        }
      }
      $sql .= implode(', ', $assignments);
      $sql .= " WHERE " . implode(' AND ', $conditions);
    }

    db()->command($sql, $sql_params);

    // If this was an insert action and there's an id column, get the new id.
    if (($action == 'insert') && $has_id_column)
    {
      $this->id = db()->insert_id(static::TABLE_NAME, 'id');
    }
  }



  public function load(array $row)
  {
    foreach ($row as $key => $value)
    {
      $this->{$key} = $value;
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
