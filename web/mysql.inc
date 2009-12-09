<?php
// $Id$

// mysql.inc - Simple PHP database support for MySQL.
// The standard MRBS database connection utilises the following configuration
// variables:
//   $db_host = The hostname of the database server
//   $db_login = The username to use when connecting to the database
//   $db_password = The database account password
//   $db_database = The database name.


// A small utility function (not part of the DB abstraction API) to
// update a connection handle to the global MRBS connection handle
// if said handle is null/empty
function sql_mysql_ensure_handle(&$db_conn)
{
  if (empty($db_conn))
  {
    global $sql_mysql_conn;
    $db_conn = $sql_mysql_conn;
  }
}


// Free a results handle. You need not call this if you call sql_row or
// sql_row_keyed until the row returns 0, since sql_row frees the results
// handle when you finish reading the rows.
function sql_mysql_free ($r, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  mysql_free_result($r);
}


// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_mysql_command ($sql, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  if (mysql_query($sql, $db_conn))
  {
    return mysql_affected_rows($db_conn);
  }
  return -1;
}


// Execute an SQL query which should return a single non-negative number value.
// This is a lightweight alternative to sql_query, good for use with count(*)
// and similar queries. It returns -1 on error or if the query did not return
// exactly one value, so error checking is somewhat limited.
// It also returns -1 if the query returns a single NULL value, such as from
// a MIN or MAX aggregate function applied over no rows.
function sql_mysql_query1 ($sql, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  $r = mysql_query($sql, $db_conn);
  if (! $r)
  {
    return -1;
  }
  if (mysql_num_rows($r) != 1 || mysql_num_fields($r) != 1
      || ($result = mysql_result($r, 0, 0)) == "")
  {
    $result = -1;
  }
  mysql_free_result($r);
  return $result;
}


// Execute an SQL query. Returns a database-dependent result handle,
// which should be passed back to sql_row or sql_row_keyed to get the results.
// Returns 0 on error; use sql_error to get the error message.
function sql_mysql_query ($sql, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  $r = mysql_query($sql, $db_conn);
  return $r;
}


// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_mysql_row ($r, $i, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  if ($i >= mysql_num_rows($r))
  {
    mysql_free_result($r);
    return 0;
  }
  mysql_data_seek($r, $i);
  return mysql_fetch_row($r);
}


// Return a row from a result as an associative array keyed by field name.
// The first row is 0.
// This is actually upward compatible with sql_row since the underlying
// routing also stores the data under number indexes.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
function sql_mysql_row_keyed ($r, $i, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  if ($i >= mysql_num_rows($r))
  {
    mysql_free_result($r);
    return 0;
  }
  mysql_data_seek($r, $i);
  return mysql_fetch_array($r);
}


// Return the number of rows returned by a result handle from sql_query.
function sql_mysql_count ($r, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return mysql_num_rows($r);
}


// Return the value of an autoincrement field from the last insert.
// Must be called right after an insert on that table!
function sql_mysql_insert_id($table, $field, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return mysql_insert_id($db_conn);
}


// Return the text of the last error message.
function sql_mysql_error($db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return mysql_error($db_conn);
}


// Begin a transaction, if the database supports it. This is used to
// improve PostgreSQL performance for multiple insert/delete/updates.
// There is no rollback support, since MySQL doesn't support it.
function sql_mysql_begin($db_conn = null)
{
}


// Commit (end) a transaction. See sql_begin().
function sql_mysql_commit($db_conn = null)
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
function sql_mysql_mutex_lock($name, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  global $sql_mysql_mutex_shutdown_registered, $sql_mysql_mutex_unlock_name;
  if (!sql_mysql_query1("SELECT GET_LOCK('$name', 20)", $db_conn))
  {
    return 0;
  }
  $sql_mysql_mutex_unlock_name = $name;
  if (empty($sql_mysql_mutex_shutdown_registered))
  {
    register_shutdown_function("sql_mysql_mutex_cleanup", $db_conn);
    $sql_mysql_mutex_shutdown_registered = 1;
  }
  return 1;
}


// Release a mutual-exclusion lock on the named table. See sql_mutex_unlock.
function sql_mysql_mutex_unlock($name, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  global $sql_mysql_mutex_unlock_name;
  sql_mysql_query1("SELECT RELEASE_LOCK('$name')", $db_conn);
  $sql_mysql_mutex_unlock_name = "";
}


