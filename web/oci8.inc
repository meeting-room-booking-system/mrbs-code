<?php
// $Id$

/* oci8.inc.php - Simple PHP database support for Oracle.
   Include this file after database.inc.php.
   This file contains specific DBMS features/commands not handled by MDB
   database wrapper.
 */


// Acquire a mutual-exclusion lock on the named table.
// Prerequisites : The table or view must be in your own schema or you must
// have the LOCK ANY TABLE system privilege, or you must have any object
// privilege on the table or view.
// For portability:
// This will not lock out SELECTs.
// It lock out DELETE/UPDATE/INSERT.
// A locked table remains locked until you either commit your transaction or
// roll it back, either entirely or to a savepoint before you locked the table.
// It will lock out other callers of this routine with the same name argument.
// Oracle waits until the table is available, locks it, and returns control
// to you.
// It returns 1 when the lock has been acquired.
// Caller must release the lock with sql_mutex_unlock().
// Caller must not have more than one mutex at any time.
// Do not mix this with $mdb->autoCommit()/$mdb->commit() calls.
//
// In Oracle, the EXCLUSIVE allows queries on the locked table but
// prohibits any other activity on it.
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
    $v = $mdb->queryOne('SELECT version FROM V$INSTANCE');
    return $v;
}


/**
 * sql_syntax_caseless_contains().
 *
 * Generate non-standard SQL to match a string anywhere in a field's value
 * in a case insensitive manner.
 * In Oracle, case is significant in all conditions comparing character
 * expressions including the LIKE and equality (=) operators. You can use the
 * UPPER function to perform a case-insensitive match, as in this condition:
 * UPPER(ename) LIKE 'SM%'
 *
 * @param    string    $fieldname    field to apply regexp
 * @param    string    $s            un-escaped/un-slashed string to search for
 * @return   string    fieldname and escaped/slashed string
 * @todo     This function have not been tested
 */
function sql_syntax_caseless_contains($fieldname, $s)
{
    $s = strtoupper($s);
    $s = str_replace("\\", "\\\\", $s);
    $s = str_replace("%", "\\%", $s);
    $s = str_replace("_", "\\_", $s);
    $s = str_replace("'", "''", $s);
    return " UPPER($fieldname) LIKE '%$s%' ESCAPE '\'";
}

?>