<?php

// $Id$

global $dbsys;

// These variables specify the names of the tables in the database
global $db_tbl_prefix;

$tbl_area      = $db_tbl_prefix . "area";
$tbl_entry     = $db_tbl_prefix . "entry";
$tbl_repeat    = $db_tbl_prefix . "repeat";
$tbl_room      = $db_tbl_prefix . "room";
$tbl_users     = $db_tbl_prefix . "users";
$tbl_variables = $db_tbl_prefix . "variables";


$db_schema_version = 24;
$local_db_schema_version = 1;


// Include the abstraction configured to be used for the default MRBS
// database
require_once "${dbsys}.inc";


// All the sql_* functions below apart from sql_connect(), sql_default_connect()
// and sql_close() and take an optional final argument which is
// the MRBS database handle to use. If this is not passed, the default
// MRBS database connection is used.


// Free a result object
function sql_free($r)
{
  global $dbsys;

  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_free";
  $f($r, $db_conn);
}


// Run an SQL query that doesn't produce results
function sql_command($sql)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_command";
  return $f($sql, $db_conn);
}


// Run an SQL query that returns only one result - returns the result
// directly. SQL query must select only one column and one row.
function sql_query1($sql)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_query1";
  return $f($sql, $db_conn);
}


// Run a SQL query, returns a result object
function sql_query($sql)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_query";
  return $f($sql, $db_conn);
}


// Return one row from a result object, as a numbered array
function sql_row($r, $i)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_row";
  return $f($r, $i, $db_conn);
}


// Return one row from a result object, as an array keyed on the column
// name
function sql_row_keyed($r, $i)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_row_keyed";
  return $f($r, $i, $db_conn);
}


// Return the number of rows in a result object
function sql_count($r)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_count";
  return $f($r, $db_conn);
}


// Get the last ID inserted into a table
function sql_insert_id($table, $field)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_insert_id";
  return $f($table, $field, $db_conn);
}


// Return the last SQL error for a connection
function sql_error()
{
  if (func_num_args() > 0)
  {
    $handle = func_get_arg(0);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_error";
  return $f($db_conn);
}


// Start a transaction
function sql_begin()
{
  if (func_num_args() > 0)
  {
    $handle = func_get_arg(0);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_begin";
  $f($db_conn);
}


// Finish a transaction
function sql_commit()
{
  if (func_num_args() > 0)
  {
    $handle = func_get_arg(0);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_commit";
  $f($db_conn);
}


// Get a mutex lock
function sql_mutex_lock($name)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_mutex_lock";
  return $f($name, $db_conn);
}


// Return a mutex lock
function sql_mutex_unlock($name)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_mutex_unlock";
  $f($name, $db_conn);
}


// Return the SQL database version for the connection
function sql_version()
{
  if (func_num_args() > 0)
  {
    $handle = func_get_arg(0);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_version";
  return $f($db_conn);
}


// Returns the syntax for a limit statement
function sql_syntax_limit($count, $offset)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_syntax_limit";
  return $f($count, $offset, $db_conn);
}


// Returns the syntax for converting a timestamp into a "UNIX timestamp"
function sql_syntax_timestamp_to_unix($fieldname)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_syntax_timestamp_to_unix";
  return $f($fieldname, $db_conn);
}


// Returns the syntax for a caseless "contains" function
function sql_syntax_caseless_contains($fieldname, $s)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_syntax_caseless_contains";
  return $f($fieldname, $s, $db_conn);
}

// Returns the syntax for adding a column after another column, in an
// ALTER TABLE command
function sql_syntax_addcolumn_after($fieldname)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_syntax_addcolumn_after";

  if (function_exists($f))
  {
    return $f($fieldname, $db_conn);
  }
  else
  {
    return "";
  }
}


// Returns the syntax for a caseless "contains" function
function sql_syntax_createtable_autoincrementcolumn()
{
  if (func_num_args() > 0)
  {
    $handle = func_get_arg(0);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_syntax_createtable_autoincrementcolumn";
  return $f($db_conn);
}


// Returns the syntax for a bitwise XOR operator
function sql_syntax_bitwise_xor()
{
  if (func_num_args() > 0)
  {
    $handle = func_get_arg(0);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_syntax_bitwise_xor";
  return $f($db_conn);
}


// Return the name of a column in a result object
function sql_field_name($result, $index)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_field_name";
  return $f($result, $index, $db_conn);
}


// Return the type of a column in a result object
function sql_field_type($result, $index)
{
  if (func_num_args() > 2)
  {
    $handle = func_get_arg(2);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_field_type";
  return $f($result, $index, $db_conn);
}


// Return the number of columns in a result object
function sql_num_fields($result)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_num_fields";
  return $f($result, $db_conn);
}


// Check if a table exists - returns TRUE if it exists, FALSE if it doesn't
function sql_table_exists($table)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }

  $f = "sql_${db_sys}_table_exists";
  return $f($table, $db_conn);
}


