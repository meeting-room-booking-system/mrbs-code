<?php
// $Id$

/* fbsql.inc - Simple PHP database support for FrontBase.
   Include this file after database.inc.php.
   This file contains specific DBMS features/commands not handled by MDB
   database wrapper.
 */


// Acquire a mutual-exclusion lock on the named table. For portability:
// This will not lock out SELECTs.
// It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
// It will lock out other callers of this routine with the same name argument.
// In Frontbase the amount of data that is locked is reflected by the
// isolation level. SERIALIZABLE locks the whole table. Row locks are
// not escalated to table locks at any point.
// Locking Discipline : In addition to updateability and isolation level,
// FrontBase introduces the concept of locking discipline:
// PESSIMISTIC locking assumes that the given object will be
// changed, i.e. a transaction must wait until the object is available (
// unlocked).
// It returns 1 when the lock has been acquired.
// Caller must release the lock with sql_mutex_unlock().
// Caller must not have more than one mutex at any time.
// Do not mix this with $mdb->autoCommit()/$mdb->commit() calls.
function sql_mutex_lock($name)
{
    global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name, $mdb;
    if (MDB::isError($mdb->autoCommit(FALSE))
        || MDB::isError($mdb->query("SET TRANSACTION ISOLATION LEVEL
        SERIALIZABLE, LOCKING PESSIMISTIC")))
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
    $v = $mdb->queryOne("SELECT VALUES(SERVER_NAME)");
    return "$v";
}

/**
 * sql_syntax_caseless_contains().
 *
 * Generate non-standard SQL to match a string anywhere in a field's value
 * in a case insensitive manner.
 * In Frontbase, case is significant in all conditions comparing character
 * expressions including the LIKE and equality (=) operators. You can use the
 * UPPER function to perform a case-insensitive match, as in this condition:
 * UPPER(ename) LIKE 'SM%'
 *
 * @param   string  $fieldname  field to apply regexp
 * @param   string  $s          un-escaped/un-slashed string to search for
 * @return  string  fieldname and escaped/slashed string
 * @todo     This function have not been tested
 */
function sql_syntax_caseless_contains($fieldname, $s)
{
    $s = strtoupper($s);
    $s = str_replace("\\", "\\\\", $s);
    $s = str_replace("%", "\\%", $s);
    $s = str_replace("_", "\\_", $s);
    $s = str_replace("'", "''", $s);
    return " UPPER($fieldname) LIKE " . $mdb->getTextValue("%$s%") . " ";
}

?>