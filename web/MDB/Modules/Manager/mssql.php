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
// | Author: Frank M. Kromann <frank@kromann.info                         |
// +----------------------------------------------------------------------+
//
// $Id$
//

if(!defined('MDB_MANAGER_MSSQL_INCLUDED'))
{
    define('MDB_MANAGER_MSSQL_INCLUDED',1);

require_once('MDB/Modules/Manager/Common.php');

/**
 * MDB MSSQL driver for the management modules
 *
 * @package MDB
 * @category Database
 * @author  Frank M. Kromann <frank@kromann.info
 */
class MDB_Manager_mssql extends MDB_Manager_Common
{
    // }}}
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $name name of the database that should be created
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createDatabase(&$db, $name)
    {
        $DatabaseDevice = isset($db->options["DatabaseDevice"]) ? $db->options["DatabaseDevice"] : "DEFAULT";
        $DatabaseSize = isset($db->options["DatabaseSize"]) ? ", SIZE=".$db->options["DatabaseSize"] : "";
        return($db->standaloneQuery("CREATE DATABASE $name ON ".$DatabaseDevice.$DatabaseSize));
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $name name of the database that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropDatabase(&$db, $name)
    {
        return($db->standaloneQuery("DROP DATABASE $name"));
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param object    $dbs        database object that is extended by this class
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
            for ($change = 0, reset($changes);
                $change < count($changes);
                next($changes), $change++)
            {
                switch (key($changes)) {
                    case "AddedFields":
                        break;
                    case "RemovedFields":
                    case "name":
                    case "RenamedFields":
                    case "ChangedFields":
                    default:
                        return($db->raiseError(MDB_ERROR_CANNOT_ALTER, NULL, NULL,
                            'Alter table: change type "'.key($changes).'" not yet supported'));
                }
            }
            return(MDB_OK);
        } else {
            if (isset($changes[$change = 'RemovedFields'])
                || isset($changes[$change = 'name'])
                || isset($changes[$change = 'RenamedFields'])
                || isset($changes[$change = 'ChangedFields']))
            {
                return($db->raiseError(MDB_ERROR_CANNOT_ALTER, NULL, NULL,
                    'Alter table: change type "'.$change.'" is not supported by the server"'));
            }
            $query='';
            if (isset($changes['AddedFields'])) {
                if(strcmp($query, '')) {
                    $query.= ', ';
                }
                $query.= 'ADD ';
                $fields = $changes['AddedFields'];
                for ($field = 0, reset($fields);
                    $field < count($fields);
                    next($fields), $field++)
                {
                    if(strcmp($query, '')) {
                        $query.= ', ';
                    }
                    $query.= $fields[key($fields)]['Declaration'];
                }
            }
            return(strcmp($query, '') ? $db->query("ALTER TABLE $name $query") : MDB_OK);
        }
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
        $query = 'EXECUTE sp_tables @table_type = "\'TABLE\'"';
        $table_names = $db->queryCol($query, null, 2);
        if (MDB::isError($table_names)) {
            return($table_names);
        }
        $tables = array();
        for ($i = 0, $j = count($table_names); $i <$j; ++$i) {
            if (!$this->_isSequenceName($db, $table_names[$i])) {
                $tables[] = $table_names[$i];
            }
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
        $result = $db->query("SELECT * FROM $table");
        if( MDB::isError($result)) {
            return($result);
        }
        $columns = $db->getColumnNames($result);
        if (MDB::isError($columns)) {
            $db->freeResult($columns);
            return $columns;
        }
        return(array_flip($columns));
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
        $columns = $db->queryRow("EXEC sp_columns @table_name='$table',
                   @column_name='$field_name'", NULL, MDB_FETCHMODE_ASSOC );
        if (MDB::isError($columns))
        {
            return($columns);
        }
        if (!$db->options['optimize'] == 'portability') {
            array_change_key_case($columns);
        }
        if (!isset($columns[$column = 'column_name'])
            || !isset($columns[$column = 'type_name']))
        {
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'Get table field definition: no result, please check table '.
                $table.' and field '.$field_name.' are correct'));
        }
        $field_column = $columns['column_name'];
        $type_column = $columns['type_name'];
        $db_type = strtolower($type_column);
        if (strpos($type_column, ' ') !== FALSE)
        {
            $db_type = strtok($db_type, ' ');
        }
        $length = $columns['precision'];
        $decimal = $columns['scale'];
        $type = array();
        switch($db_type)
        {
            case 'bigint':
            case 'int':
            case 'smallint':
            case 'tinyint':
                $type[0] = 'integer';
                if ($length == '1')
                {
                    $type[1] = 'boolean';
                }
                break;
            case 'bit':
                $type[0] = 'integer';
                $type[1] = 'boolean';
                break;
            case 'decimal':
            case 'numeric':
                $type[0] = 'decimal';
                break;
            case 'money':
            case 'smallmoney':
                $type[0] = 'decimal';
                $type[1] = 'float';
                break;
            case 'float':
            case 'real':
                $type[0] = 'float';
                break;
            case 'datetime':
            case 'smalldatetime':
                $type[0] = 'timestamp';
                break;
            case 'char':
            case 'varchar':
            case 'nchar':
            case 'nvarchar':
                $type[0] = 'text';
                if ($length == '1')
                {
                    $type[1] = 'boolean';
                }
                break;
            case 'text':
            case 'ntext':
                $type[0] = 'clob';
                $type[1] = 'text';
                break;
            case 'binary':
            case 'varbinary':
            case 'image':
                $type[0] = 'blob';
                break;
            case 'timestamp':
                $type[0] = 'blob';
                break;
            default:
                return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                    'List table fields: unknown database attribute type'));
        }
        unset($notnull);
        if ($columns['nullable'] == 0)
        {
            $notnull = 1;
        }
        unset($default);
        if (isset($columns['column_def']) && ($columns['column_def'] != NULL))
        {
            if (($type[0] = 'integer') OR ($type[0] = 'boolean'))
            {
                $columns['column_def'] = str_replace( '(', '', $columns['column_def'] );
                $columns['column_def'] = str_replace( ')', '', $columns['column_def'] );
            }
            $default = $columns['column_def'];
        }
        $definition = array();
        for ($field_choices = array(), $datatype = 0; $datatype < count($type); $datatype++)
        {
            $field_choices[$datatype] = array('type' => $type[$datatype]);
            if (isset($notnull))
            {
                $field_choices[$datatype]['notnull'] = 1;
            }
            if (isset($default))
            {
                $field_choices[$datatype]['default'] = $default;
            }
            if ($type[$datatype] != 'boolean'
                && $type[$datatype] != 'time'
                && $type[$datatype] != 'date'
                && $type[$datatype] != 'timestamp')
            {
                if (strlen($length))
                {
                    $field_choices[$datatype]['length'] = $length;
                }
            }
        }
        $definition[0] = $field_choices;
        if (strpos($type_column, 'identity') !== FALSE)
        {
            $implicit_sequence = array();
            $implicit_sequence['on'] = array();
            $implicit_sequence['on']['table'] = $table;
            $implicit_sequence['on']['field'] = $field_name;
            $definition[1]['name'] = $table.'_'.$field_name;
            $definition[1]['definition'] = $implicit_sequence;
        }
        if (MDB::isError($indexes = $db->queryAll("EXEC sp_pkeys @table_name='$table'"
                , NULL, MDB_FETCHMODE_ASSOC)))
        {
            return $indexes;
        }
        if ($indexes != NULL)
        {
            $is_primary = FALSE;
            foreach ($indexes as $index)
            {
                if ($index['column_name'] == $field_name)
                {
                    $is_primary = TRUE;
                    break;
                }
            }
            if ($is_primary)
            {
                $implicit_index = array();
                $implicit_index['unique'] = 1;
                $implicit_index['FIELDS'][$field_name] = '';
                $definition[2]['name'] = $field_name;
                $definition[2]['definition'] = $implicit_index;
            }
        }
        return($definition);
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $seq_name     name of the sequence to be created
     * @param string    $start         start value of the sequence; default is 1
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createSequence(&$db, $seq_name, $start)
    {
        $sequence_name = $db->getSequenceName($seq_name);
        $query = "CREATE TABLE $sequence_name (".$db->options['sequence_col_name']." INT NOT NULL IDENTITY($start,1) PRIMARY KEY CLUSTERED)";
        return($db->query($query));
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $seq_name     name of the sequence to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropSequence(&$db, $seq_name)
    {
        $sequence_name = $db->getSequenceName($seq_name);
        return($db->Query("DROP TABLE $sequence_name"));
    }
}

};
?>