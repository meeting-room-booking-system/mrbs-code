<?php
namespace MRBS;
use PDO, PDOException;

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
function sql_mysqli_free ($sth, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $sth->closeCursor();
  unset($sth);
}


// Quote a table or column name (which could be a qualified identifier, eg 'table.column')
function sql_mysqli_quote($identifier)
{
  $quote_char = '`';
  $parts = explode('.', $identifier);
  return $quote_char . implode($quote_char . '.' . $quote_char, $parts) . $quote_char;
}


// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_mysqli_command ($sql, $params = array(), $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);
  
  $ret = -1;

  trigger_error($sql, E_USER_WARNING);
  trigger_error(print_r($params, true), E_USER_WARNING);

  $sth = $db_conn->prepare($sql);
  $sth->execute($params);
  trigger_error(print_r($sth->errorInfo(), true), E_USER_WARNING);
  if ($sth)
  {
    $ret = $sth->rowCount();
  }
  return $ret;
}


// Execute an SQL query which should return a single non-negative number value.
// This is a lightweight alternative to sql_query, good for use with count(*)
// and similar queries. It returns -1 on error or if the query did not return
// exactly one value, so error checking is somewhat limited.
// It also returns -1 if the query returns a single NULL value, such as from
// a MIN or MAX aggregate function applied over no rows.
function sql_mysqli_query1 ($sql, $params = array(), $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $sth = $db_conn->prepare($sql);
  if (!$sth)
  {
    trigger_error(mysql_mysqli_error($db_conn), E_USER_WARNING);
    return -1;
  }
  $sth->execute($params);

  if (($sth->rowCount() != 1) || ($sth->columnCount() != 1) ||
      (($row = $sth->fetch(PDO::FETCH_NUM)) == NULL))
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


// Execute an SQL query. Returns a database-dependent result handle,
// which should be passed back to sql_row or sql_row_keyed to get the results.
// Returns FALSE on error; use sql_error to get the error message.
function sql_mysqli_query ($sql, $params = array(), $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $sth = $db_conn->prepare($sql);
  $sth->execute($params);
  
  return $sth;
}


// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_mysqli_row ($sth, $i, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  if ($i >= $sth->rowCount())
  {
    $sth->closeCursor();
    return 0;
  }
  return $sth->fetch(PDO::FETCH_NUM, PDO::FETCH_ORI_ABS, $i);
}


// Return a row from a result as an associative array keyed by field name.
// The first row is 0.
// This is actually upward compatible with sql_row since the underlying
// routing also stores the data under number indexes.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
function sql_mysqli_row_keyed ($sth, $i, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  if ($i >= $sth->rowCount())
  {
    $sth->closeCursor();
    return 0;
  }
  return $sth->fetch(PDO::FETCH_ASSOC, PDO::FETCH_ORI_ABS, $i);
}


// Return the number of rows returned by a result handle from sql_query.
function sql_mysqli_count ($sth, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return $sth->rowCount();
}


// Return the value of an autoincrement field from the last insert.
// Must be called right after an insert on that table!
function sql_mysqli_insert_id($table, $field, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return $db_conn->lastInsertId();
}


// Return the text of the last error message.
function sql_mysqli_error($db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $error_info = $db_conn->errorInfo();

  return $error_info[2];
}


// Begin a transaction, if the database supports it. This is used to
// improve performance for multiple insert/delete/updates.
function sql_mysqli_begin($db_conn = null)
{
  $result = sql_mysqli_command("START TRANSACTION", array(), $db_conn);
  
  if ($result < 0)
  {
    trigger_error (sql_mysqli_error($db_conn), E_USER_WARNING);
  }
}


// Commit (end) a transaction. See sql_begin().
function sql_mysqli_commit($db_conn = null)
{
  $result = sql_mysqli_command("COMMIT", array(), $db_conn);
  
  if ($result < 0)
  {
    trigger_error (sql_mysqli_error($db_conn), E_USER_WARNING);
  }
}


