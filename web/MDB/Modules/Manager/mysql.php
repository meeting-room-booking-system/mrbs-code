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
//
// $Id$
//

if(!defined('MDB_MANAGER_MYSQL_INCLUDED'))
{
    define('MDB_MANAGER_MYSQL_INCLUDED', 1);

require_once('MDB/Modules/Manager/Common.php');

/**
 * MDB MySQL driver for the management modules
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB_Manager_mysql extends MDB_Manager_Common
{
    // {{{ properties
    var $verified_table_types = array();

    // }}}
    // {{{ _verifyTransactionalTableType()

    /**
     * verify that chosen transactional table hanlder is available in the database
     *
     * @param object    $db        database object that is extended by this class
     * @param string $table_type name of the table handler
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access private
     */
    function _verifyTransactionalTableType(&$db, $table_type)
    {
        switch(strtoupper($table_type)) {
            case 'BERKELEYDB':
            case 'BDB':
                $check = array('have_bdb');
                break;
            case 'INNODB':
                $check = array('have_innobase', 'have_innodb');
                break;
            case 'GEMINI':
                $check = array('have_gemini');
                break;
            case 'HEAP':
            case 'ISAM':
            case 'MERGE':
            case 'MRG_MYISAM':
            case 'MYISAM':
            case '':
                return(MDB_OK);
            default:
                return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'Verify transactional table',
                    $table_type.' is not a supported table type'));
        }
        if(isset($this->verified_table_types[$table_type])
            && $this->verified_table_types[$table_type] == $db->connection)
        {
            return(MDB_OK);
        }
        $not_supported = false;
        for($i=0, $j=count($check); $i<$j; ++$i) {
            $result = $db->query('SHOW VARIABLES LIKE '.$db->getValue('text', $check[$i]), null, false);
            if (MDB::isError($result)) {
                return $db->mysqlRaiseError();
            }
            $has = $db->fetchRow($result, MDB_FETCHMODE_ORDERED);
            if (MDB::isError($has)) {
                return $has;
            }
            if (is_array($has)) {
                $not_supported = true;
                if ($has[1] == 'YES') {
                    $this->verified_table_types[$table_type] = $db->connection;
                    return(MDB_OK);
                }
            }
        }
        if ($not_supported) {
            return($db->raiseError(MDB_ERROR_UNSUPPORTED, null, null,
                $table_type.' is not a supported table type by this MySQL database server'));
        }
        return($db->raiseError(MDB_ERROR_UNSUPPORTED, null, null,
            'could not tell if '.$table_type.' is a supported table type'));
    }

    // }}}
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param object    $db        database object that is extended by this class
     * @param string $name name of the database that should be created
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createDatabase(&$db, $name)
    {
        if (MDB::isError($result = $db->connect())) {
            return($result);
        }
        $query = 'CREATE DATABASE '.$name;
        if(MDB::isError($db->query($query))) {
            return($db->mysqlRaiseError());
        }

        return(MDB_OK);
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param object    $db        database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropDatabase(&$db, $name)
    {
        if (MDB::isError($result = $db->connect())) {
            return($result);
        }
        $query = 'DROP DATABASE '.$name;
        if(MDB::isError($db->query($query))) {
            return($db->mysqlRaiseError());
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param object    $db        database object that is extended by this class
     * @param string $name     Name of the database that should be created
     * @param array $fields Associative array that contains the definition of each field of the new table
     *                        The indexes of the array entries are the names of the fields of the table an
     *                        the array entry values are associative arrays like those that are meant to be
     *                         passed with the field definitions to get[Type]Declaration() functions.
     *
     *                        Example
     *                        array(
     *
     *                            'id' => array(
     *                                'type' => 'integer',
     *                                'unsigned' => 1
     *                                'notnull' => 1
     *                                'default' => 0
     *                            ),
     *                            'name' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            ),
     *                            'password' => array(
     *                                'type' => 'text',
     *                                'length' => 12
     *                            )
     *                        );
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createTable(&$db, $name, $fields)
    {
        if (!isset($name) || !strcmp($name, '')) {
            return($db->raiseError(MDB_ERROR_CANNOT_CREATE, NULL, NULL, 'no valid table name specified'));
        }
        if (count($fields) == 0) {
            return($db->raiseError(MDB_ERROR_CANNOT_CREATE, NULL, NULL, 'no fields specified for table "'.$name.'"'));
        }
        if(MDB::isError($verify = $this->_verifyTransactionalTableType($db, $db->default_table_type))) {
            return($verify);
        }
        if (MDB::isError($query_fields = $db->getFieldDeclarationList($fields))) {
            return($db->raiseError(MDB_ERROR_CANNOT_CREATE, NULL, NULL, 'unkown error'));
        }
        if (isset($db->supported['Transactions'])
            && $db->default_table_type=='BDB')
        {
            $query_fields .= ', dummy_primary_key INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (dummy_primary_key)';
        }
        $query = "CREATE TABLE $name ($query_fields)".(strlen($db->default_table_type) ? ' TYPE='.$db->default_table_type : '');

        return($db->query($query));
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param object    $db        database object that is extended by this class
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            AddedFields
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *                                Additionally, there should be an entry named Declaration that
     *                                 is expected to contain the portion of the field declaration already
     *                                 in DBMS specific SQL code as it is used in the CREATE TABLE statement.
     *
     *                            RemovedFields
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            RenamedFields
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            ChangedFields
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the ChangedFields array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                                If the default property is meant to be added, removed or changed, there
     *                                 should also be an entry with index ChangedDefault assigned to 1. Similarly,
     *                                 if the notnull constraint is to be added or removed, there should also be
     *                                 an entry with index ChangedNotNull assigned to 1.
     *
     *                                Additionally, there should be an entry named Declaration that is expected
     *                                 to contain the portion of the field changed declaration already in DBMS
     *                                 specific SQL code as it is used in the CREATE TABLE statement.
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'AddedFields' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                            'Declaration' => 'quota INT'
     *                                        )
     *                                    ),
     *                                    'RemovedFields' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                        ),
     *                                    'ChangedFields' => array(
     *                                        'gender' => array(
     *                                            'default' => 'M',
     *                                            'ChangeDefault' => 1,
     *                                            'Declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                        )
     *                                    ),
     *                                    'RenamedFields' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'Declaration' => "gender CHAR(1) DEFAULT 'M'"
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @access public
     *
      * @return mixed MDB_OK on success, a MDB error on failure
     */
    function alterTable(&$db, $name, $changes, $check)
    {
        if ($check) {
            for($change = 0,reset($changes);
                $change < count($changes);
                next($changes), $change++)
            {
                switch(key($changes)) {
                    case 'AddedFields':
                    case 'RemovedFields':
                    case 'ChangedFields':
                    case 'RenamedFields':
                    case 'name':
                        break;
                    default:
                        return($db->raiseError(MDB_ERROR_CANNOT_ALTER, NULL, NULL,
                            'Alter table: change type "'.Key($changes).'" not yet supported'));
                }
            }
            return(MDB_OK);
        } else {
            $query = (isset($changes['name']) ? 'RENAME AS '.$changes['name'] : '');
            if (isset($changes['AddedFields'])) {
                $fields = $changes['AddedFields'];
                for($field = 0, reset($fields);
                    $field<count($fields);
                    next($fields), $field++)
                {
                    if (strcmp($query, '')) {
                        $query .= ',';
                    }
                    $query .= 'ADD '.$fields[key($fields)]['Declaration'];
                }
            }
            if (isset($changes['RemovedFields'])) {
                $fields = $changes['RemovedFields'];
                for($field = 0,reset($fields);
                    $field<count($fields);
                    next($fields), $field++)
                {
                    if (strcmp($query, '')) {
                        $query .= ',';
                    }
                    $query .= 'DROP '.key($fields);
                }
            }
            $renamed_fields = array();
            if (isset($changes['RenamedFields'])) {
                $fields = $changes['RenamedFields'];
                for($field = 0,reset($fields);
                    $field<count($fields);
                    next($fields), $field++)
                {
                    $renamed_fields[$fields[key($fields)]['name']] = key($fields);
                }
            }
            if (isset($changes['ChangedFields'])) {
                $fields = $changes['ChangedFields'];
                for($field = 0,reset($fields);
                    $field<count($fields);
                    next($fields), $field++)
                {
                    if (strcmp($query, '')) {
                        $query .= ',';
                    }
                    if (isset($renamed_fields[key($fields)])) {
                        $field_name = $renamed_fields[key($fields)];
                        unset($renamed_fields[key($fields)]);
                    } else {
                        $field_name = key($fields);
                    }
                    $query .= "CHANGE $field_name ".$fields[key($fields)]['Declaration'];
                }
            }
            if (count($renamed_fields))
            {
                for($field = 0,reset($renamed_fields);
                    $field<count($renamed_fields);
                    next($renamed_fields), $field++)
                {
                    if (strcmp($query, '')) {
                        $query .= ',';
                    }
                    $old_field_name = $renamed_fields[Key($renamed_fields)];
                    $query .= "CHANGE $old_field_name ".$changes['RenamedFields'][$old_field_name]['Declaration'];
                }
            }
            return($db->query("ALTER TABLE $name $query"));
        }
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
        $result = $db->queryCol('SHOW DATABASES');
        if(MDB::isError($result)) {
            return($result);
        }
        return($result);
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listUsers(&$db)
    {
        $result = $db->queryCol('SELECT DISTINCT USER FROM USER');
        if(MDB::isError($result)) {
            return($result);
        }
        return($result);
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTables(&$db)
    {
        $table_names = $db->queryCol('SHOW TABLES');
        if(MDB::isError($table_names)) {
            return($table_names);
        }
        for($i = 0, $j = count($table_names), $tables = array(); $i < $j; ++$i)
        {
            if (!$this->_isSequenceName($db, $table_names[$i]))
                $tables[] = $table_names[$i];
        }
        return($tables);
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
        $result = $db->query("SHOW COLUMNS FROM $table");
        if(MDB::isError($result)) {
            return($result);
        }
        $columns = $db->getColumnNames($result);
        if(MDB::isError($columns)) {
            $db->freeResult($columns);
            return($columns);
        }
        if(!isset($columns['field'])) {
            $db->freeResult($result);
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'List table fields: show columns does not return the table field names'));
        }
        $field_column = $columns['field'];
        for($fields = array(), $field = 0; !$db->endOfResult($result); ++$field) {
            $field_name = $db->fetch($result, $field, $field_column);
            if ($field_name != $db->dummy_primary_key)
                $fields[] = $field_name;
        }
        $db->freeResult($result);
        return($fields);
    }

    // }}}
    // {{{ getTableFieldDefinition()

    /**
     * get the stucture of a field into an array
     *
     * @param object    $db        database object that is extended by this class
     * @param string    $table         name of table that should be used in method
     * @param string    $field_name     name of field that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getTableFieldDefinition(&$db, $table, $field_name)
    {
        if ($field_name == $db->dummy_primary_key) {
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'Get table field definiton: '.$db->dummy_primary_key.' is an hidden column'));
        }
        $result = $db->query("SHOW COLUMNS FROM $table");
        if(MDB::isError($result)) {
            return($result);
        }
        $columns = $db->getColumnNames($result);
        if(MDB::isError($columns)) {
            $db->freeResult($columns);
            return($columns);
        }
        if (!isset($columns[$column = 'field'])
            || !isset($columns[$column = 'type']))
        {
            $db->freeResult($result);
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'Get table field definition: show columns does not return the column '.$column));
        }
        $field_column = $columns['field'];
        $type_column = $columns['type'];
        while (is_array($row = $db->fetchInto($result))) {
            if ($field_name == $row[$field_column]) {
                $db_type = strtolower($row[$type_column]);
                $db_type = strtok($db_type, '(), ');
                if ($db_type == 'national') {
                    $db_type = strtok('(), ');
                }
                $length = strtok('(), ');
                $decimal = strtok('(), ');
                $type = array();
                switch($db_type) {
                    case 'tinyint':
                    case 'smallint':
                    case 'mediumint':
                    case 'int':
                    case 'integer':
                    case 'bigint':
                        $type[0] = 'integer';
                        if($length == '1') {
                            $type[1] = 'boolean';
                            if (preg_match('/^[is|has]/', $field_name)) {
                                $type = array_reverse($type);
                            }
                        }
                        break;
                    case 'tinytext':
                    case 'mediumtext':
                    case 'longtext':
                    case 'text':
                    case 'char':
                    case 'varchar':
                        $type[0] = 'text';
                        if($decimal == 'binary') {
                            $type[1] = 'blob';
                        } elseif($length == '1') {
                            $type[1] = 'boolean';
                            if (preg_match('/[is|has]/', $field_name)) {
                                $type = array_reverse($type);
                            }
                        } elseif(strstr($db_type, 'text'))
                            $type[1] = 'clob';
                        break;
                    case 'enum':
                        preg_match_all('/\'.+\'/U',$row[$type_column], $matches);
                        $length = 0;
                        if(is_array($matches)) {
                            foreach($matches[0] as $value) {
                                $length = max($length, strlen($value)-2);
                            }
                        }
                        unset($decimal);
                    case 'set':
                        $type[0] = 'text';
                        $type[1] = 'integer';
                        break;
                    case 'date':
                        $type[0] = 'date';
                        break;
                    case 'datetime':
                    case 'timestamp':
                        $type[0] = 'timestamp';
                        break;
                    case 'time':
                        $type[0] = 'time';
                        break;
                    case 'float':
                    case 'double':
                    case 'real':
                        $type[0] = 'float';
                        break;
                    case 'decimal':
                    case 'numeric':
                        $type[0] = 'decimal';
                        break;
                    case 'tinyblob':
                    case 'mediumblob':
                    case 'longblob':
                    case 'blob':
                        $type[0] = 'blob';
                        $type[1] = 'text';
                        break;
                    case 'year':
                        $type[0] = 'integer';
                        $type[1] = 'date';
                        break;
                    default:
                        return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                            'List table fields: unknown database attribute type'));
                }
                unset($notnull);
                if (isset($columns['null'])
                    && $row[$columns['null']] != 'YES')
                {
                    $notnull = 1;
                }
                unset($default);
                if (isset($columns['default'])
                    && isset($row[$columns['default']]))
                {
                    $default = $row[$columns['default']];
                }
                $definition = array();
                for($field_choices = array(), $datatype = 0; $datatype < count($type); $datatype++) {
                    $field_choices[$datatype] = array('type' => $type[$datatype]);
                    if(isset($notnull)) {
                        $field_choices[$datatype]['notnull'] = 1;
                    }
                    if(isset($default)) {
                        $field_choices[$datatype]['default'] = $default;
                    }
                    if($type[$datatype] != 'boolean'
                        && $type[$datatype] != 'time'
                        && $type[$datatype] != 'date'
                        && $type[$datatype] != 'timestamp')
                    {
                        if(strlen($length)) {
                            $field_choices[$datatype]['length'] = $length;
                        }
                    }
                }
                $definition[0] = $field_choices;
                if (isset($columns['extra'])
                    && isset($row[$columns['extra']])
                    && $row[$columns['extra']] == 'auto_increment')
                {
                    $implicit_sequence = array();
                    $implicit_sequence['on'] = array();
                    $implicit_sequence['on']['table'] = $table;
                    $implicit_sequence['on']['field'] = $field_name;
                    $definition[1]['name'] = $table.'_'.$field_name;
                    $definition[1]['definition'] = $implicit_sequence;
                }
                if (isset($columns['key'])
                    && isset($row[$columns['key']])
                    && $row[$columns['key']] == 'PRI')
                {
                    // check that its not just a unique field
                    if(MDB::isError($indexes = $db->queryAll("SHOW INDEX FROM $table", NULL, MDB_FETCHMODE_ASSOC))) {
                        return($indexes);
                    }
                    $is_primary = FALSE;
                    if (!$db->options['optimize'] == 'portability') {
                        array_change_key_case($indexes);
                    }
                    foreach($indexes as $index) {
                        if ($index['key_name'] == 'PRIMARY' && $index['column_name'] == $field_name) {
                            $is_primary = TRUE;
                            break;
                        }
                    }
                    if($is_primary) {
                        $implicit_index = array();
                        $implicit_index['unique'] = 1;
                        $implicit_index['FIELDS'][$field_name] = '';
                        $definition[2]['name'] = $field_name;
                        $definition[2]['definition'] = $implicit_index;
                    }
                }
                $db->freeResult($result);
                return($definition);
            }
        }
        if(!$db->options['autofree']) {
            $db->freeResult($result);
        }
        if(MDB::isError($row)) {
            return($row);
        }
        return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
            'Get table field definition: it was not specified an existing table column'));
    }

    // }}}
    // {{{ createIndex()

    /**
     * get the stucture of a field into an array
     *
     * @param object    $db        database object that is extended by this class
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
        $query = "ALTER TABLE $table ADD ".(isset($definition['unique']) ? 'UNIQUE' : 'INDEX')." $name (";
        for($field = 0, reset($definition['FIELDS']);
            $field < count($definition['FIELDS']);
            $field++, next($definition['FIELDS']))
        {
            if ($field > 0) {
                $query .= ',';
            }
            $query .= key($definition['FIELDS']);
        }
        $query .= ')';
        return($db->query($query));
    }

    // }}}
    // {{{ dropIndex()

    /**
     * drop existing index
     *
     * @param object    $db        database object that is extended by this class
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropIndex(&$db, $table, $name)
    {
        return($db->query("ALTER TABLE $table DROP INDEX $name"));
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param object    $db        database object that is extended by this class
     * @param string    $table      name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableIndexes(&$db, $table)
    {
        if(MDB::isError($result = $db->query("SHOW INDEX FROM $table"))) {
            return($result);
        }
        $key_name = 'Key_name';
        if (!$db->options['optimize'] == 'portability') {
            $key_name = strtolower($key_name);
        }
        $indexes_all = $db->fetchCol($result, $key_name);
        for($found = $indexes = array(), $index = 0, $indexes_all_cnt = count($indexes_all);
            $index < $indexes_all_cnt;
            $index++)
        {
            if ($indexes_all[$index] != 'PRIMARY'
                && !isset($found[$indexes_all[$index]]))
            {
                $indexes[] = $indexes_all[$index];
                $found[$indexes_all[$index]] = 1;
            }
        }
        return($indexes);
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * get the stucture of an index into an array
     *
     * @param object    $db        database object that is extended by this class
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getTableIndexDefinition(&$db, $table, $index_name)
    {
        if($index_name == 'PRIMARY') {
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL, 'Get table index definition: PRIMARY is an hidden index'));
        }
        if(MDB::isError($result = $db->query("SHOW INDEX FROM $table"))) {
            return($result);
        }
        $definition = array();
        while (is_array($row = $db->fetchInto($result, MDB_FETCHMODE_ASSOC))) {
            if (!$db->options['optimize'] == 'portability') {
                $row = array_change_key_case($row);
            }
            $key_name = $row['key_name'];
            if(!strcmp($index_name, $key_name)) {
                if(!$row['non_unique']) {
                    $definition[$index_name]['unique'] = 1;
                }
                $column_name = $row['column_name'];
                $definition['FIELDS'][$column_name] = array();
                if(isset($row['collation'])) {
                    $definition['FIELDS'][$column_name]['sorting'] = ($row['collation'] == 'A' ? 'ascending' : 'descending');
                }
            }
        }
        $db->freeResult($result);
        if (!isset($definition['FIELDS'])) {
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL, 'Get table index definition: it was not specified an existing table index'));
        }
        return($definition);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param object    $db        database object that is extended by this class
     * @param string    $seq_name     name of the sequence to be created
     * @param string    $start         start value of the sequence; default is 1
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createSequence(&$db, $seq_name, $start)
    {
        $sequence_name = $db->getSequenceName($seq_name);
        $res = $db->query("CREATE TABLE $sequence_name
            (sequence INT NOT NULL AUTO_INCREMENT, PRIMARY KEY (sequence))");
        if (MDB::isError($res)) {
            return($res);
        }
        if ($start == 1) {
            return(MDB_OK);
        }
        $res = $db->query("INSERT INTO $sequence_name (sequence) VALUES (".($start-1).')');
        if (!MDB::isError($res)) {
            return(MDB_OK);
        }
        // Handle error
        $result = $db->query("DROP TABLE $sequence_name");
        if (MDB::isError($result)) {
            return($db->raiseError(MDB_ERROR, NULL, NULL,
                'Create sequence: could not drop inconsistent sequence table ('.
                $result->getMessage().' ('.$result->getUserinfo().'))'));
        }
        return($db->raiseError(MDB_ERROR, NULL, NULL,
            'Create sequence: could not create sequence table ('.
            $res->getMessage().' ('.$res->getUserinfo().'))'));
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param object    $db        database object that is extended by this class
     * @param string    $seq_name     name of the sequence to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropSequence(&$db, $seq_name)
    {
        $sequence_name = $db->getSequenceName($seq_name);
        return($db->query("DROP TABLE $sequence_name"));
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listSequences(&$db)
    {
        $table_names = $db->queryCol('SHOW TABLES');
        if(MDB::isError($table_names)) {
            return($table_names);
        }
        for($i = 0, $j = count($table_names), $sequences = array(); $i < $j; ++$i)
        {
            if ($sqn = $this->_isSequenceName($db, $table_names[$i]))
                $sequences[] = $sqn;
        }
        return($sequences);
    }

    // }}}
}

};
?>