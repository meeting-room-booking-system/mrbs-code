<?php
// $Id$

/* ibase.inc - Simple PHP database support for Interbase/Firebird.
   Include this file after database.inc.php.
   This file contains specific DBMS features/commands not handled by MDB
   database wrapper.
 */


// Acquire a mutual-exclusion lock on the named table. For portability:
// This will not lock out SELECTs.
// It may lock out DELETE/UPDATE/INSERT or not, depending on the implementation.
// It will lock out other callers of this routine with the same name argument.
// In Interbase, the SET TRANSACTION start a new transaction, so we don't
// need to autoCommit().
// It returns 1 when the lock has been acquired.
// Caller must release the lock with sql_mutex_unlock().
// Caller must not have more than one mutex at any time.
// Do not mix this with $mdb->autoCommit()/$mdb->commit() calls.
function sql_mutex_lock($name)
{
    global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name, $mdb;
    if (MDB::isError($mdb->query("SET TRANSACTION ISOLATION LEVEL
        SNAPSHOT TABLE STABILITY
        RESERVING $name FOR PROTECTED WRITE")))
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
    $v = $mdb->queryOne("SHOW VERSION");
    return "$v";
}

/**
 * sql_syntax_caseless_contains().
 *
 * Generate non-standard SQL to match a string anywhere in a field's value
 * in a case insensitive manner.
 * In Interbase, case is not significant in all conditions comparing character
 * expressions using the CONTAINING operator. CONTAINING test to see if an
 * ASCII string value contains a quoted ASCII string.
 *
 * @param   string  $fieldname  field to apply regexp
 * @param   string  $s          un-escaped/un-slashed string to search for
 * @return  string  fieldname and escaped/slashed string
 * @todo     This function have not been tested
 */
function sql_syntax_caseless_contains($fieldname, $s)
{
    $s = str_replace("\\", "\\\\", $s);
    $s = str_replace("%", "\\%", $s);
    $s = str_replace("_", "\\_", $s);
    $s = str_replace("'", "''", $s);
    return " $fieldname CONTAINING " . $mdb->getTextValue("%$s%") . " ESCAPE '\'";
}

?>