// Rollback a transaction. See sql_begin().
function sql_mysqli_rollback($db_conn = null)
{
  $result = sql_mysqli_command("ROLLBACK", array(), $db_conn);
  
  if ($result < 0)
  {
    trigger_error (sql_mysqli_error($db_conn), E_USER_WARNING);
  }
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
  global $sql_mutex_unlock_name;
  
  sql_mysqli_ensure_handle($db_conn);

  // GET_LOCK returns 1 if the lock was obtained successfully, 0 if the attempt
  // timed out (for example, because another client has previously locked the name),
  // or NULL if an error occurred (such as running out of memory or the thread was
  // killed with mysqladmin kill)
  $sth = $db_conn->query("SELECT GET_LOCK('$name', 20)");
  if ($sth === FALSE)
  {
    trigger_error(sql_mysqli_error($db_conn), E_USER_WARNING);
    return FALSE;
  }

  if (($sth->rowCount() != 1) || 
      ($sth->columnCount() != 1) ||
      (($row = $sth->fetch(PDO::FETCH_NUM)) === NULL))
  {
    return FALSE;
  }
  else
  {
    $result = $row[0];
  }
  
  if ($result == 1)
  {
    $sql_mysqli_mutex_unlock_name = $name;
  }
  $sth->closeCursor();
  return $result;
}


// Release a mutual-exclusion lock on the named table. See sql_mutex_unlock.
function sql_mysqli_mutex_unlock($name, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  global $sql_mysqli_mutex_unlock_name;
  sql_mysqli_query1("SELECT RELEASE_LOCK('$name')", array(), $db_conn);
  $sql_mysqli_mutex_unlock_name = NULL;
}


// Shutdown function to clean up the connection. For internal use only.
function sql_mysqli_cleanup($db_conn = null)
{
  global $sql_mysqli_mutex_unlock_name;
  
  // Release any forgotten locks
  if (isset($sql_mysqli_mutex_unlock_name))
  {
    sql_mysqli_mutex_unlock($sql_mysqli_mutex_unlock_name, $db_conn);
  }
  
  // Rollback any outstanding transactions
  sql_mysqli_rollback($db_conn);
}


