<?php
// $Id$

// pgsql.inc - Simple PHP database support for PostgreSQL.
// The standard MRBS database connection utilises the following configuration
// variables:
//   $db_host = The hostname of the database server
//   $db_login = The username to use when connecting to the database
//   $db_password = The database account password
//   $db_database = The database name.

// This code hides an implementation difference in error reporting by the PHP
// PostgreSQL and MySQL extensions. PostgreSQL reports an E_WARNING error
// for some queries which MySQL does not; both properly set their own
// error code and the PHP error raised by PostgreSQL is not needed.
// The code here turns that off with error_reporting() calls around each
// pg_exec call, so as not to make you change the display_errors
// setting in your php.ini configuration file.


// A small utility function (not part of the DB abstraction API) to
// update a connection handle to the global MRBS connection handle
// if said handle is null/empty
function sql_pgsql_ensure_handle(&$db_conn)
{
  if (empty($db_conn))
  {
    global $sql_pgsql_conn;
    $db_conn = $sql_pgsql_conn;
  }
}


// Free a results handle. You need not call this if you call sql_row or
// sql_row_keyed until the row returns 0, since sql_row frees the results
// handle when you finish reading the rows.
function sql_pgsql_free ($r, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  pg_free_result($r);
}


// Escapes special characters in a string for use in an SQL statement
function sql_pgsql_escape($str, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);
  
  if (function_exists('pg_escape_string'))
  {
    return pg_escape_string($db_conn, $str);
  }
  else
  {
    return addslashes($str);
  }
}


// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_pgsql_command ($sql, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $e = error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));
  $r = pg_query($db_conn, $sql);
  error_reporting($e);
  if (! $r)
  {
    return -1;
  }
  $n = pg_affected_rows($r);
  pg_free_result($r);
  return $n;
}


// Execute an SQL query which should return a single non-negative number value.
// This is a lightweight alternative to sql_query, good for use with count(*)
// and similar queries. It returns -1 on error or if the query did not return
// exactly one value, so error checking is somewhat limited.
// It also returns -1 if the query returns a single NULL value, such as from
// a MIN or MAX aggregate function applied over no rows.
function sql_pgsql_query1 ($sql, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $e = error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));
  $r = pg_query($db_conn, $sql);
  error_reporting($e);
  if (! $r)
  {
    return -1;
  }
  if (pg_num_rows($r) != 1 || pg_num_fields($r) != 1
      || ($result = pg_fetch_result($r, 0, 0)) == "")
  {
    $result = -1;
  }
  pg_free_result($r);
  return $result;
}


// Execute an SQL query. Returns a database-dependent result handle,
// which should be passed back to sql_row or sql_row_keyed to get the results.
// Returns 0 on error; use sql_error to get the error message.
function sql_pgsql_query ($sql, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $e = error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));
  $r = pg_query($db_conn, $sql);
  error_reporting($e);
  return $r;
}


// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_pgsql_row ($r, $i, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  if ($i >= pg_num_rows($r))
  {
    pg_free_result($r);
    return 0;
  }
  return pg_fetch_row($r, $i);
}


// Return a row from a result as an associative array keyed by field name.
// The first row is 0.
// This is actually upward compatible with sql_row since the underlying
// routing also stores the data under number indexes.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
function sql_pgsql_row_keyed ($r, $i, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  if ($i >= pg_num_rows($r))
  {
    pg_free_result($r);
    return 0;
  }
  // Use _array() rather _assoc() to ensure support
  // for as many PHP versions as possible
  return pg_fetch_array($r, $i, PGSQL_ASSOC);
}


// Return the number of rows returned by a result handle from sql_query.
function sql_pgsql_count ($r, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return pg_num_rows($r);
}


// Return the value of an autoincrement field from the last insert.
// For PostgreSQL, this must be a SERIAL type field.
function sql_pgsql_insert_id($table, $field, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $seq_name = $table . "_" . $field . "_seq";
  return sql_pgsql_query1("select last_value from $seq_name", $db_conn);
}


// Return the text of the last error message.
function sql_pgsql_error($db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return pg_last_error($db_conn);
}


// Begin a transaction, if the database supports it. This is used to
// improve performance for multiple insert/delete/updates.
// There is no rollback support, since MySQL myisam tables don't support it.
function sql_pgsql_begin($db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  sql_pgsql_command("BEGIN", $db_conn);
}


