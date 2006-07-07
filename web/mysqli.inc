<?php
// $Id$

// mysqli.inc - Simple PHP database support for MySQL, using mysqli extension.
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
  $r->close();
}

// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_command ($sql)
{
  global $mysqli;
  
  $ret = -1;

  if ($mysqli->query($sql)) {
    $ret = $mysqli->affected_rows();
  }
  return $ret;
}

// Execute an SQL query which should return a single non-negative number value.
// This is a lightweight alternative to sql_query, good for use with count(*)
// and similar queries. It returns -1 on error or if the query did not return
// exactly one value, so error checking is somewhat limited.
// It also returns -1 if the query returns a single NULL value, such as from
// a MIN or MAX aggregate function applied over no rows.
function sql_query1 ($sql)
{
    global $mysqli;
    
    $r = $mysqli->query($sql);
    if (! $r) return -1;
    if (($r->num_rows != 1) || ($r->field_count != 1) ||
        (($row = $r->fetch_row()) == NULL)) {
        $result = -1;
    }
    $r->close();
    return $row[0];
}

// Execute an SQL query. Returns a database-dependent result handle,
// which should be passed back to sql_row or sql_row_keyed to get the results.
// Returns 0 on error; use sql_error to get the error message.
function sql_query ($sql)
{
    global $mysqli;

    $r = $mysqli->query($sql);
    return $r;
}

// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_row ($r, $i)
{
    if ($i >= $r->num_rows) {
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
function sql_row_keyed ($r, $i)
{
    if ($i >= $r->num_rows()) {
        $r->close();
        return 0;
    }
    $r->data_seek($i);
    return $r->fetch_array();
}

// Return the number of rows returned by a result handle from sql_query.
function sql_count ($r)
{
    return $r->num_rows;
}

// Return the value of an autoincrement field from the last insert.
// Must be called right after an insert on that table!
function sql_insert_id($table, $field)
{
    global $mysqli;

    return $mysqli->insert_id;
}

// Return the text of the last error message.
function sql_error()
{
    global $mysqli;

    return $mysqli->error;
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
    if (!sql_query1("SELECT GET_LOCK('$name', 20)")) {
        return 0;
    }
    $sql_mutex_unlock_name = $name;
    if (empty($sql_mutex_shutdown_registered)) {
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
    if (!empty($sql_mutex_unlock_name)) {
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
    $finfo = $result->fetch_field_direct($index);
    return $finfo->name;
}

// Returns the type of a field. (one of "int", "real", "string", "blob", etc...)
function sql_field_type($result, $index)
{
    $finfo = $result->fetch_field_direct($index);
    return $finfo->type;
}

// Returns the number of fields in a result.
function sql_num_fields($result)
{
    return $result->field_count;
}



// Establish a database connection.
// On connection error, the message will be output without a proper HTML
// header. There is no way I can see around this; if track_errors isn't on
// there seems to be no way to supress the automatic error message output and
// still be able to access the error text.

$mysqli = new mysqli($db_host, $db_login, $db_password, $db_database);

/* check connection */
if (mysqli_connect_errno()) {
    echo "\n<p>\n" . get_vocab("failed_connect_db") . " : " . mysqli_connect_error();
    exit;
}

?>
