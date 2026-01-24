<?php
declare(strict_types=1);
namespace MRBS\DB;

use MRBS\Column;
use MRBS\Columns;
use MRBS\Exception;
use PDO;
use PDOException;
use Throwable;
use function MRBS\mrbs_ignore_user_abort;


abstract class DB
{
  const DB_SCHEMA_VERSION = 82;
  const DB_SCHEMA_VERSION_LOCAL = 1;

  const DB_DEFAULT_PORT = null;
  const DB_DBO_DRIVER = null;
  const DB_CHARSET = 'UTF8';

  protected $dbh = null;
  protected $mutex_locks = array();
  protected $version_string = null;


  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  abstract public function __construct(
    string $db_host,
    #[\SensitiveParameter]
    string $db_username,
    #[\SensitiveParameter]
    string $db_password,
    #[\SensitiveParameter]
    string $db_name,
    bool   $persist = false,
    ?int   $db_port = null,
    array  $db_options = []
  );


  // Destructor cleans up the connection if there is one
  public function __destruct()
  {
    try {
      // Release any forgotten locks
      $this->mutex_unlock_all();

      // Rollback any outstanding transactions
      $this->rollback();
    }
    catch (Throwable $e) {
      // Don't do anything, except raise an error.  This is the destructor and if we get an
      // exception or error it's probably because the connection has been lost or timed out,
      // in which case the locks will have been released and the transaction rolled back anyway.
      trigger_error($e->getMessage(), E_USER_NOTICE);
    }
  }


  // Build a DSN.
  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  public static function dsn(
    string $db_host,
    #[\SensitiveParameter]
    string $db_name,
    ?int   $db_port = null
  ) : string
  {
    // Early error handling
    if (is_null(static::DB_DBO_DRIVER) ||
        is_null(static::DB_DEFAULT_PORT)) {
      throw new Exception("Encountered a fatal bug in DB abstraction code!");
    }

    // Prefix
    $result = static::DB_DBO_DRIVER . ':';

    // Host
    if ($db_host !== '') {
      $result .= 'host=' . $db_host . ';';
    }

    // Port
    if (empty($db_port)) {
      $db_port = static::DB_DEFAULT_PORT;
    }
    $result .= 'port=' . $db_port . ';';

    // Database name
    $result .= 'dbname=' . $db_name;

    return $result;
  }


  // The SensitiveParameter attribute needs to be on a separate line for PHP 7.
  // The attribute is only recognised by PHP 8.2 and later.
  // $driver_options is an optional array of options that supplements/overrides the
  // default options.
  protected function connect(
    string $db_host,
    #[\SensitiveParameter]
    string $db_username,
    #[\SensitiveParameter]
    string $db_password,
    #[\SensitiveParameter]
    string $db_name,
    bool   $persist = false,
    ?int   $db_port = null,
    ?array $driver_options = null
  ): void
  {
    // Establish a database connection.
    $default_options = array(
      PDO::ATTR_PERSISTENT => $persist,
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    );
    // The LHS of the array + operator overrides the RHS if the keys are the same
    $options = (empty($driver_options)) ? $default_options : $driver_options + $default_options;

    $this->dbh = new PDO(
      static::dsn($db_host, $db_name, $db_port),
      $db_username,
      $db_password,
      $options
    );
    $this->command("SET NAMES '" . static::DB_CHARSET . "'");
  }


  //
  public function error(): string
  {
    $error = "No database connection!";

    if ($this->dbh) {
      $error_info = $this->dbh->errorInfo();
      $error = $error_info[2];
    }
    return $error;
  }


  public function getAttribute(int $attribute)
  {
    return $this->dbh->getAttribute($attribute);
  }


  /**
   * Execute a non-SELECT SQL command (insert/update/delete).
   *
   * @return int The number of tuples matched (whether affected or not) if OK (a number >= 0)
   * @throws DBException
   */
  public function command(string $sql, array $params = array()): int
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


