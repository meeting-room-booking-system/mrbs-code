<?php

namespace MRBS;

use PDO;
use PDOException;


abstract class DB
{
  const DB_SCHEMA_VERSION = 86;
  const DB_SCHEMA_VERSION_LOCAL = 1;

  const DB_DEFAULT_PORT = null;
  const DB_DBO_DRIVER = null;
  const DB_CHARSET = 'UTF8';

  protected $dbh = null;
  protected $mutex_lock_name;


  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  abstract   public function __construct(
    string $db_host,
    #[SensitiveParameter]
    string $db_username,
    #[SensitiveParameter]
    string $db_password,
    #[SensitiveParameter]
    string $db_name,
    bool $persist=false,
    ?int $db_port=null);


  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  protected function connect(
    string $db_host,
    #[SensitiveParameter]
    string $db_username,
    #[SensitiveParameter]
    string $db_password,
    #[SensitiveParameter]
    string $db_name,
    bool $persist=false,
    ?int $db_port=null)
  {
    // Early error handling
    if (is_null(static::DB_DBO_DRIVER) ||
        is_null(static::DB_DEFAULT_PORT))
    {
      throw new Exception("Encountered a fatal bug in DB abstraction code!");
    }

    // If no port has been provided, set a SQL variant dependent default
    if (empty($db_port))
    {
      $db_port = static::DB_DEFAULT_PORT;
    }

    // Establish a database connection.
    if (!isset($db_host) || ($db_host == ""))
    {
      $hostpart = "";
    }
    else
    {
      $hostpart = "host=$db_host;";
    }
    $this->dbh = new PDO(static::DB_DBO_DRIVER.":{$hostpart}port=$db_port;dbname=$db_name",
                         $db_username,
                         $db_password,
                         array(PDO::ATTR_PERSISTENT => (bool) $persist,
                               PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION));
    $this->command("SET NAMES '".static::DB_CHARSET."'");
  }


  // Output our own message to avoid giving away the database credentials
  protected function connectError($message)
  {
    trigger_error($message, E_USER_WARNING);
    fatal_error(get_vocab('fatal_db_error'));
  }

  //
  public function error() : string
  {
    $error = "No database connection!";

    if ($this->dbh)
    {
      $error_info = $this->dbh->errorInfo();
      $error = $error_info[2];
    }
    return $error;
  }


