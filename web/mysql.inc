<?php
// $Id$

/* mysql.inc - Simple PHP database support for MySQL.
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
// In MySQL, we avoid table locks, and use low-level locks instead.
function sql_mutex_lock($name)
{
    global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name, $mdb;
    $res = $mdb->queryOne("SELECT GET_LOCK(" . $mdb->getTextValue($name) . ", 20)");
    if (MDB::isError($res) or (FALSE == $res))
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
    return "Mysql $v";
}

/**
 * sql_syntax_caseless_contains().
 *
 * Generate non-standard SQL to match a string anywhere in a field's value
 * in a case insensitive manner.
 * In MySQL, REGEXP seems to be case sensitive, so use LIKE instead. But this
 * requires quoting of % and _ in addition to the usual.
 *
 * @param   string  $fieldname  field to apply regexp
 * @param   string  $s          un-escaped/un-slashed string to search for
 * @return  string  fieldname and escaped/slashed string
 * @todo    As of MySQL Version 3.23.4, REGEXP is case-insensitive for normal
 *          (not binary) strings, so we could use regexp instead
 */
function sql_syntax_caseless_contains($fieldname, $s)
{
    $s = str_replace("\\", "\\\\", $s);
    $s = str_replace("%", "\\%", $s);
    $s = str_replace("_", "\\_", $s);
    $s = str_replace("'", "''", $s);
    return " $fieldname LIKE '%$s%' ";
}

?>