// This function returns information about columns in the database in a manner
// that is independent of the underlying database.    It was originally designed
// for use by MRBS pages that have user defined columns in the relevant table
// and to enable them to decide how to represent the column on a page.  For example
// a smallint is often used to represent a boolean, which should be represented by a
// checkbox. (Smallints are used by MRBS for booleans in PostgreSQL because booleans return
// a PHP boolean type, rather than a 0 or 1 which MySQL does, and so this makes testing
// of the results difficult).
//
// Returns an array with the following indices for each column
//
//  'name'        the column name
//  'type'        the type as reported by MySQL or PostgreSQL
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
// 
// The difficulty is that MySQL and PostgreSQL return different formats
// of answer for the type of field when using sql_field_type.    For example
// for a smallint, PostgreSQL will just return int, and there is no exact equivalent
// of the mysql_ field_ len() function.   Although pg_field_size is a close
// approximation and returns 2 for a smallint, enabling it to be distinguished
// from an int, when used with variable length character types such as text or 
// varying character it returns -1.
//
// [There must be a better way of doing all this???!]
function sql_field_info($table)
{
  if (func_num_args() > 1)
  {
    $handle = func_get_arg(1);
    $db_sys = $handle['system'];
    $db_conn = $handle['connection'];
  }
  else
  {
    global $dbsys;

    $db_sys = $dbsys;
    $db_conn = null;
  }
  
  $f = "sql_${db_sys}_field_info";
  return $f($table, $db_conn);
}


// Connect to a database host and select the supplied database
function sql_connect($system, $host, $username, $password, $db_name,
                     $persist = 0)
{
  require_once "$system.inc";

  $f = "sql_${system}_connect";

  $db_conn = $f($host, $username, $password, $db_name, $persist);

  $handle = array('system' => $system, 'connection' => $db_conn);

  return $handle;
}


// Close a database connection that was previously opened by sql_connect()
function sql_close($handle)
{
  $system = $handle['system'];
  require_once "$system.inc";
  $f = "sql_${system}_close";
  $f($handle['connection']);
}


// Get a database username and password
function db_get_userpass()
{
  global $PHP_SELF;
  print_header(0, 0, 0, 0, "");
  ?>
  <form class="form_general" id="db_logon" method="post" action="<?php echo htmlspecialchars(basename($PHP_SELF)) ?>">
  <fieldset>
  <legend><?php echo get_vocab("database_login") ?></legend>
    <div>
      <label for="form_username">Database username</label>
      <input id="form_username" name="form_username" type="text">
    </div>
    <div>
      <label for="form_password">Database password</label>
      <input id="form_password" name="form_password" type="password">
    </div>
    <div id="db_logon_submit">
      <input class="submit" type="submit" value=" <?php echo get_vocab('login') ?>">
    </div>
  </fieldset>
  </form>
  <?php
  // Print footer and exit
  print_footer(TRUE);
}


//////////////////////////////////////////
// Connect to the configured MRBS database

$f = "sql_${dbsys}_default_connect";
$f();

// Default version is 0, before we had schema versions
$current_db_schema_version = 0;
$current_local_db_schema_version = 0;

if (sql_table_exists($tbl_variables))
{
  $current_db_schema_version = sql_query1("SELECT variable_content ".
                                          "FROM $tbl_variables ".
                                          "WHERE variable_name = 'db_version'");
  $current_local_db_schema_version = sql_query1("SELECT variable_content ".
                                                "FROM $tbl_variables ".
                                                "WHERE variable_name = 'local_db_version'");                                            
  if ($current_local_db_schema_version < 0)
  {
    $current_local_db_schema_version = 0;
  }
}

// If either of the database schema version numbers are out of date, then 
// upgrade the database - provided of course that the entry table exists.
// (We check that the entry table exists just in case this is a new installation
// and they've forgotten to create the database tables)
if ((($current_db_schema_version < $db_schema_version) || 
     ($current_local_db_schema_version < $local_db_schema_version)) &&
    sql_table_exists($tbl_entry))
{
  // Upgrade needed

  require_once "functions.inc";
  require_once "upgrade.inc";

  // Just use a simple header as the normal header may (a) use features
  // which are not available until after the database upgrade or (b) use
  // functions which are not available until after dbsys has run.
  print_simple_header();
 
  echo "<h1>" . get_vocab("mrbs") . "</h1>\n";
  echo "<p class=\"error\">" . get_vocab("upgrade_required") . "</p>\n"; 

  // We need to open a connection to the database with a database
  // username that has admin rights.
  while (empty($admin_handle))
  {
    $db_admin_username = get_form_var('form_username', 'string');
    $db_admin_password = get_form_var('form_password', 'string');
    if (!isset($db_admin_username) || !isset($db_admin_password))
    {
      // Get a username and password if we haven't got them
      echo "<p>" . get_vocab("supply_userpass") . "</p>\n";
      echo "<p>" . get_vocab("contact_admin") . "</p>\n"; 
      db_get_userpass();
    }
    else
    {
      // Turn off error reporting for the database connection because
      // we don't want to see the system error reports if the user
      // supplies an incorrect username and password.
      $old_error_reporting = error_reporting(0);
      $admin_handle = sql_connect($dbsys, $db_host, $db_admin_username, $db_admin_password, $db_database, 0);
      error_reporting($old_error_reporting);  // Turn error reporting back on
    }
  }

  $ok = TRUE;

  // Do any MRBS upgrades first
  if ($current_db_schema_version < $db_schema_version)
  {
    $ok = upgrade_database(FALSE, $current_db_schema_version, $db_schema_version, $admin_handle);
  }
  // Then any local upgrades
  if ($ok && $current_local_db_schema_version < $local_db_schema_version)
  {
    $ok = upgrade_database(TRUE, $current_local_db_schema_version, $local_db_schema_version, $admin_handle);
  }
  
  // close the database connection that has admin rights
  sql_close($admin_handle);

  if ($ok)
  {
    echo "<p>" . get_vocab("upgrade_completed") . "</p>\n";
  }
  echo "<a href=\"./\">" . get_vocab("returncal") . "</a>.";

  print_footer(TRUE);
}