  // Execute an SQL query which should return a single non-negative integer value.
  // This is a lightweight alternative to query(), good for use with count(*)
  // and similar queries.
  // It returns -1 if the query returns no result, or a single NULL value, such as from
  // a MIN or MAX aggregate function applied over no rows.
  // Throws a DBException on error.
  public function query1(string $sql, array $params = array()) : int
  {
    $result = $this->query_scalar_non_bool($sql, $params);

    if (is_null($result) || ($result === false))
    {
      return -1;
    }

    // Check that the result looks like an integer, even though it may be a string, and then cast
    // it to an integer.  For example "2" is OK, but "2.0" is not.
    $result = filter_var($result, FILTER_VALIDATE_INT);

    if ($result === false)
    {
      throw new \UnexpectedValueException("query1() should only be used for selecting integer values.");
    }

    return $result;
  }


  /**
   * Execute an SQL query which should return a single scalar value that can be anything
   * other than a boolean (because the function returns FALSE if there is no value).
   *
   * @return mixed The value returned by the query, or FALSE if there is none.
   * @throws DBException
   */
  public function query_scalar_non_bool(string $sql, array $params = [])
  {
    try
    {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
      return $sth->fetchColumn();
    }
    catch (PDOException $e)
    {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }
  }


  /**
   * Run an SQL query that returns a simple one-dimensional array of results.
   * The SQL query must select only one column.
   *
   * @return array The results, as an array of scalars, or an empty array if there are no results.
   * @throws DBException
   */
  public function query_array(string $sql, array $params = []): array
  {
    $stmt = $this->query($sql, $params);

    $result = [];

    while (false !== ($row = $stmt->next_row()))
    {
      $result[] = $row[0];
    }

    return $result;
  }


  /**
   * Execute an SQL query.
   *
   * @throws DBException
   */
  public function query(string $sql, array $params = []): DBStatement
  {
    try {
      $sth = $this->dbh->prepare($sql);
      $sth->execute($params);
    } catch (PDOException $e) {
      throw new DBException($e->getMessage(), 0, $e, $sql, $params);
    }

    return new DBStatement($this, $sth);
  }


  /**
   * Begin a transaction.  If already inside a transaction, this is a no-op.
   *
   * @see PDO::beginTransaction()
   */
  public function begin(): void
  {
    // Turn off ignore_user_abort until the transaction has been committed or rolled back.
    // See the warning at http://php.net/manual/en/features.persistent-connections.php
    // (Only applies to persistent connections, but we'll do it for all cases to keep
    // things simple)
    mrbs_ignore_user_abort(true);
    if (!$this->dbh->inTransaction()) {
      $this->dbh->beginTransaction();
    }
  }


  /**
   * Commit a transaction.  If not already inside a transaction, this is a no-op.
   *
   * @see PDO::commit()
   */
  public function commit(): void
  {
    if ($this->dbh->inTransaction()) {
      $this->dbh->commit();
    }
    mrbs_ignore_user_abort(false);
  }


  /**
   * Roll back a transaction.  If not already inside a transaction, this is a no-op.
   *
   * @see PDO::rollBack()
   */
  public function rollback(): void
  {
    if ($this->dbh && $this->dbh->inTransaction()) {
      $this->dbh->rollBack();
    }
    mrbs_ignore_user_abort(false);
  }


  // Checks if inside a transaction
  public function inTransaction(): bool
  {
    return $this->dbh->inTransaction();
  }


  // Dies with a message that the database version is lower than the minimum required
  protected function versionDie(string $database, string $this_version, string $min_version): void
  {
    $message = "MRBS requires $database version $min_version or higher. " .
      "This server is running version $this_version.";
    die($message);
  }


  // Returns the version string, eg "8.0.28",
  // "10.3.36-MariaDB-log-cll-lve" or
  // "PostgreSQL 14.2, compiled by Visual C++ build 1914, 64-bit".
  protected function versionString(): string
  {
    if (!isset($this->version_string)) {
      // Don't use getAttribute(PDO::ATTR_SERVER_VERSION) because that will
      // sometimes also give you the version prefix (so-called "replication
      // version hack") with MariaDB.
      $result = $this->query_scalar_non_bool("SELECT VERSION()");

      $this->version_string = ($result === false) ? '' : $result;
    }

    return $this->version_string;
  }