// Commit (end) a transaction. See sql_begin().
function sql_pgsql_commit($db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  sql_pgsql_command("COMMIT", $db_conn);
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
// In PostgreSQL, the EXCLUSIVE mode lock excludes all but SELECT.
// It does not timeout, but waits forever for the lock.
function sql_pgsql_mutex_lock($name, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  global $sql_pgsql_mutex_shutdown_registered, $sql_pgsql_mutex_unlock_name;
  if ((sql_pgsql_command("BEGIN", $db_conn) < 0) ||
      (sql_pgsql_command("LOCK TABLE $name IN EXCLUSIVE MODE", $db_conn) < 0))
  {
    return 0;
  }
  $sql_pgsql_mutex_unlock_name = $name;
  if (empty($sql_pgsql_mutex_shutdown_registered))
  {
    register_shutdown_function("sql_pgsql_mutex_cleanup", $db_conn);
    $sql_pgsql_mutex_shutdown_registered = 1;
  }
  return 1;
}


// Release a mutual-exclusion lock on the named table. See sql_mutex_lock.
// In PostgreSQL, all locks are released by closing the transaction; there
// is no other way.
function sql_pgsql_mutex_unlock($name, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  global $sql_pgsql_mutex_unlock_name;
  sql_pgsql_command("COMMIT", $db_conn);
  $sql_pgsql_mutex_unlock_name = "";
}


// Shutdown function to clean up a forgotten lock. For internal use only.
function sql_pgsql_mutex_cleanup($db_conn)
{
  global $sql_pgsql_mutex_shutdown_registered, $sql_pgsql_mutex_unlock_name;
  if (!empty($sql_pgsql_mutex_unlock_name))
  {
    sql_pgsql_command("ABORT", $db_conn);
    $sql_pgsql_mutex_unlock_name = "";
  }
}


// Return a string identifying the database version:
function sql_pgsql_version($db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $r = sql_pgsql_query("select version()", $db_conn);
  $v = sql_pgsql_row($r, 0, $db_conn);
  sql_pgsql_free($r, $db_conn);

  return $v[0];
}


// Generate non-standard SQL for LIMIT clauses:
function sql_pgsql_syntax_limit($count, $offset, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return " LIMIT $count OFFSET $offset ";
}


// Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
function sql_pgsql_syntax_timestamp_to_unix($fieldname, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return " DATE_PART('epoch', $fieldname) ";
}


// Returns the syntax for a case sensitive string "equals" function
function sql_pgsql_syntax_casesensitive_equals($fieldname, $s, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return " \"$fieldname\"='" . sql_pgsql_escape($s) . "'";
}


// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
// In PostgreSQL, we can do case insensitive regexp with ~*, but not case
// insensitive LIKE matching.
// Quotemeta escapes everything we need except for single quotes.
function sql_pgsql_syntax_caseless_contains($fieldname, $s, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $s = quotemeta($s);
  $s = str_replace("'", "''", $s);
  return " $fieldname ~* '$s' ";
}


// Generate non-standard SQL to specify a column as an auto-incrementing
// integer while doing a CREATE TABLE
function sql_pgsql_syntax_createtable_autoincrementcolumn($db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return "serial";
}


// Returns the syntax for a bitwise XOR operator
function sql_pgsql_syntax_bitwise_xor($db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return "#";
}


// Returns the name of a field.
function sql_pgsql_field_name($result, $index, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return pg_field_name($result, $index);
}


// A map to turn pgsql extension field type names into our (well, the mysql
// extension's) field type strings
$sql_pgsql_type_map = array();
$sql_pgsql_type_map['bool'] = "int";
$sql_pgsql_type_map['int2'] = "int";
$sql_pgsql_type_map['int4'] = "int";
$sql_pgsql_type_map['int8'] = "int";
$sql_pgsql_type_map['numeric'] = "int";
$sql_pgsql_type_map['float4'] = "real";
$sql_pgsql_type_map['float8'] = "real";
$sql_pgsql_type_map['varchar'] = "string";
$sql_pgsql_type_map['text'] = "string";
$sql_pgsql_type_map['bpchar'] = "string";


// Returns the type of a field. (one of "int", "real", "string", "blob", etc...)
function sql_pgsql_field_type($result, $index, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  global $sql_pgsql_type_map;

  $type = pg_field_type($result, $index);

  return isset($sql_pgsql_type_map[$type]) ? $sql_pgsql_type_map[$type] : 'unknown';
}


// Returns the number of fields in a result.
function sql_pgsql_num_fields($result, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  return pg_num_fields($result);
}


// Check if a table exists
function sql_pgsql_table_exists($table, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);

  $res = sql_pgsql_query1("SELECT relname FROM pg_class ".
                          "WHERE relname = '".sql_pgsql_escape($table)."'");

  return ($res == -1) ? FALSE : TRUE;
}


