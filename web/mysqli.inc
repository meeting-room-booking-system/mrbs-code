<?php
// $Id$

// mysqli.inc - Simple PHP database support for MySQL, using mysqli extension.
// The standard MRBS database connection utilises the following configuration
// variables:
//   $db_host = The hostname of the database server
//   $db_login = The username to use when connecting to the database
//   $db_password = The database account password
//   $db_database = The database name.


// A small utility function (not part of the DB abstraction API) to
// update a connection handle to the global MRBS connection handle
// if said handle is null/empty
function sql_mysqli_ensure_handle(&$db_conn)
{
  if (empty($db_conn))
  {
    global $sql_mysqli_conn;
    $db_conn = $sql_mysqli_conn;
  }
}


// Free a results handle. You need not call this if you call sql_row or
// sql_row_keyed until the row returns 0, since sql_row frees the results
// handle when you finish reading the rows.
function sql_mysqli_free ($r, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $r->close();
}


// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_mysqli_command ($sql, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);
  
  $ret = -1;

  if ($db_conn->query($sql))
  {
    $ret = $db_conn->affected_rows;
  }
  return $ret;
}


// Execute an SQL query which should return a single non-negative number value.
// This is a lightweight alternative to sql_query, good for use with count(*)
// and similar queries. It returns -1 on error or if the query did not return
// exactly one value, so error checking is somewhat limited.
// It also returns -1 if the query returns a single NULL value, such as from
// a MIN or MAX aggregate function applied over no rows.
function sql_mysqli_query1 ($sql, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $r = $db_conn->query($sql);
  if (! $r)
  {
    return -1;
  }

  if (($r->num_rows != 1) || ($r->field_count != 1) ||
      (($row = $r->fetch_row()) == NULL))
  {
    $result = -1;
  }
  else
  {
    $result = $row[0];
  }
  $r->close();
  return $result;
}


// Execute an SQL query. Returns a database-dependent result handle,
// which should be passed back to sql_row or sql_row_keyed to get the results.
// Returns 0 on error; use sql_error to get the error message.
function sql_mysqli_query ($sql, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $r = $db_conn->query($sql);
  return $r;
}


// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_mysqli_row ($r, $i, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  if ($i >= $r->num_rows)
  {
    $r->close();
    return 0;
  }
  $r->data_seek($i);
  return $r->fetch_row();
}


// Return a row from a result as an associative array keyed by field name.
// The first row is 0.
// This is actually upward compatible with sql_row since the underlying
// routing also stores the data under number indexes.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
function sql_mysqli_row_keyed ($r, $i, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  if ($i >= $r->num_rows)
  {
    $r->close();
    return 0;
  }
  $r->data_seek($i);
  return $r->fetch_array();
}


// Return the number of rows returned by a result handle from sql_query.
function sql_mysqli_count ($r, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return $r->num_rows;
}


// Return the value of an autoincrement field from the last insert.
// Must be called right after an insert on that table!
function sql_mysqli_insert_id($table, $field, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return $db_conn->insert_id;
}


// Return the text of the last error message.
function sql_mysqli_error($db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return $db_conn->error;
}


// Begin a transaction, if the database supports it. This is used to
// improve PostgreSQL performance for multiple insert/delete/updates.
// There is no rollback support, since MySQL doesn't support it.
function sql_mysqli_begin($db_conn = null)
{
}


// Commit (end) a transaction. See sql_begin().
function sql_mysqli_commit($db_conn = null)
{
}


// Acquire a mutual-exclusion lock on the named table. For portability:
// This will not lock out SELECTs.
// It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
// It will lock out other callers of this routine with the same name argument.
// It may timeout in 20 seconds and return 0, or may wait forever.
// It returns 1 when the lock has been acquired.
// Caller must release the lock with sql_mutex_unlock().
// Caller must not have more than one mutex at any time.
// Do not mix this with sql_begin()/sql_end() calls.
//
// In MySQL, we avoid table locks, and use low-level locks instead.
function sql_mysqli_mutex_lock($name, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name;
  if (!sql_mysqli_query1("SELECT GET_LOCK('$name', 20)", $db_conn))
  {
    return 0;
  }
  $sql_mysqli_mutex_unlock_name = $name;
  if (empty($sql_mysqli_mutex_shutdown_registered))
  {
    register_shutdown_function("sql_mysqli_mutex_cleanup", $db_conn);
    $sql_mysqli_mutex_shutdown_registered = 1;
  }
  return 1;
}


// Release a mutual-exclusion lock on the named table. See sql_mutex_unlock.
function sql_mysqli_mutex_unlock($name, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  global $sql_mysqli_mutex_unlock_name;
  sql_mysqli_query1("SELECT RELEASE_LOCK('$name')", $db_conn);
  $sql_mysqli_mutex_unlock_name = "";
}


