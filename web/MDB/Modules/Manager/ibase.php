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
// | Author: Lorenzo Alberton <l.alberton@quipo.it>                       |
// +----------------------------------------------------------------------+
//
// $Id$

if(!defined('MDB_MANAGER_IBASE_INCLUDED'))
{
    define('MDB_MANAGER_IBASE_INCLUDED', 1);

require_once('MDB/Modules/Manager/Common.php');

/**
 * MDB FireBird/InterBase driver for the management modules
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Lorenzo Alberton <l.alberton@quipo.it>
 */
class MDB_Manager_ibase extends MDB_Manager_common
{
    
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param object $db    database object that is extended by this class
     * @param string $name  name of the database that should be created
     * @return mixed        MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function createDatabase(&$db, $name)
    {
        return ($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Create database',
                'PHP Interbase API does not support direct queries. You have to '
                .'create the db manually by using isql command or a similar program'));
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param object $db    database object that is extended by this class
     * @param string $name  name of the database that should be dropped
     * @return mixed        MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function dropDatabase(&$db, $name)
    {
        return ($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Drop database',
                'PHP Interbase API does not support direct queries. You have '
                .'to drop the db manually by using isql command or a similar program'));
    }

    // }}}
    // {{{ checkSupportedChanges()

    /**
     * check if planned changes are supported
     *
     * @param object $db        database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function checkSupportedChanges(&$db, &$changes)
    {
        for($change=0, reset($changes);
            $change<count($changes);
            next($changes), $change++)
        {
            switch(key($changes)) {
                case "ChangedNotNull":
                case "notnull":
                    return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Check supported changes: it is not supported changes to field not null constraint'));
                case "ChangedDefault":
                case "default":
                    return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Check supported changes: it is not supported changes to field default value'));
                case "length":
                    return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Check supported changes: it is not supported changes to field default length'));
                case "unsigned":
                case "type":
                case "Declaration":
                case "Definition":
                    break;
                default:
                    return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Check supported changes: it is not supported change of type' . key($changes)));
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param object    $db        database object that is extended by this class
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
     *                                  if the notnull constraint is to be added or removed, there should also be
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
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function alterTable(&$db, $name, &$changes, $check)
    {
        if($check) {
            for($change=0, reset($changes);
                $change<count($changes);
                next($changes), $change++)
            {
                switch(key($changes)) {
                    case "AddedFields":
                    case "RemovedFields":
                    case "RenamedFields":
                        break;
                    case "ChangedFields":
                        $fields = $changes['ChangedFields'];
                        for($field=0, reset($fields);
                            $field<count($fields);
                            next($fields), $field++)
                        {
                            if(MDB::isError($err = $this->checkSupportedChanges($fields[Key($fields)]))) {
                                return($err);
                            }
                        }
                        break;
                    default:
                        return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                            'Alter table: change type ' . key($changes) . ' not yet supported'));
                }
            }
            return(MDB_OK);
        } else {
            $query = '';
            if(isset($changes['AddedFields'])) {
                $fields = $changes['AddedFields'];
                for($field=0, reset($fields); $field<count($fields); next($fields), $field++) {
                    if(strcmp($query, "")) {
                        $query .= ', ';
                    }
                    $query .= 'ADD ' . $fields[key($fields)]['Declaration'];
                }
            }
            if(isset($changes['RemovedFields'])) {
                $fields = $changes['RemovedFields'];
                for($field=0, reset($fields); $field<count($fields); next($fields), $field++) {
                    if(strcmp($query, "")) {
                        $query .= ', ';
                    }
                    $query .= 'DROP ' . key($fields);
                }
            }
            if(isset($changes['RenamedFields'])) {
                $fields = $changes['RenamedFields'];
                for($field=0, reset($fields); $field<count($fields); next($fields), $field++) {
                    if(strcmp($query, "")) {
                        $query .= ', ';
                    }
                    $query .= 'ALTER ' . key($fields) . ' TO ' . $fields[Key($fields)]['name'];
                }
            }
            if(isset($changes['ChangedFields'])) {
                $fields = $changes['ChangedFields'];
                for($field=0, reset($fields); $field<count($fields); next($fields), $field++) {
                    $field_name = key($fields);
                    if(MDB::isError($err = $this->CheckSupportedChanges($fields[Key($fields)]))) {
                        return($err);
                    }
                    if(strcmp($query, "")) {
                        $query .= ', ';
                    }
                    $query .= 'ALTER '.$field_name.' TYPE '.$db->getFieldDeclaration($fields[$field_name]['Definition']);
                }
            }
            if(MDB::isError($err = $db->query("ALTER TABLE $name $query"))) {
                return $err;
            }
            return(MDB_OK);
        }
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
        $result = $db->query("SELECT RDB$FIELD_SOURCE FROM RDB$RELATION_FIELDS WHERE RDB$RELATION_NAME='$table'");
        if(MDB::isError($result)) {
            return($result);
        }
        $columns = $db->getColumnNames($result);
        if(MDB::isError($columns)) {
            $db->freeResult($columns);
        }
        return($columns);
    }

    // }}}
    // {{{ getTableFieldDefinition()

    // }}}
    // {{{ listViews()

    /**
     * list the views in the database
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function listViews(&$db)
    {
        //return($db->queryCol('SELECT RDB$VIEW_NAME'));
        return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'not supported feature'));
    }

    // }}}
    // {{{ createIndex()

    /**
     * get the stucture of a field into an array
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $table         name of the table on which the index is to be created
     * @param string    $name         name of the index to be created
     * @param array     $definition        associative array that defines properties of the index to be created.
     *                                 Currently, only one property named FIELDS is supported. This property
     *                                 is also an associative with the names of the index fields as array
     *                                 indexes. Each entry of this array is set to another type of associative
     *                                 array that specifies properties of the index that are specific to
     *                                 each field.
     *
     *                                Currently, only the sorting property is supported. It should be used
     *                                 to define the sorting direction of the index. It may be set to either
     *                                 ascending or descending.
     *
     *                                Not all DBMS support index sorting direction configuration. The DBMS
     *                                 drivers of those that do not support it ignore this property. Use the
     *                                 function support() to determine whether the DBMS driver can manage indexes.

     *                                 Example
     *                                    array(
     *                                        'FIELDS' => array(
     *                                            'user_name' => array(
     *                                                'sorting' => 'ascending'
     *                                            ),
     *                                            'last_login' => array()
     *                                        )
     *                                    )
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createIndex(&$db, $table, $name, $definition)
    {
        for($query_sort='', $query_fields='', $field=0, reset($definition['FIELDS']);
            $field<count($definition['FIELDS']);
            $field++, next($definition['FIELDS']))
        {
            if($field > 0) {
                $query_fields .= ',';
            }
            $field_name = key($definition['FIELDS']);
            $query_fields .= $field_name;
            if(!strcmp($query_sort, "") 
                && $db->support('IndexSorting')
                && isset($definition['FIELDS'][$field_name]['sorting']))
            {
                switch($definition['FIELDS'][$field_name]['sorting']) {
                    case "ascending":
                        $query_sort = " ASC";
                        break;
                    case "descending":
                        $query_sort = " DESC";
                        break;
                }
            }
        }
        return($db->query('CREATE'.(isset($definition['unique']) ? ' UNIQUE' : '') . $query_sort
                         ." INDEX $name  ON $table ($query_fields)"));
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param object    $db        database object that is extended by this class
     * @param string $seq_name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function createSequence(&$db, $seq_name, $start)
    {
        $seqname = $db->getSequenceName($seq_name);
        if (MDB::isError($result = $db->query("CREATE GENERATOR $seqname"))) {
            return($result);
        }
        if (MDB::isError($result = $db->query("SET GENERATOR $seqname TO ".($start-1)))) {
            if (MDB::isError($err = $db->dropSequence($seq_name))) {
                return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Could not setup sequence start value and then it was not possible to drop it: '
                        .$err->getMessage().' - ' .$err->getUserInfo()));
            }
        }
        return($result);
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param object    $db        database object that is extended by this class
     * @param string $seq_name name of the sequence to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function dropSequence(&$db, $seq_name)
    {
        $seqname = $db->getSequenceName($seq_name);
        return($db->query("DELETE FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME='".strtoupper($seqname)."'"));
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     **/
    function listSequences(&$db)
    {
        return($db->queryCol("SELECT RDB\$GENERATOR_NAME FROM RDB\$GENERATORS"));
    }
}

};
?>