  // Replaces the keys in the array $array according to $key_map.  Elements with
  // value NULL are dropped.
  protected static function replaceOptionKeys(array $array, array $key_map): array
  {
    $result = array();

    foreach ($array as $key => $value) {
      if (isset($value)) {
        if (array_key_exists($key, $key_map)) {
          $result[$key_map[$key]] = $value;
        }
        else {
          trigger_error("Unsupported database driver option '$key'");
        }
      }
    }

    return $result;
  }


  // Return a boolean depending on whether $field exists in $table
  public function field_exists(string $table, string $field): bool
  {
    $rows = $this->field_info($table);
    foreach ($rows as $row) {
      if ($row['name'] === $field) {
        return true;
      }
    }
    return false;
  }


  // Checks whether a table has duplicate values for a field
  public function tableHasDuplicates(string $table, string $field): bool
  {
    $sql = "SELECT $field, COUNT(*)
              FROM $table
          GROUP BY $field
            HAVING COUNT(*) > 1";
    $res = $this->query($sql);
    return ($res->count() > 0);
  }

  // Quote a table or column name (which could be a qualified identifier, eg 'table.column')
  abstract public function quote(string $identifier): string;

  // Return the value of an autoincrement field from the last insert.
  // Must be called right after an insert on that table!
  abstract public function insert_id(string $table, string $field) : int;

  // Acquire a mutual-exclusion lock.
  // Returns true if the lock is acquired successfully, otherwise false.
  abstract public function mutex_lock(string $name): bool;

  // Release a mutual-exclusion lock.
  // Returns true if the lock is released successfully, otherwise false.
  abstract public function mutex_unlock(string $name): bool;

  // Release all mutual-exclusion locks.
  abstract public function mutex_unlock_all(): void;

  // Return a string identifying the database version and type
  abstract public function version(): string;

  // Check if a table exists
  abstract public function table_exists(string $table): bool;

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
  abstract public function field_info(string $table): array;

  // Syntax methods

  /**
   * Generate the SQL for LIMIT clauses.
   */
  abstract public function syntax_limit(int $count, int $offset): string;

  /**
   * Generate the SQL for converting a TIMESTAMP to a Unix timestamp.
   */
  abstract public function syntax_timestamp_to_unix(string $fieldname): string;

  /**
   * Generate the SQL for a case-sensitive string "equals" function.
   *
   * NB: This method is assumed to do a strict comparison, eg take account of trailing spaces.
   *
   * @param array &$params The SQL parameters, which will be modified by this function.
   */
  abstract public function syntax_casesensitive_equals(string $fieldname, string $string, array &$params): string;

  /**
   * Generate the SQL for a case-insensitive string "contains" function.
   *
   * @param string $string The (unescaped) string to search for.
   * @param array &$params The SQL parameters, which will be modified by this function.
   */
  abstract public function syntax_caseless_contains(string $fieldname, string $string, array &$params): string;

  /**
   * Generate the SQL to add a table column after another specified column.
   */
  abstract public function syntax_addcolumn_after(string $fieldname): string;

  /**
   * Generate the SQL to specify a column as an auto-incrementing integer while doing a CREATE TABLE.
   */
  abstract public function syntax_createtable_autoincrementcolumn(): string;

  /**
   * Generate the SQL for a bitwise XOR operator.
   */
  abstract public function syntax_bitwise_xor(): string;

  /**
   * Generate the syntax for a column being in a list of values.
   */
  public function syntax_in_list(string $column_name, array $list, array &$params) : string
  {
    // Empty lists aren't allowed.
    if (count($list) === 0)
    {
      return 'FALSE';
    }

    $params = array_merge($params, $list);

    return $this->quote($column_name) . " IN (" . implode(',', array_fill(0, count($list), '?')) . ")";
  }

  /**
   * Generate the SQL for a simple split of a column's value into two parts, separated by a delimiter.  Note: this
   * function assumes there is only one occurrence of the delimiter in the column's value.
   *
   * @param int $part The part to return, either 1 for the text to the left of the delimiter, or 2 for the text to the right.
   * @param array $params The SQL parameters, which will be modified by this function.
   */
  abstract public function syntax_simple_split(string $fieldname, string $delimiter, int $part, array &$params): string;

