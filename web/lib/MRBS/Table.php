<?php
namespace MRBS;

use PDOException;

// A generic class for reading and writing data from tables.  It assumes that:
//    - if an auto increment column exists then it is called 'id'
//    - the table has just one unique key, excluding any id column, but that
//      unique key can cover multiple columns
//    - any columns called 'timestamp' auto-update
//
// The class handles any processing of columns before they are written or read
// (eg json_encoding) and sanitisation of values, eg truncating strings to fit
// the column and turning booleans into 0/1.
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
    return (array_key_exists($name, $this->data)) ? $this->data[$name] : null;
  }


  public function __set($name, $value)
  {
    $this->data[$name] = $value;
  }


  public function __isset($name)
  {
    return (array_key_exists($name, $this->data) && isset($this->data[$name]));
  }


  public function __unset($name)
  {
    unset($this->data[$name]);
  }


  // Checks whether an instance has the property $property.
  // (We cannot use property_exists() because we're using magic getters and setters.)
  public function hasProperty($property) : bool
  {
    return array_key_exists($property, $this->data);
  }

  // Checks if this instance already exists in the table
  public function exists() : bool
  {
    return ($this->getRow() !== null);
  }


  // Delete from the database
  public function delete() : void
  {
    $conditions = array();
    $sql_params = array();
    $cols = Columns::getInstance(_tbl(static::TABLE_NAME));
    $condition_columns = $cols->hasIdColumn() ? array('id') : static::$unique_columns;

    foreach ($condition_columns as $condition_column)
    {
      $conditions[] = db()->quote($condition_column) . "=?";
      $sql_params[] = $this->{$condition_column};
    }

    $sql = "DELETE FROM " . _tbl(static::TABLE_NAME) . "
                  WHERE " . implode(' AND ', $conditions);
    db()->command($sql, $sql_params);
  }


  // Saves to the database.  Note that this saves all columns, even if they
  // are not set in the object properties (when NULL will be saved if allowed,
  // otherwise the default column value).  You therefore need to make sure that
  // all pre-existing properties are present to stop them from being accidentally
  // overwritten.
  public function save() : void
  {
    $this->data = static::onWrite($this->data);

    // If there is an id column and we have an id then we know we
    // are doing an update of that row
    if (isset($this->data['id']))
    {
      $this->update();
    }
    // Otherwise it could be an update or an insert
    else
    {
      $this->upsert();
    }
  }


  // Inserts a new object into the database
  public function insert() : void
  {
    $this->data = static::onWrite($this->data);

    // If there is an id column, and we have an id, then this isn't a new object
    if (isset($this->data['id']))
    {
      throw new Exception("Object already exists");
    }

    $this->upsert(true);
  }


  // To be used when updating a table which has an id column and when
  // the id is already known, ie we are updating an existing row.
  private function update() : void
  {
    $columns = array();
    $values = array();
    $sql_params = array();
    $this->getQueryComponents($columns, $values, $sql_params);

    // Then go through the columns we've just found and turn them into assignments
    // for the update part
    $assignments = array();
    for ($i=0; $i<count($columns); $i++)
    {
      $column = $columns[$i];
      $value = $values[$i];
      $assignments[] = db()->quote($column) . "=$value";
    }

    $sql_params[':id'] = $this->id;

    $sql = "UPDATE " . _tbl(static::TABLE_NAME) . "
               SET " . implode(', ', $assignments) . "
             WHERE id=:id";

    db()->command($sql, $sql_params);
  }


  // Inserts/updates into the table.
  private function upsert($insert_only = false) : void
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
    $this->getQueryComponents($columns, $values, $sql_params);

    // Then go through the columns we've just found and turn them into assignments
    // for the update part
    $assignments = array();
    for ($i=0; $i<count($columns); $i++)
    {
      $column = $columns[$i];
      $value = $values[$i];
      $assignments[] = db()->quote($column) . "=$value";
    }

    $cols = Columns::getInstance(_tbl(static::TABLE_NAME));
    $quoted_columns = array_map(array(db(), 'quote'), $columns);
    $sql = "INSERT INTO " . _tbl(static::TABLE_NAME) . "
                        (" . implode(', ', $quoted_columns) . ")
                 VALUES (" . implode(', ', $values) . ") ";
    if (!$insert_only)
    {
      $sql .= db()->syntax_on_duplicate_key_update(
        static::$unique_columns,
        $assignments,
        $cols->hasIdColumn()
      );
    }

    $res = db()->query($sql, $sql_params);

    // If there's an id column, get the id.   First of all we try and see if it
    // has been returned in the query, which will be the case if we are using
    // PostgreSQL.  If that doesn't work we can get it using insert_id(), which
    // will work for MySQL, but not for PostgreSQL.
    if ($cols->hasIdColumn())
    {
      // We can't rely on $res->count() because MySQL will just return the number of
      // rows affected, not the number of rows in the result.
      try
      {
        if (!$insert_only && (false !== ($row = $res->next_row_keyed())))
        {
          $this->id = $row['id'];
        }
        else
        {
          throw new PDOException("No id returned");
        }
      }
      catch (PDOException $e)
      {
        $this->id = db()->insert_id(_tbl(static::TABLE_NAME), 'id');
      }
    }
  }


  private function getQueryComponents(array &$columns, array &$values, array &$sql_params) : void
  {
    $data = $this->data;
    $cols = Columns::getInstance(_tbl(static::TABLE_NAME));

    // First of all get the column names and values for the INSERT part
    $i = 0;
    foreach ($cols as $col)
    {
      // We are only interested in those elements of $table_data that have
      // a corresponding column in the table - except for 'id' which is
      // assumed to be auto-increment, and 'timestamp' which is assumed to
      // auto-update.
      if (in_array($col->name, array('id', 'timestamp')))
      {
        continue;
      }

      $columns[] = $col->name;

      if (!isset($data[$col->name]) && $col->getIsNullable())
      {
        if (in_array($col->name, static::$unique_columns))
        {
          throw new \Exception("Unique column '$col->name' is null");
        }
        $values[] = 'NULL';
      }
      else
      {
        // Need to make sure the placeholder only uses allowed characters which are
        // [a-zA-Z0-9_].   We can't use the column name because the column name might
        // contain characters which are not allowed.   And we can't use '?' because
        // we may want to use the placeholders twice, once for an insert and once for an
        // update.  Besides, debugging is easier with named parameters.
        $named_parameter = ":p$i";
        $values[] = $named_parameter;
        if (isset($data[$col->name]))
        {
          $sql_param = $col->sanitizeValue($data[$col->name]);
        }
        else
        {
          // The column is not nullable if we got here
          $sql_param = $col->getDefault();
        }
        $sql_params[$named_parameter] = $col->sanitizeValue($sql_param);
        $i++;
      }
    }
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead(array $row) : array
  {
    return $row;
  }


  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite(array $row) : array
  {
    return $row;
  }


  public function load(array $row) : void
  {
    global $dbsys;

    // MySQL returns everything as a string, so convert the values to
    // their proper types first. (It's easier to test for the db not
    // being pgsql, because both 'mysql' and 'mysqli' cover MySQL, for
    // backwards compatibility of config files.)
    if ($dbsys != 'pgsql')
    {
      $columns = Columns::getInstance(_tbl(static::TABLE_NAME));

      foreach ($columns as $column)
      {
        if (isset($row[$column->name]))
        {
          switch ($column->getNature())
          {
            case $column::NATURE_INTEGER:
              $row[$column->name] = (int)$row[$column->name];
              break;
            case $column::NATURE_REAL:
              $row[$column->name] = (float)$row[$column->name];
              break;
            default:
              break;
          }
        }
      }
    }

    $row = static::onRead($row);

    foreach ($row as $key => $value)
    {
      $this->{$key} = $value;
    }
  }


  public static function getById($id) : ?object
  {
    return static::getByColumn('id', $id);
  }


  public static function deleteById($id) : void
  {
    // Can't use LIMIT with DELETE in PostgreSQL
    $sql = "DELETE FROM " . _tbl(static::TABLE_NAME) . "
                  WHERE id=:id";
    $sql_params = array(':id' => $id);
    db()->command($sql, $sql_params);
  }


  protected static function getByColumn($column, $value) : ?object
  {
    return static::getByColumns(array($column => $value));
  }


  protected static function getByColumns(array $columns) : ?object
  {
    $conditions = array();
    $sql_params = array();

    foreach ($columns as $name => $value)
    {
      $conditions[] = db()->quote($name) . "=?";
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
  private function getRow() : ?array
  {
    $where_condition_parts = array();
    $sql_params = array();
    foreach (static::$unique_columns as $unique_column)
    {
      $where_condition_parts[] = db()->quote($unique_column) . '=?';
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
