<?php
namespace MRBS;

// A generic class for reading and writing data from tables.  It assumes that:
//    - if an auto increment column exists then it is called 'id'
//    - the table has one or more unique columns
abstract class Table
{
  // All sub-classes must declare the following:
  //    const TABLE_NAME  // the short name for the table, eg 'area'
  //    protected static $unique_columns // an array of unique columns, eg array('area_name')
  //
  // All properties are accessed via magic methods; there should be no public properties

  private $data;  // Will contain the column keys, but could also contain extra keys


  public function __construct()
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


  public function __isset($name)
  {
    return (isset($this->data) && isset($this->data[$name]));
  }


  // Checks if this instance already exists in the table
  public function exists()
  {
    return ($this->getRow() !== null);
  }


  // Delete from the database
  public function delete()
  {
    $conditions = array();
    $sql_params = array();
    $cols = new Columns(_tbl(static::TABLE_NAME));
    $condition_columns = $cols->hasIdColumn() ? array('id') : static::$unique_columns;

    foreach ($condition_columns as $condition_column)
    {
      $conditions[] = "$condition_column=?";
      $sql_params[] = $this->{$condition_column};
    }

    $sql = "DELETE FROM " . _tbl(static::TABLE_NAME) . "
                  WHERE " . implode(' AND ', $conditions);
    db()->command($sql, $sql_params);
  }


  // Saves to the database
  public function save()
  {
    $this->data = static::onWrite($this->data);
    $this->upsert('update');
  }


  // Inserts/updates into the table.
  private function upsert()
  {
    // We use an "upsert" query here because that avoids having to test to
    // see whether the row exists first - leaving a (very small) chance that
    // the row might be deleted/created in the meantime.   The upsert query also
    // returns the id as part of the query, though the mechanism differs between
    // MySQl and PostgreSQL. This avoids having to do a second query to find the
    // id of the row which we have just inserted/updated.  The other reason for
    // doing this is that there's also again a very small chance that the row
    // could be deleted after we've upserted it and before the second query.
    $columns = array();
    $values = array();
    $sql_params = array();

    $table_data = $this->data;

    $cols = new Columns(_tbl(static::TABLE_NAME));
    $column_names = $cols->getNames();

    // First of all get the column names and values for the INSERT part
    for ($i=0; $i < count($column_names); $i++)
    {
      $column_name = $column_names[$i];
      // We are only interested in those elements of $table_data that have
      // a corresponding column in the table - except for 'id' which is
      // assumed to be auto-increment.
      if (($column_name == 'id') || !array_key_exists($column_name, $table_data))
      {
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
        // Need to make sure the placeholder only uses allowed characters which are
        // [a-zA-Z0-9_].   We can't use the column name because the column name might
        // contain characters which are not allowed.   And we can't use '?' because
        // we want to use the placeholders twice, once for the insert and once for the
        // update part of the query.
        $named_parameter = ":p$i";
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

    // Then go through the columns we've just found and turn them into assignments
    // for the update part
    $assignments = array();
    for ($i=0; $i<count($columns); $i++)
    {
      $column = $columns[$i];
      $value = $values[$i];
      $assignments[] = db()->quote($column) . "=$value";
    }

    $quoted_columns = array_map(array(db(), 'quote'), $columns);
    $sql = "INSERT INTO " . _tbl(static::TABLE_NAME) . "
                        (" . implode(', ', $quoted_columns) . ")
                 VALUES (" . implode(', ', $values) . ") ";
    $sql .= db()->syntax_on_duplicate_key_update(static::$unique_columns,
                                                 $assignments,
                                                 $cols->hasIdColumn());

    $res = db()->query($sql, $sql_params);

    // If there's an id column, get the id.   First of all we try and see if it
    // has been returned in the query, which will be the case if we are using
    // PostgreSQL.  If that doesn't work we can get it using insert_id(), which
    // will work for MySQL, but not for PostgreSQL.
    if ($cols->hasIdColumn())
    {
      try
      {
        $row = $res->next_row_keyed();
        $this->id = $row['id'];
      }
      catch (\PDOException $e)
      {
        $this->id = db()->insert_id(_tbl(static::TABLE_NAME), 'id');
      }
    }
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead($row)
  {
    return $row;
  }


  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite($row)
  {
    return $row;
  }


  public function load(array $row)
  {
    $row = static::onRead($row);

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
    // Obtain a lock (see also save)
    db()->mutex_lock(_tbl(static::TABLE_NAME));

    $sql = "DELETE FROM " . _tbl(static::TABLE_NAME) . "
                  WHERE id=:id
                  LIMIT 1";
    $sql_params = array(':id' => $id);
    db()->command($sql, $sql_params);

    // Release the lock
    db()->mutex_unlock(_tbl(static::TABLE_NAME));
  }


  protected static function getByColumn($column, $value)
  {
    return static::getByColumns(array($column => $value));
  }


  protected static function getByColumns(array $columns)
  {
    $conditions = array();
    $sql_params = array();

    foreach ($columns as $name => $value)
    {
      $conditions[] = "$name=?";
      $sql_params[] = $value;
    }

    $sql = "SELECT *
              FROM " . _tbl(static::TABLE_NAME) . "
             WHERE " . implode(' AND ', $conditions) . "
             LIMIT 1";

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


  // Returns the row in the table corresponding to this instance, or
  // NULL if it doesn't exist.
  private function getRow()
  {
    $where_condition_parts = array();
    $sql_params = array();
    foreach (static::$unique_columns as $unique_column)
    {
      $where_condition_parts[] = $unique_column . '=?';
      if (!isset($this->{$unique_column}))
      {
        throw new \Exception("Column '$unique_column' is not set.");
      }
      $sql_params[] = $this->{$unique_column};
    }
    $where_condition = implode(' AND ', $where_condition_parts);

    $sql = "SELECT *
              FROM " . _tbl(static::TABLE_NAME) . "
             WHERE $where_condition
             LIMIT 1";
    $res = db()->query($sql, $sql_params);

    return ($res->count() > 0) ? $res->next_row_keyed() : null;
  }
}