// Return a string identifying the database version:
function sql_mysqli_version($db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $r = sql_mysqli_query("select version()", array(), $db_conn);
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


// Returns the syntax for a case sensitive string "equals" function
// (By default MySQL is case insensitive, so we force a binary comparison)
//
// Also takes a required pass-by-reference parameter to modify the SQL
// parameters appropriately.
//
// NB:  This function is also assumed to do a strict comparison, ie
// take account of training spaces.  (The '=' comparison in MySQL allows
// trailing spaces, eg 'john' = 'john ').
function sql_mysqli_syntax_casesensitive_equals($fieldname, $string, &$params, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $params[] = $string;

  return " BINARY " . sql_mysqli_quote($fieldname) . "=?";
}

// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
//
// Also takes a required pass-by-reference parameter to modify the SQL
// parameters appropriately.
//
// In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
// requires quoting of % and _ in addition to the usual.
function sql_mysqli_syntax_caseless_contains($fieldname, $string, &$params, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $string = str_replace("\\", "\\\\", $string);
  $string = str_replace("%", "\\%", $string);
  $string = str_replace("_", "\\_", $string);

  $params[] = "%$string%";

  return " $fieldname LIKE ? ";
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


// Returns the syntax for a bitwise XOR operator
function sql_mysqli_syntax_bitwise_xor($db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  return "^";
}


// Returns the name of a field.
function sql_mysqli_field_name($result, $index, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);

  $finfo = $result->getColumnMeta($index);
  return $finfo[name];
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

  $res = sql_mysqli_query1("SHOW TABLES LIKE ?", array($table), $db_conn);
  
  return ($res == -1) ? FALSE : TRUE;
}


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
function sql_mysqli_field_info($table, $db_conn = null)
{
  sql_mysqli_ensure_handle($db_conn);
  
  // Map MySQL types on to a set of generic types
  $nature_map = array('bigint'    => 'integer',
                      'char'      => 'character',
                      'double'    => 'real',
                      'float'     => 'real',
                      'int'       => 'integer',
                      'mediumint' => 'integer',
                      'smallint'  => 'integer',
                      'text'      => 'character',
                      'tinyint'   => 'integer',
                      'tinytext'  => 'character',
                      'varchar'   => 'character');
  
  // Length in bytes of MySQL integer types                                        
  $int_bytes = array('bigint'    => 8, // bytes
                     'int'       => 4,
                     'mediumint' => 3,
                     'smallint'  => 2,
                     'tinyint'   => 1);
  
  $res = sql_mysqli_query("SHOW COLUMNS FROM $table", array(), $db_conn);
  if ($res === FALSE)
  {
    trigger_error(mysql_mysqi_error($db_conn), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }
  else
  {
    $fields = array();
    for ($i = 0; ($row = sql_mysqli_row_keyed($res, $i)); $i++)
    {
      $name = $row['Field'];
      $type = $row['Type'];
      // split the type (eg 'varchar(25)') around the opening '('
      $parts = explode('(', $type);
      // map the type onto one of the generic natures, if a mapping exists
      $nature = (array_key_exists($parts[0], $nature_map)) ? $nature_map[$parts[0]] : $parts[0];
      // now work out the length
      if ($nature == 'integer')
      {
        // if it's one of the ints, then look up the length in bytes
        $length = (array_key_exists($parts[0], $int_bytes)) ? $int_bytes[$parts[0]] : 0;
      }
      elseif ($nature == 'character')
      {
        // if it's a character type then use the length that was in parentheses
        // eg if it was a varchar(25), we want the 25
        if (isset($parts[1]))
        {
          $length = preg_replace('/\)/', '', $parts[1]);  // strip off the closing ')'
        }
        // otherwise it could be any length (eg if it was a 'text')
        else
        {
          $length = defined('PHP_INT_MAX') ? PHP_INT_MAX : 9999;
        }
      }
      else  // we're only dealing with a few simple cases at the moment
      {
        $length = NULL;
      }
      // Convert the is_nullable field to a boolean
      $is_nullable = (utf8_strtolower($row['Null']) == 'yes') ? TRUE : FALSE;
      
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
function sql_mysqli_connect($host, $username, $password,
                            $db_name, $persist = 0, $db_port = 3306)
{
  static $shutdown_registered = FALSE;
  
  if (!$shutdown_registered)
  {
    register_shutdown_function(__NAMESPACE__ . "\\sql_mysqli_cleanup");
    $shutdown_registered = TRUE;
  }
  
  if (empty($db_port))
  {
    $db_port = 3306;
  }

  // Establish a database connection.

  // On connection error, the message will be output without a proper HTML
  // header. There is no way I can see around this; if track_errors isn't on
  // there seems to be no way to supress the automatic error message output and
  // still be able to access the error text.

  try
  {
    $db_conn = new PDO("mysql:host=$host;port=$db_port;dbname=$db_name;charset=UTF8",
                       $username,
                       $password,
                       array(PDO::ATTR_PERSISTENT => ($persist ? true : false)));
  }
  catch (PDOException $e)
  {
    trigger_error($e->getMessage(), E_USER_WARNING);
    echo "\n<p>\n" . get_vocab("failed_connect_db") . "\n</p>\n";
    exit;
  }
  return $db_conn;
}


//
function sql_mysqli_default_connect()
{
  global $sql_mysqli_conn, $db_persist, $db_host, $db_login, $db_password,
         $db_database, $db_port;

  /////////////////////////////////////////////
  // Open the standard MRBS database connection

  $persist = !empty($db_persist);

  $port = NULL;
  // If a port is specified, cast it to int as mysqli doesn't accept
  // a string port specifier
  if (isset($db_port))
  {
    $port = (int)$db_port;
  }
  $sql_mysqli_conn = sql_mysqli_connect($db_host, $db_login, $db_password,
                                        $db_database, $persist, $port);
}


// Close a connection
function sql_mysqli_close($connection)
{
  unset($connection);
}

