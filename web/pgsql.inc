<?php
// $Id$

// pgsql.inc - Simple PHP database support for PostgreSQL.
// Include this file after defining the following variables:
//   $db_host = The hostname of the database server
//   $db_login = The username to use when connecting to the database
//   $db_password = The database account password
//   $db_database = The database name.
// Including this file connects you to the database, or exits on error.
// This code hides an implementation difference in error reporting by the PHP
// PostgreSQL and MySQL extensions. PostgreSQL reports an E_WARNING error
// for some queries which MySQL does not; both properly set their own
// error code and the PHP error raised by PostgreSQL is not needed.
// The code here turns that off with error_reporting() calls around each
// pg_exec call, so as not to make you change the display_errors
// setting in your php.ini configuration file.


// Free a results handle. You need not call this if you call sql_row or
// sql_row_keyed until the row returns 0, since sql_row frees the results
// handle when you finish reading the rows.
function sql_free ($r)
{
	pg_freeresult($r);
}

// Execute a non-SELECT SQL command (insert/update/delete).
// Returns the number of tuples affected if OK (a number >= 0).
// Returns -1 on error; use sql_error to get the error message.
function sql_command ($sql)
{
	global $db_c;
	$e = error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));
	$r = pg_exec($db_c, $sql);
	error_reporting($e);
	if (! $r) return -1;
	$n = pg_cmdtuples($r);
	pg_freeresult($r);
	return $n;
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
	$e = error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));
	$r = pg_exec($db_c, $sql);
	error_reporting($e);
	if (! $r) return -1;
	if (pg_numrows($r) != 1 || pg_numfields($r) != 1
		|| ($result = pg_result($r, 0, 0)) == "") $result = -1;
	pg_freeresult($r);
	return $result;
}

// Execute an SQL query. Returns a database-dependent result handle,
// which should be passed back to sql_row or sql_row_keyed to get the results.
// Returns 0 on error; use sql_error to get the error message.
function sql_query ($sql)
{
	global $db_c;
	$e = error_reporting(E_ALL & ~(E_WARNING|E_NOTICE));
	$r = pg_exec($db_c, $sql);
	error_reporting($e);
	return $r;
}

// Return a row from a result. The first row is 0.
// The row is returned as an array with index 0=first column, etc.
// When called with i >= number of rows in the result, cleans up from
// the query and returns 0.
// Typical usage: $i = 0; while ((a = sql_row($r, $i++))) { ... }
function sql_row ($r, $i)
{
	if ($i >= pg_numrows($r))
	{
		pg_freeresult($r);
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
function sql_row_keyed ($r, $i)
{
	if ($i >= pg_numrows($r))
	{
		pg_freeresult($r);
		return 0;
	}
	return pg_fetch_array($r, $i);
}

// Return the number of rows returned by a result handle from sql_query.
function sql_count ($r)
{
	return pg_numrows($r);
}

// Return the value of an autoincrement field from the last insert.
// For PostgreSQL, this must be a SERIAL type field.
function sql_insert_id($table, $field)
{
	$seq_name = $table . "_" . $field . "_seq";
	return sql_query1("select last_value from $seq_name");
}

// Return the text of the last error message.
function sql_error()
{
	global $db_c;
	return pg_errormessage($db_c);
}

// Begin a transaction, if the database supports it. This is used to
// improve PostgreSQL performance for multiple insert/delete/updates.
// There is no rollback support, since MySQL doesn't support it.
function sql_begin()
{
	sql_command("BEGIN");
}

// Commit (end) a transaction. See sql_begin().
function sql_commit()
{
	sql_command("COMMIT");
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
function sql_mutex_lock($name)
{
	global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name;
	if (sql_command("BEGIN") < 0
		|| sql_command("LOCK TABLE $name IN EXCLUSIVE MODE") < 0) return 0;
	$sql_mutex_unlock_name = $name;
	if (empty($sql_mutex_shutdown_registered))
	{
		register_shutdown_function("sql_mutex_cleanup");
		$sql_mutex_shutdown_registered = 1;
	}
	return 1;
}

// Release a mutual-exclusion lock on the named table. See sql_mutex_lock.
// In PostgreSQL, all locks are released by closing the transaction; there
// is no other way.
function sql_mutex_unlock($name)
{
	global $sql_mutex_unlock_name;
	sql_command("COMMIT");
	$sql_mutex_unlock_name = "";
}

// Shutdown function to clean up a forgotten lock. For internal use only.
function sql_mutex_cleanup()
{
	global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name;
	if (!empty($sql_mutex_unlock_name))
	{
		sql_command("ABORT");
		$sql_mutex_unlock_name = "";
	}
}

// Return a string identifying the database version:
function sql_version()
{
	$r = sql_query("select version()");
	$v = sql_row($r, 0);
	sql_free($r);
	return $v[0];
}

// Generate non-standard SQL for LIMIT clauses:
function sql_syntax_limit($count, $offset)
{
	return " LIMIT $count OFFSET $offset ";
}

// Generate non-standard SQL to output a TIMESTAMP as a Unix-time:
function sql_syntax_timestamp_to_unix($fieldname)
{
	return " DATE_PART('epoch', $fieldname) ";
}

// Generate non-standard SQL to match a string anywhere in a field's value
// in a case insensitive manner. $s is the un-escaped/un-slashed string.
// In PostgreSQL, we can do case insensitive regexp with ~*, but not case
// insensitive LIKE matching.
// Quotemeta escapes everything we need except for single quotes.
function sql_syntax_caseless_contains($fieldname, $s)
{
	$s = quotemeta($s);
	$s = str_replace("'", "''", $s);
	return " $fieldname ~* '$s' ";
}

// Establish a database connection.
// On connection error, the message will be output without a proper HTML
// header. There is no way I can see around this; if track_errors isn't on
// there seems to be no way to supress the automatic error message output and
// still be able to access the error text.
$conninfo = (empty($db_host) ? "" : "host=$db_host ")
	. "dbname=$db_database user=$db_login password=$db_password";
if (empty($db_nopersist))
	$db_c = pg_pconnect($conninfo);
else
	$db_c = pg_connect($conninfo);
unset($conninfo);

if (!$db_c)
{
	echo "\n<p>\n" . get_vocab("failed_connect_db") . "\n";
	exit;
}

?>