// Get information about the columns in a table
// Returns an array with the following indices for each column
//
//  'name'        the column name
//  'type'        the type as reported by PostgreSQL
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
function sql_pgsql_field_info($table, $db_conn = null)
{
  sql_pgsql_ensure_handle($db_conn);
  
  // Map PostgreSQL types on to a set of generic types
  $nature_map = array('bigint'            => 'integer',
                      'boolean'           => 'boolean',
                      'bytea'             => 'binary',
                      'character'         => 'character',
                      'character varying' => 'character',
                      'double precision'  => 'real',
                      'integer'           => 'integer',
                      'real'              => 'real',
                      'smallint'          => 'integer',
                      'text'              => 'character');
  
  $res = sql_pgsql_query("SELECT column_name, data_type, numeric_precision,
                                 character_maximum_length, character_octet_length,
                                 is_nullable
                            FROM information_schema.columns
                           WHERE table_name ='$table'
                        ORDER BY ordinal_position");
  if ($res === FALSE)
  {
    trigger_error(pg_result_error($res), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }
  else
  {
    $fields = array();
    for ($i = 0; ($row = sql_pgsql_row_keyed($res, $i)); $i++)
    {
      $name = $row['column_name'];
      $type = $row['data_type'];
      // map the type onto one of the generic natures, if a mapping exists
      $nature = (array_key_exists($type, $nature_map)) ? $nature_map[$type] : $type;
      // Get a length value;  one of these values should be set
      if (isset($row['numeric_precision']))
      {
        $length = (int) floor($row['numeric_precision'] / 8);  // precision is in bits
      }
      elseif (isset($row['character_maximum_length']))
      {
        $length = $row['character_maximum_length'];
      }
      elseif (isset($row['character_octet_length']))
      {
        $length = $row['character_octet_length'];
      }
      // Convert the is_nullable field to a boolean
      $is_nullable = (strtolower($row['is_nullable']) == 'yes') ? TRUE : FALSE;
      
      $fields[$i]['name'] = $name;
      $fields[$i]['type'] = $type;
      $fields[$i]['nature'] = $nature;
      $fields[$i]['length'] = $length;
      $fields[$i]['is_nullable'] = $is_nullable;
    }
    return $fields;
  }
}


// Connect to a database server and select a database, optionally using
// persistent connections
function sql_pgsql_connect($host, $username, $password, $db_name,
                           $persist = 0)
{
  // Establish a database connection.

  // On connection error, the message will be output without a proper HTML
  // header. There is no way I can see around this; if track_errors isn't on
  // there seems to be no way to supress the automatic error message output and
  // still be able to access the error text.

  $conninfo = (empty($host) ? "" : "host=$host ") .
    "dbname=$db_name user=$username password=$password";

  if ($persist)
  {
    $db_conn = pg_pconnect($conninfo);
  }
  else
  {
    $db_conn = pg_connect($conninfo);
  }

  unset($conninfo);

  if (!$db_conn)
  {
    echo "\n<p>\n" . get_vocab("failed_connect_db") . "\n</p>\n";
    exit;
  }
  
  pg_set_client_encoding($db_conn, 'UTF8');
  
  return $db_conn;
}


//
function sql_pgsql_default_connect()
{
  global $sql_pgsql_conn, $db_nopersist, $db_host, $db_login, $db_password,
         $db_database;

  /////////////////////////////////////////////
  // Open the standard MRBS database connection

  $persist = 1;
  if (!empty($db_nopersist) && $db_nopersist)
  {
    $persist = 0;
  }
  global $sql_pgsql_conn;

  $sql_pgsql_conn = sql_pgsql_connect($db_host, $db_login, $db_password,
                                      $db_database, $persist);
}


// Close a connection
function sql_pgsql_close($connection)
{
  pg_close($connection);
}

?>