// Shutdown function to clean up a forgotten lock. For internal use only.
function sql_mysqli_mutex_cleanup($db_conn = null)
{
  global $sql_mysqli_mutex_shutdown_registered, $sql_mysqli_mutex_unlock_name;
  if (!empty($sql_mysqli_mutex_unlock_name))
  {
    sql_mysqli_mutex_unlock($sql_mysqli_mutex_unlock_name, $db_conn);
    $sql_mysqli_mutex_unlock_name = "";
  }
}


// Return a string identifying the database version:
function sql_mysqli_version($db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $r = sql_mysqli_query("select version()", $db_conn);
  $v = sql_mysqli_row($r, 0, $db_conn);
  sql_mysqli_free($r, $db_conn);

  return "MySQL $v[0]";
}


// Generate non-standard SQL for LIMIT clauses:
function sql_mysqli_syntax_limit($count, $offset, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return " LIMIT $offset,$count ";
}


// Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
function sql_mysqli_syntax_timestamp_to_unix($fieldname, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return " UNIX_TIMESTAMP($fieldname) ";
}


// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
// In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
// requires quoting of % and _ in addition to the usual.
function sql_mysqli_syntax_caseless_contains($fieldname, $s, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $s = str_replace("\\", "\\\\", $s);
  $s = str_replace("%", "\\%", $s);
  $s = str_replace("_", "\\_", $s);
  $s = str_replace("'", "''", $s);
  return " $fieldname LIKE '%$s%' ";
}


// Generate non-standard SQL to add a table column after another specified
// column
function sql_mysqli_syntax_addcolumn_after($fieldname, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return "AFTER $fieldname";
}


// Generate non-standard SQL to specify a column as an auto-incrementing
// integer while doing a CREATE TABLE
function sql_mysqli_syntax_createtable_autoincrementcolumn($db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return "int NOT NULL auto_increment";
}

// Returns the name of a field.
function sql_mysqli_field_name($result, $index, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $finfo = $result->fetch_field_direct($index);
  return $finfo->name;
}


// A map to turn mysqli extension field type numbers into our (well, the mysql
// extension's) field type strings
$sql_mysqli_type_map = array();
$sql_mysqli_type_map[0] = "int";      // DECIMAL
$sql_mysqli_type_map[1] = "int";      // TINYINT
$sql_mysqli_type_map[2] = "int";      // SMALLINT
$sql_mysqli_type_map[3] = "int";      // INTEGER
$sql_mysqli_type_map[4] = "real";     // FLOAT
$sql_mysqli_type_map[5] = "real";     // DOUBLE
$sql_mysqli_type_map[8] = "int";      // BIGINT
$sql_mysqli_type_map[9] = "int";      // MEDIUMINT
$sql_mysqli_type_map[246] = "int";    // DECIMAL
$sql_mysqli_type_map[247] = "string"; // ENUM
$sql_mysqli_type_map[252] = "string"; // BLOB
$sql_mysqli_type_map[253] = "string"; // VARCHAR
$sql_mysqli_type_map[254] = "string"; // CHAR


// Returns the type of a field. (one of "int", "real", "string", "blob", etc...)
function sql_mysqli_field_type($result, $index, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  global $sql_mysqli_type_map;

  $finfo = $result->fetch_field_direct($index);
  return isset($sql_mysqli_type_map[$finfo->type]) ? $sql_mysqli_type_map[$finfo->type] : 'unknown';
}


// Returns the number of fields in a result.
function sql_mysqli_num_fields($result, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return $result->field_count;
}


// Check if a table exists
function sql_mysqli_table_exists($table, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $res = sql_mysqli_query1("SHOW TABLES LIKE '".addslashes($table)."'");

  return ($res == -1) ? FALSE : TRUE;
}


// Connect to a database server and select a database, optionally using
// persistent connections
function sql_mysqli_connect($host, $username, $password,
                            $db_name, $persist = 0)
{
  // Establish a database connection.

  // On connection error, the message will be output without a proper HTML
  // header. There is no way I can see around this; if track_errors isn't on
  // there seems to be no way to supress the automatic error message output and
  // still be able to access the error text.

  // PHP 5.3.0 and above support persistent connections in mysqli
  if ($persist && (version_compare(PHP_VERSION, '5.3.0') === 1))
  {
    $full_host = "p:$host";
  }
  else
  {
    $full_host = $host;
  }
  
  $db_conn = new mysqli($full_host, $username, $password, $db_name);

  /* check connection */
  if (mysqli_connect_errno())
  {
    echo "\n<p>\n" . get_vocab("failed_connect_db") . " : " . mysqli_connect_error() . "\n</p>\n";
    exit;
  }
  return $db_conn;
}


//
function sql_mysqli_default_connect()
{
  global $sql_mysqli_conn, $db_nopersist, $db_host, $db_login, $db_password,
         $db_database;

  /////////////////////////////////////////////
  // Open the standard MRBS database connection

  $persist = 1;
  if (!empty($db_nopersist) && $db_nopersist)
  {
    $persist = 0;
  }

  $sql_mysqli_conn = sql_mysqli_connect($db_host, $db_login, $db_password,
                                        $db_database, $persist);
}


// Close a connection
function sql_mysqli_close($connection)
{
  mysqli_close($connection);
}

?>
