<?php
// $Id$

/* pgsql.inc - Simple PHP database support for PostgreSQL.
   Include this file after database.inc.php.
   This file contains specific DBMS features/commands not handled by MDB
   database wrapper.
 */


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
    global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name, $mdb;
    if (MDB::isError($mdb->autoCommit(FALSE))
        || MDB::isError($mdb->query("LOCK TABLE $name IN EXCLUSIVE MODE")))
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


// Return a string identifying the database version:
function sql_version()
{
    global $mdb;
    $v = $mdb->queryOne("select version()");
    return $v;
}


/**
 * sql_syntax_caseless_contains().
 *
 * Generate non-standard SQL to match a string anywhere in a field's value
 * in a case insensitive manner.
 * In PostgreSQL, we can do case insensitive regexp with ~*, but not case
 * insensitive LIKE matching.
 * Quotemeta escapes everything we need except for single quotes.
 *
 * @param    string    $fieldname    field to apply regexp
 * @param    string    $s            un-escaped/un-slashed string to search for
 * @return   string    fieldname and escaped/slashed string
 */
function sql_syntax_caseless_contains($fieldname, $s)
{
    $s = quotemeta($s);
    $s = str_replace("'", "''", $s);
    return " $fieldname ~* " . $mdb->getTextValue("$s") . " ";
}

?>