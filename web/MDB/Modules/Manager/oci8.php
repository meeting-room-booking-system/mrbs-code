<?php 
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2004 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB is a merge of PEAR DB and Metabases that provides a unified DB   |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Lukas Smith <smith@backendmedia.com>                         |
// +----------------------------------------------------------------------+

// $Id$

if (!defined('MDB_MANAGER_OCI8_INCLUDED')) {
    define('MDB_MANAGER_OCI8_INCLUDED', 1);

require_once('MDB/Modules/Manager/Common.php');

/**
 * MDB oci8 driver for the management modules
 * 
 * @package MDB
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com> 
 */
class MDB_Manager_oci8 extends MDB_Manager_Common {

    // {{{ createDatabase()

    /**
     * create a new database
     * 
     * @param object $dbs database object that is extended by this class
     * @param string $name name of the database that should be created
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public 
     */
/*
    function createDatabase(&$db, $name)
    {
        $user = $db->getOption('DBAUser');
        if (MDB::isError($user)) {
            return($db->raiseError(MDB_ERROR_INSUFFICIENT_DATA, NULL, NULL, 'Create database',
                'it was not specified the Oracle DBAUser option'));
        }
        $password = $db->getOption('DBAPassword');
        if (MDB::isError($password)) {
            return($db->raiseError(MDB_ERROR_INSUFFICIENT_DATA, NULL, NULL, 'Create database',
                'it was not specified the Oracle DBAPassword option'));
        }
        if (!MDB::isError($result = $db->connect($user, $password, 0))) {
            $tablespace = $db->getOption('DefaultTablespace');
            if(MDB::isError($tablespace)) {
                $tablespace = '';
            } else {
                $tablespace = ' DEFAULT TABLESPACE '.$tablespace;
            }
            if (!MDB::isError($result = $db->_doQuery('CREATE USER '.$db->user.' IDENTIFIED BY '.$db->password.$tablespace))) {
                if (!MDB::isError($result = $db->_doQuery('GRANT CREATE SESSION, CREATE TABLE,UNLIMITED TABLESPACE,CREATE SEQUENCE TO '.$db->user))) {
                    return(MDB_OK);
                } else {
                    if (MDB::isError($result2 = $db->_doQuery('DROP USER '.$db->user.' CASCADE'))) {
                        return($db->raiseError(MDB_ERROR, '','', 'Create database',
                            'could not setup the database user ('.$result->getUserinfo().') and then could drop its records ('.$result2->getUserinfo().')'));
                    }
                    return($db->raiseError(MDB_ERROR, '','', 'Create database',
                        'could not setup the database user ('.$result->getUserinfo().')'));
                }
            }
        }
        return($result);
    }
*/

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     * 
     * @param object $dbs database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public 
     */
/*
    function dropDatabase(&$db, $name)
    {
        $user = $db->getOption('DBAUser');
        if (MDB::isError($user)) {
            return($db->raiseError(MDB_ERROR_INSUFFICIENT_DATA, NULL, NULL, 'Create database',
                'it was not specified the Oracle DBAUser option'));
        }
        $password = $db->getOption('DBAPassword');
        if (MDB::isError($password)) {
            return($db->raiseError(MDB_ERROR_INSUFFICIENT_DATA, NULL, NULL, 'Create database',
                'it was not specified the Oracle DBAPassword option'));
        }
        if (MDB::isError($db->connect($user, $password, 0))) {
            return($result);
        }
        return($db->_doQuery('DROP USER '.$db->user.' CASCADE'));
    }
*/

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     * 
     * @param object $dbs database object that is extended by this class
     * @param string $name name of the table that is intended to be changed.
     * @param array $changes associative array that contains the details of each type
     *                              of change that is intended to be performed. The types of
     *                              changes that are currently supported are defined as follows:
     * 
     *                              name
     * 
     *                                 New name for the table.
     * 
     *                             AddedFields
     * 
     *                                 Associative array with the names of fields to be added as
     *                                  indexes of the array. The value of each entry of the array
     *                                  should be set to another associative array with the properties
     *                                  of the fields to be added. The properties of the fields should
     *                                  be the same as defined by the Metabase parser.
     * 
     *                                 Additionally, there should be an entry named Declaration that
     *                                  is expected to contain the portion of the field declaration already
     *                                  in DBMS specific SQL code as it is used in the CREATE TABLE statement.
     * 
     *                             RemovedFields
     * 
     *                                 Associative array with the names of fields to be removed as indexes
     *                                  of the array. Currently the values assigned to each entry are ignored.
     *                                  An empty array should be used for future compatibility.
     * 
     *                             RenamedFields
     * 
     *                                 Associative array with the names of fields to be renamed as indexes
     *                                  of the array. The value of each entry of the array should be set to
     *                                  another associative array with the entry named name with the new
     *                                  field name and the entry named Declaration that is expected to contain
     *                                  the portion of the field declaration already in DBMS specific SQL code
     *                                  as it is used in the CREATE TABLE statement.
     * 
     *                             ChangedFields
     * 
     *                                 Associative array with the names of the fields to be changed as indexes
     *                                  of the array. Keep in mind that if it is intended to change either the
     *                                  name of a field and any other properties, the ChangedFields array entries
     *                                  should have the new names of the fields as array indexes.
     * 
     *                                 The value of each entry of the array should be set to another associative
     *                                  array with the properties of the fields to that are meant to be changed as
     *                                  array entries. These entries should be assigned to the new values of the
     *                                  respective properties. The properties of the fields should be the same
     *                                  as defined by the Metabase parser.
     * 
     *                                 If the default property is meant to be added, removed or changed, there
     *                                  should also be an entry with index ChangedDefault assigned to 1. Similarly,
     *                                  if the notNULL constraint is to be added or removed, there should also be
     *                                  an entry with index ChangedNotNull assigned to 1.
     * 
     *                                 Additionally, there should be an entry named Declaration that is expected
     *                                  to contain the portion of the field changed declaration already in DBMS
     *                                  specific SQL code as it is used in the CREATE TABLE statement.
     *                             Example
     *                                 array(
     *                                     'name' => 'userlist',
     *                                     'AddedFields' => array(
     *                                         'quota' => array(
     *                                             'type' => 'integer',
     *                                             'unsigned' => 1
     *                                             'Declaration' => 'quota INT'
     *                                         )
     *                                     ),
     *                                     'RemovedFields' => array(
     *                                         'file_limit' => array(),
     *                                         'time_limit' => array()
     *                                         ),
     *                                     'ChangedFields' => array(
     *                                         'gender' => array(
     *                                             'default' => 'M',
     *                                             'ChangeDefault' => 1,
     *                                             'Declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                         )
     *                                     ),
     *                                     'RenamedFields' => array(
     *                                         'sex' => array(
     *                                             'name' => 'gender',
     *                                             'Declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                         )
     *                                     )
     *                                 )
     * @param boolean $check indicates whether the function should just check if the DBMS driver
     *                              can perform the requested table alterations if the value is TRUE or
     *                              actually perform them otherwise.
     * @access public 
     * @return mixed MDB_OK on success, a MDB error on failure
     */
    function alterTable(&$db, $name, $changes, $check)
    {
        if ($check) {
            for($change = 0, reset($changes);
                $change < count($changes);
                next($changes), $change++)
            {
                switch (key($changes)) {
                    case 'AddedFields':
                    case 'RemovedFields':
                    case 'ChangedFields':
                    case 'name':
                        break;
                    case 'RenamedFields':
                    default:
                        return($db->raiseError(MDB_ERROR, NULL, NULL, 'Alter table',
                            'change type "'.key($changes).'" not yet supported'));
                }
            }
            return(MDB_OK);
        } else {
            if (isset($changes['RemovedFields'])) {
                $query = ' DROP (';
                $fields = $changes['RemovedFields'];
                for($field = 0, reset($fields);
                    $field < count($fields);
                    next($fields), $field++)
                {
                    if ($field > 0) {
                        $query .= ', ';
                    }
                    $query .= key($fields);
                }
                $query .= ')';
                if (MDB::isError($result = $db->query("ALTER TABLE $name $query"))) {
                    return($result);
                }
                $query = '';
            }
            $query = (isset($changes['name']) ? 'RENAME TO '.$changes['name'] : '');
            if (isset($changes['AddedFields'])) {
                $fields = $changes['AddedFields'];
                for($field = 0, reset($fields);
                    $field < count($fields);
                    next($fields), $field++)
                {
                    $query .= ' ADD ('.$fields[key($fields)]['Declaration'].')';
                }
            }
            if (isset($changes['ChangedFields'])) {
                $fields = $changes['ChangedFields'];
                for($field = 0, reset($fields);
                    $field < count($fields);
                    next($fields), $field++)
                {
                    $current_name = key($fields);
                    if (isset($renamed_fields[$current_name])) {
                        $field_name = $renamed_fields[$current_name];
                        unset($renamed_fields[$current_name]);
                    } else {
                        $field_name = $current_name;
                    }
                    $change = '';
                    $change_type = $change_default = 0;
                    if (isset($fields[$current_name]['type'])) {
                        $change_type = $change_default = 1;
                    }
                    if (isset($fields[$current_name]['length'])) {
                        $change_type = 1;
                    }
                    if (isset($fields[$current_name]['ChangedDefault'])) {
                        $change_default = 1;
                    }
                    if ($change_type) {
                        $change .= ' '.$db->getTypeDeclaration($fields[$current_name]['Definition']);
                    }
                    if ($change_default) {
                        $change .= ' DEFAULT '.(isset($fields[$current_name]['Definition']['default'])
                            ? $db->getFieldValue($fields[$current_name]['Definition']['type'], $fields[$current_name]['Definition']['default']) : 'NULL');
                    }
                    if (isset($fields[$current_name]['ChangedNotNull'])) {
                        $change .= (isset($fields[$current_name]['notnull']) ? ' NOT' : '').' NULL';
                    }
                    if (strcmp($change, '')) {
                        $query .= " MODIFY ($field_name$change)";
                    }
                }
            }
            if($query != '' && MDB::isError($result = $db->query("ALTER TABLE $name $query"))) {
                return($result);
            }
            return(MDB_OK);
        }
        return($db->raiseError());
    }

    // }}}
    // {{{ listDatabases()

    /**
     * list all databases
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listDatabases(&$db)
    {
        $query = "SELECT table_name, tablespace_name FROM user_tables";
        return($db->queryCol($query));
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     **/
    function listTables(&$db)
    {
        $query = 'SELECT table_name FROM sys.user_tables';
        return($db->queryCol($sql));
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a tables in the current database
     *
     * @param object    $db        database object that is extended by this class
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableFields(&$db, $table)
    {
        $table = strtoupper($table);
        $query = "SELECT column_name FROM user_tab_columns WHERE table_name='$table' ORDER BY column_id";
        $columns = $db->queryCol($query);
        if (MDB::isError($result)) {
            return($result);
        }
        if ($db->options['optimize'] == 'portability') {
            $columns = array_flip($columns);
            $columns = array_change_key_case($columns, CASE_LOWER);
            $columns = array_flip($columns);
        }
        return($columns);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     * 
     * @param object $dbs database object that is extended by this class
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public 
     */
    function createSequence(&$db, $seq_name, $start)
    {
        $sequence_name = $db->getSequenceName($seq_name);
        return $db->query("CREATE SEQUENCE $sequence_name START WITH $start INCREMENT BY 1".
            ($start < 1 ? " MINVALUE $start" : ''));
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     * 
     * @param object $dbs database object that is extended by this class
     * @param string $seq_name name of the sequence to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public 
     */
    function dropSequence(&$db, $seq_name)
    {
        $sequence_name = $db->getSequenceName($seq_name);
        return($db->query("DROP SEQUENCE $sequence_name"));
    }
}

};
?>