  /**
   * Generate the SQL for aggregating a number of rows as a delimited string.
   */
  abstract public function syntax_group_array_as_string(string $fieldname, string $delimiter = ','): string;

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
    bool $has_id_column=false
  ) : string;


  // Determines whether the driver returns native types (eg a PHP int
  // for an SQL INT).
  abstract public function returnsNativeTypes() : bool;

  // Determines whether the database supports multiple locks
  abstract public function supportsMultipleLocks(): bool;

  /**
   * Constructs an SQL upsert (insert or update) query based on the provided data and parameters.
   *
   * Unfortunately, getting the id of the last row differs between MySQL and PostgreSQL.   In PostgreSQL the query will
   * return a row with the id in the 'id' column.  However, there isn't a corresponding way of doing this in MySQL, but
   * db()->insert_id() will work, regardless of whether an insert or update was performed.
   *
   * @param array $data An associative array of data to be inserted or updated, indexed by column name.
   * @param string $table The table name where the data should be inserted or updated.
   * @param array &$params A reference to an array where the generated SQL parameters will be stored.
   * @param array|string $conflict_keys A list of column names or a single column name that will be used to detect conflicts (e.g., unique constraints).
   * @param array $ignore_columns A list of columns to be excluded from the query.
   * @param bool $has_id_column Indicates whether the table includes an ID column that requires special handling.
   * @return string The constructed SQL upsert query string.
   */
  public function syntax_upsert(array $data, string $table, array &$params, $conflict_keys=[], array $ignore_columns=[], bool $has_id_column = false): string
  {
    if (is_scalar($conflict_keys))
    {
      $conflict_keys = array($conflict_keys);
    }

    list('columns' => $columns, 'values' => $values, 'sql_params' => $params) = $this->prepareData($data, $table, $ignore_columns);
    $quoted_columns = array_map(array(\MRBS\db(), 'quote'), $columns);
    $sql = "INSERT INTO " . $this->quote($table) . "
                        (" . implode(', ', $quoted_columns) . ")
                 VALUES (" . implode(', ', $values) . ") ";

    // Go through the columns we've just found and turn them into assignments
    // for the update part
    $assignments = array();
    for ($i=0; $i<count($columns); $i++)
    {
      $column = $columns[$i];
      $value = $values[$i];
      $assignments[] = $this->quote($column) . "=$value";
    }

    $sql .= \MRBS\db()->syntax_on_duplicate_key_update(
      $conflict_keys,
      $assignments,
      $has_id_column
    );

    return $sql;
  }


  // Prepares $data for an SQL query. If $table is given then it will also sanitize values,
  // eg by trimming and truncating strings and converting booleans into 0/1.
  private function prepareData(array $data, ?string $table=null, array $ignore_columns=[]): array
  {
    $columns = array();
    $values = array();
    $sql_params = array();

    $cols = (isset($table)) ? Columns::getInstance($table) : array_keys($data);

    $i = 0;
    foreach ($cols as $col)
    {
      // We are only interested in those elements of $data that have a corresponding
      // column in the table - except for those that we have been told to ignore.
      // Examples might be 'id' which normally auto-increments, and 'timestamp' which
      // normally auto-updates.
      if (is_object($col) && in_array($col->name, $ignore_columns))
      {
        continue;
      }

      $column_name = (is_object($col)) ? $col->name : $col;
      $columns[] = $column_name;

      if (!isset($data[$column_name]) && (!is_object($col) || $col->getIsNullable()))
      {
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
        if (isset($data[$column_name]))
        {
          $sql_param = $data[$column_name];
          if (is_object($col))
          {
            // NB MariaDB doesn't support the JSON data type.  It treats it as an
            // alias of LONG TEXT.
            if ($col->getNature() === Column::NATURE_JSON)
            {
              if (!is_string($sql_param) || !json_validate($sql_param))
              {
                throw new Exception('Invalid JSON string');
              }
            }
            else
            {
              $sql_param = $col->sanitizeValue($sql_param);
            }
          }
        }
        else
        {
          // The column is not nullable and $col is an object if we got here
          $sql_param = $col->getDefault();
        }
        $sql_params[$named_parameter] = $sql_param;
        $i++;
      }
    }

    return array(
      'columns' => $columns,
      'values' => $values,
      'sql_params' => $sql_params
    );
  }

}