// Shutdown function to clean up a forgotten lock. For internal use only.
function sql_mysql_mutex_cleanup($db_conn)
{
  global $sql_mysql_mutex_shutdown_registered, $sql_mysql_mutex_unlock_name;
  if (!empty($sql_mysql_mutex_unlock_name))
  {
    sql_mysql_mutex_unlock($sql_mysql_mutex_unlock_name, $db_conn);
    $sql_mysql_mutex_unlock_name = "";
  }
}


// Return a string identifying the database version:
function sql_mysql_version($db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  $r = sql_mysql_query("select version()", $db_conn);
  $v = sql_mysql_row($r, 0, $db_conn);
  sql_mysql_free($r, $db_conn);
  return "MySQL $v[0]";
}


// Generate non-standard SQL for LIMIT clauses:
function sql_mysql_syntax_limit($count, $offset, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return " LIMIT $offset,$count ";
}


// Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
function sql_mysql_syntax_timestamp_to_unix($fieldname, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return " UNIX_TIMESTAMP($fieldname) ";
}


// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
// In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
// requires quoting of % and _ in addition to the usual.
function sql_mysql_syntax_caseless_contains($fieldname, $s, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  $s = str_replace("\\", "\\\\", $s);
  $s = str_replace("%", "\\%", $s);
  $s = str_replace("_", "\\_", $s);
  $s = str_replace("'", "''", $s);
  return " $fieldname LIKE '%$s%' ";
}


// Generate non-standard SQL to add a table column after another specified
// column
function sql_mysql_syntax_addcolumn_after($fieldname, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return "AFTER $fieldname";
}


// Generate non-standard SQL to specify a column as an auto-incrementing
// integer while doing a CREATE TABLE
function sql_mysql_syntax_createtable_autoincrementcolumn($db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);
  
  return "int NOT NULL auto_increment";
}


// Returns the name of a field.
function sql_mysql_field_name($result, $index, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return mysql_field_name($result, $index);
}


// Returns the type of a field. (one of "int", "real", "string", "blob", etc...)
function sql_mysql_field_type($result, $index, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return mysql_field_type($result, $index);
}


// Returns the number of fields in a result.
function sql_mysql_num_fields($result, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  return mysql_num_fields($result);
}


// Check if a table exists
function sql_mysql_table_exists($table, $db_conn = null)
{
  sql_mysql_ensure_handle($db_conn);

  $res = sql_mysql_query1("SHOW TABLES LIKE '".addslashes($table)."'");

  return ($res == -1) ? FALSE : TRUE;
}


// Connect to a database server and select a database, optionally using
// persistent connections
function sql_mysql_connect($host, $username, $password, $db_name, $persist = 0)
{
  global $unicode_encoding;

  // Establish a database connection.

  // On connection error, the message will be output without a proper HTML
  // header. There is no way I can see around this; if track_errors isn't on
  // there seems to be no way to supress the automatic error message output and
  // still be able to access the error text.

  if ($persist)
  {
    $db_conn = mysql_pconnect($host, $username, $password);
  }
  else
  {
    $db_conn = mysql_connect($host, $username, $password);
  }

  if (!$db_conn || !mysql_select_db ($db_name, $db_conn))
  {
    echo "\n<p>\n" . get_vocab("failed_connect_db") . "\n</p>\n";
    exit;
  }
  if ($unicode_encoding)
  {
    if (function_exists('mysql_set_charset'))
    {
      mysql_set_charset('utf8', $db_conn);
    }
    else
    {
      mysql_query("SET NAMES 'utf8'", $db_conn);
    }
  }
  return $db_conn;
}


//
function sql_mysql_default_connect()
{
  global $sql_mysql_conn, $db_nopersist, $db_host, $db_login, $db_password,
         $db_database;

  /////////////////////////////////////////////
  // Open the standard MRBS database connection

  $persist = 1;
  if (!empty($db_nopersist) && $db_nopersist)
  {
    $persist = 0;
  }

  $sql_mysql_conn = sql_mysql_connect($db_host, $db_login, $db_password,
                                      $db_database, $persist);
}


// Close a connection
function sql_mysql_close($connection)
{
  mysql_close($connection);
}

?>
