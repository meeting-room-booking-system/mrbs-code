<?php
// $Id$

// mysql.inc - Simple PHP database support for MySQL.
// Include this file after defining the following variables:
//   $db_host = The hostname of the database server
//   $db_login = The username to use when connecting to the database
//   $db_password = The database account password
//   $db_database = The database name.
// Including this file connects you to the database, or exits on error.


// Free a results handle. You need not call this if you call sql_row or
// sql_row_keyed until the row returns 0, since sql_row frees the results
// handle when you finish reading the rows.
function sql_free ($r)
{
  mysql_free_result($r);
}

// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_command ($sql)
{
  global $db_c;

  if (mysql_query($sql, $db_c))
  {
    return mysql_affected_rows($db_c);
  }
  return -1;
}

// Execute an SQL query which should return a single non-negative number value.
// This is a lightweight alternative to sql_query, good for use with count(*)
// and similar queries. It returns -1 on error or if the query did not return
// exactly one value, so error checking is somewhat limited.
// It also returns -1 if the query returns a single NULL value, such as from
// a MIN or MAX aggregate function applied over no rows.
function sql_query1 ($sql)
{
  global $db_c;

  $r = mysql_query($sql, $db_c);
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
function sql_query ($sql)
{
  global $db_c;

  $r = mysql_query($sql, $db_c);
  return $r;
}

// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_row ($r, $i)
{
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
function sql_row_keyed ($r, $i)
{
  if ($i >= mysql_num_rows($r))
  {
    mysql_free_result($r);
    return 0;
  }
  mysql_data_seek($r, $i);
  return mysql_fetch_array($r);
}

// Return the number of rows returned by a result handle from sql_query.
function sql_count ($r)
{
  return mysql_num_rows($r);
}

// Return the value of an autoincrement field from the last insert.
// Must be called right after an insert on that table!
function sql_insert_id($table, $field)
{
  global $db_c;

  return mysql_insert_id($db_c);
}

// Return the text of the last error message.
function sql_error()
{
  global $db_c;

  return mysql_error($db_c);
}

// Begin a transaction, if the database supports it. This is used to
// improve PostgreSQL performance for multiple insert/delete/updates.
// There is no rollback support, since MySQL doesn't support it.
function sql_begin()
{
}

// Commit (end) a transaction. See sql_begin().
function sql_commit()
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
function sql_mutex_lock($name)
{
  global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name;
  if (!sql_query1("SELECT GET_LOCK('$name', 20)"))
  {
    return 0;
  }
  $sql_mutex_unlock_name = $name;
  if (empty($sql_mutex_shutdown_registered))
  {
    register_shutdown_function("sql_mutex_cleanup");
    $sql_mutex_shutdown_registered = 1;
  }
  return 1;
}

// Release a mutual-exclusion lock on the named table. See sql_mutex_unlock.
function sql_mutex_unlock($name)
{
  global $sql_mutex_unlock_name;
  sql_query1("SELECT RELEASE_LOCK('$name')");
  $sql_mutex_unlock_name = "";
}

// Shutdown function to clean up a forgotten lock. For internal use only.
function sql_mutex_cleanup()
{
  global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name;
  if (!empty($sql_mutex_unlock_name))
  {
    sql_mutex_unlock($sql_mutex_unlock_name);
    $sql_mutex_unlock_name = "";
  }
}


// Return a string identifying the database version:
function sql_version()
{
  $r = sql_query("select version()");
  $v = sql_row($r, 0);
  sql_free($r);
  return "MySQL $v[0]";
}


// Generate non-standard SQL for LIMIT clauses:
function sql_syntax_limit($count, $offset)
{
  return " LIMIT $offset,$count ";
}

// Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
function sql_syntax_timestamp_to_unix($fieldname)
{
  return " UNIX_TIMESTAMP($fieldname) ";
}

// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
// In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
// requires quoting of % and _ in addition to the usual.
function sql_syntax_caseless_contains($fieldname, $s)
{
  $s = str_replace("\\", "\\\\", $s);
  $s = str_replace("%", "\\%", $s);
  $s = str_replace("_", "\\_", $s);
  $s = str_replace("'", "''", $s);
  return " $fieldname LIKE '%$s%' ";
}

// Returns the name of a field.
function sql_field_name($result, $index)
{
  return mysql_field_name($result, $index);
}

// Returns the type of a field. (one of "int", "real", "string", "blob", etc...)
function sql_field_type($result, $index)
{
  return mysql_field_type($result, $index);
}

// Returns the number of fields in a result.
function sql_num_fields($result)
{
  return mysql_num_fields($result);
}



// Establish a database connection.
// On connection error, the message will be output without a proper HTML
// header. There is no way I can see around this; if track_errors isn't on
// there seems to be no way to supress the automatic error message output and
// still be able to access the error text.
if (empty($db_nopersist))
{
  $db_c = mysql_pconnect($db_host, $db_login, $db_password);
}
else
{
  $db_c = mysql_connect($db_host, $db_login, $db_password);
}

if (!$db_c || !mysql_select_db ($db_database, $db_c))
{
  echo "\n<p>\n" . get_vocab("failed_connect_db") . "\n</p>\n";
  exit;
}

?>