  // Execute a non-SELECT SQL command (insert/update/delete).
  // Returns the number of tuples affected if OK (a number >= 0).
  // Throws a DBException on error.
  public function command(string $sql, array $params = array()) : int
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    return $sth->rowCount();
  }


  // Execute an SQL query which should return a single non-negative number value.
  // This is a lightweight alternative to query(), good for use with count(*)
  // and similar queries.
  // It returns -1 if the query returns no result, or a single NULL value, such as from
  // a MIN or MAX aggregate function applied over no rows.
  // Throws a DBException on error.
  function query1(string $sql, array $params = array())
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    if ($sth->rowCount() > 1)
    {
      throw new DBException("query1() returned more than one row.", 0, null, $sql, $params);
    }

    if ($sth->columnCount() > 1)
    {
      throw new DBException("query1() returned more than one column.", 0, null, $sql, $params);
    }

    $row = $sth->fetch(PDO::FETCH_NUM);
    if (($row === null) || ($row === false))
    {
      $result = -1;
    }
    else
    {
      $result = $row[0];
    }
    $sth->closeCursor();
    return $result;
  }


  // Run an SQL query that returns a simple one dimensional array of results.
  // The SQL query must select only one column.   Returns an empty array if
  // no results; throws a DBException if there's an error
  public function query_array(string $sql, array $params = array()) : array
  {
    $stmt = $this->query($sql, $params);

    $result = array();

    while (false !== ($row = $stmt->next_row()))
    {
      $result[] = $row[0];
    }

    return $result;
  }


  // Execute an SQL query. Returns a DBStatement object, a class with a number
  // of methods like row() and row_keyed() to get the results.
  // Throws a DBException on error
  public function query ($sql, array $params = array())
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    return new DBStatement($this, $sth);
  }


  //
  public function begin()
  {
    // Turn off ignore_user_abort until the transaction has been committed or rolled back.
    // See the warning at http://php.net/manual/en/features.persistent-connections.php
    // (Only applies to persistent connections, but we'll do it for all cases to keep
    // things simple)
    mrbs_ignore_user_abort(TRUE);
    if (!$this->dbh->inTransaction())
    {
      $this->dbh->beginTransaction();
    }
  }


  // Commit (end) a transaction. See begin().
  public function commit()
  {
    if ($this->dbh->inTransaction())
    {
      $this->dbh->commit();
    }
    mrbs_ignore_user_abort(FALSE);
  }


  // Roll back a transaction, aborting it. See begin().
  public function rollback()
  {
    if ($this->dbh && $this->dbh->inTransaction())
    {
      $this->dbh->rollBack();
    }
    mrbs_ignore_user_abort(FALSE);
  }


  // Checks if inside a transaction
  public function inTransaction()
  {
    return $this->dbh->inTransaction();
  }


  // Return a boolean depending on whether $field exists in $table
  public function field_exists($table, $field)
  {
    $rows = $this->field_info($table);
    foreach ($rows as $row)
    {
      if ($row['name'] === $field)
      {
        return true;
      }
    }
    return false;
  }


  // Checks whether a table has duplicate values for a field
  public function tableHasDuplicates($table, $field)
  {
    $sql = "SELECT $field, COUNT(*)
              FROM $table
          GROUP BY $field
            HAVING COUNT(*) > 1";
    $res = $this->query($sql);
    return ($res->count() > 0);
  }

  // Quote a table or column name (which could be a qualified identifier, eg 'table.column')
  abstract public function quote($identifier);

  // Return the value of an autoincrement field from the last insert.
  // Must be called right after an insert on that table!
  abstract public function insert_id($table, $field);

  // Acquire a mutual-exclusion lock on the named table. For portability:
  // This will not lock out SELECTs.
  // It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
  // It will lock out other callers of this routine with the same name argument.
  // It will timeout in 20 seconds and return false.
  // It returns true when the lock has been acquired.
  // Caller must release the lock with mutex_unlock().
  // Caller must not have more than one mutex at any time.
  // Do not mix this with begin()/end() calls.
  abstract public function mutex_lock($name);

  // Release a mutual-exclusion lock on the named table.
  // Returns true if the lock is released successfully, otherwise false
  abstract public function mutex_unlock($name);

  // Destructor cleans up the connection
  abstract public function __destruct();

  // Return a string identifying the database version
  abstract public function version();

  // Check if a table exists
  abstract public function table_exists($table);

  // Get information about the columns in a table
  // Returns an array with the following indices for each column
  //
  //  'name'        the column name
  //  'type'        the type as reported by MySQL
  //  'nature'      the type mapped onto one of a generic set of types
  //                (boolean, integer, real, character, binary).   This enables
  //                the nature to be used by MRBS code when deciding how to
  //                display fields, without MRBS having to worry about the
  //                differences between MySQL and PostgreSQL type names.
  //  'length'      the maximum length of the field in bytes, octets or characters
  //                (Note:  this could be NULL)
  //  'is_nullable' whether the column can be set to NULL (boolean)
  //
  //  NOTE: the type mapping is incomplete and just covers the types commonly
  //  used by MRBS
  abstract public function field_info($table);

  // Generate non-standard SQL for LIMIT clauses:
  abstract public function syntax_limit($count, $offset);

  // Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
  abstract public function syntax_timestamp_to_unix($fieldname);

  // Returns the syntax for a case sensitive string "equals" function
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  //
  // NB:  This function is also assumed to do a strict comparison, ie
  // take account of trailing spaces.
  abstract public function syntax_casesensitive_equals($fieldname, $string, &$params);

  // Generate non-standard SQL to match a string anywhere in a field's value
  // in a case insensitive manner. $s is the un-escaped/un-slashed string.
  //
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  abstract public function syntax_caseless_contains($fieldname, $string, &$params);

  // Generate non-standard SQL to add a table column after another specified
  // column
  abstract public function syntax_addcolumn_after($fieldname);

  // Generate non-standard SQL to specify a column as an auto-incrementing
  // integer while doing a CREATE TABLE
  abstract public function syntax_createtable_autoincrementcolumn();

  // Returns the syntax for a bitwise XOR operator
  abstract public function syntax_bitwise_xor();

  // Returns the syntax for a simple split of a column's value into two
  // parts, separated by a delimiter.  $part can be 1 or 2.
  // Also takes a required pass-by-reference parameter to modify the SQL
  // parameters appropriately.
  abstract public function syntax_simple_split($fieldname, $delimiter, $part, &$params);

  // Returns the syntax for aggregating a number of rows as a delimited string
  abstract public function syntax_group_array_as_string($fieldname, $delimiter=',');

  // Converts the result of syntax_group_array_as_string() into an array
  public function convert_string_to_array($string, $delimiter=',')
  {
    // If there were no rows MySQL will return NULL and PostgreSQL ''
    return (isset($string) && ($string !== '')) ? explode($delimiter, $string) : array();
  }

  // Returns the syntax for an "upsert" query.  Unfortunately getting the id of the
  // last row differs between MySQL and PostgreSQL.   In PostgreSQL the query will
  // return a row with the id in the 'id' column.  However there isn't a corresponding
  // way of doing this in MySQL, but db()->insert_id() will work, regardless of whether
  // an insert or update was performed.
  //
  //  $conflict_keys     the key(s) which is/are unique; can be a scalar or an array
  //  $assignments       an array of assignments for the UPDATE clause
  //  $has_id_column     whether the table has an id column
  abstract public function syntax_on_duplicate_key_update(
      $conflict_keys,
      array $assignments,
      $has_id_column=false
    );

}
