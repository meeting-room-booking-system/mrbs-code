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

if(!defined('MDB_MANAGER_COMMON_INCLUDED'))
{
    define('MDB_MANAGER_COMMON_INCLUDED', 1);

/**
 * Base class for the management modules that is extended by each MDB driver
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */

class MDB_Manager_Common
{
    // }}}
    // {{{ getFieldDeclaration()

    /**
     * get declaration of a field
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $field_name name of the field to be created
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Boolean value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function getFieldDeclaration(&$db, $field_name, $field)
    {
        if (!strcmp($field_name, '')) {
            return($db->raiseError(MDB_ERROR_NOSUCHFIELD, NULL, NULL,
                'Get field: it was not specified a valid field name ("'.$field_name.'")'));
        }
        switch($field['type']) {
            case 'integer':
                return($db->getIntegerDeclaration($field_name, $field));
                break;
            case 'text':
                return($db->getTextDeclaration($field_name, $field));
                break;
            case 'clob':
                return($db->getClobDeclaration($field_name, $field));
                break;
            case 'blob':
                return($db->getBlobDeclaration($field_name, $field));
                break;
            case 'boolean':
                return($db->getBooleanDeclaration($field_name, $field));
                break;
            case 'date':
                return($db->getDateDeclaration($field_name, $field));
                break;
            case 'timestamp':
                return($db->getTimestampDeclaration($field_name, $field));
                break;
            case 'time':
                return($db->getTimeDeclaration($field_name, $field));
                break;
            case 'float':
                return($db->getFloatDeclaration($field_name, $field));
                break;
            case 'decimal':
                return($db->getDecimalDeclaration($field_name, $field));
                break;
            default:
                return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'Get field: type "'.$field['type'].'" is not yet supported'));
                break;
        }
    }

    // }}}
    // {{{ getFieldDeclarationList()

    /**
     * get declaration of a number of field in bulk
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $fields  a multidimensional associative array.
             The first dimension determines the field name, while the second
            dimension is keyed with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Boolean value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     *
     *      default
     *          Boolean value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function getFieldDeclarationList(&$db, $fields)
    {
        if(is_array($fields)) {
            foreach($fields as $field_name => $field) {
                $query = $db->getFieldDeclaration($field_name, $field);
                if (MDB::isError($query)) {
                    return($query);
                }
                $query_fields[] = $query;
            }
            return(implode(',',$query_fields));
        }
        return(PEAR::raiseError(NULL, MDB_ERROR_MANAGER, NULL, NULL,
            'the definition of the table "'.$table_name.'" does not contain any fields', 'MDB_Error', TRUE));
    }

    // }}}
    // {{{ _isSequenceName()
    /**
     * list all tables in the current database
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $sqn string that containts name of a potential sequence
     * @return mixed name of the sequence if $sqn is a name of a sequence, else FALSE
     * @access private
     */
    function _isSequenceName(&$db, $sqn)
    {
        $seq_pattern = '/^'.preg_replace('/%s/', '([a-z0-9_]+)', $db->options['seqname_format']).'$/i';
        $seq_name = preg_replace($seq_pattern, '\\1', $sqn);
        if($seq_name && $sqn == $db->getSequenceName($seq_name)) {
            return($seq_name);
        }
        return(FALSE);
    }

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
    function createDatabase(&$db, $database)
    {
        return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Create database: database creation is not supported'));
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
    function dropDatabase(&$db, $database)
    {
        return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Drop database: database dropping is not supported'));
    }

    // }}}
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param object    $dbs        database object that is extended by this class
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
        if (MDB::isError($query_fields = $db->getFieldDeclarationList($fields))) {
            return($query_fields);
        }
        return($db->query("CREATE TABLE $name ($query_fields)"));
    }

    // }}}
    // {{{ dropTable()

    /**
     * drop an existing table
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $name name of the table that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropTable(&$db, $name)
    {
        return($db->query("DROP TABLE $name"));
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
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function alterTable(&$db, $name, $changes, $check)
    {
        return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Alter table: database table alterations are not supported'));
    }

    // }}}
    // {{{ listDatabases()

    /**
     * list all databases
     *
     * @param object    $dbs        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listDatabases(&$db)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List Databases: list databases is not supported'));
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @param object    $dbs        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listUsers(&$db)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List User: list user is not supported'));
    }

    // }}}
    // {{{ listViews()

    /**
     * list all views in the current database
     *
     * @param object    $dbs        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listViews(&$db)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List View: list view is not supported'));
    }

    // }}}
    // {{{ listFunctions()

    /**
     * list all functions in the current database
     *
     * @param object    $dbs        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listFunctions(&$db)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List Function: list function is not supported'));
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @param object    $dbs        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTables(&$db)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List tables: list tables is not supported'));
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a tables in the current database
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableFields(&$db, $table)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List table fields: list table fields is not supported'));
    }

    // }}}
    // {{{ getTableFieldDefinition()

    /**
     * get the stucture of a field into an array
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $table         name of table that should be used in method
     * @param string    $fields     name of field that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getTableFieldDefinition(&$db, $table, $field)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'Get table field definition: table field definition is not supported'));
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
        $query = 'CREATE';
        if (isset($definition['unique'])) {
            $query .= ' UNIQUE';
        }
        $query .= " INDEX $name ON $table (";
        for($field = 0,reset($definition['FIELDS']);
            $field<count($definition['FIELDS']); $field++,next($definition['FIELDS']))
        {
            if ($field>0) {
                $query.= ', ';
            }
            $field_name = Key($definition['FIELDS']);
            $query.= $field_name;
            if ($db->support('IndexSorting') && isset($definition['FIELDS'][$field_name]['sorting'])) {
                switch($definition['FIELDS'][$field_name]['sorting']) {
                    case 'ascending':
                        $query.= ' ASC';
                        break;
                    case 'descending':
                        $query.= ' DESC';
                        break;
                }
            }
        }
        $query.= ')';
        return($db->query($query));
    }

    // }}}
    // {{{ dropIndex()

    /**
     * drop existing index
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $table         name of table that should be used in method
     * @param string    $name         name of the index to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropIndex(&$db, $table, $name)
    {
        return($db->query("DROP INDEX $name"));
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $table      name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableIndexes(&$db, $table)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List table indexes: List Indexes is not supported'));
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * get the stucture of an index into an array
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $table      name of table that should be used in method
     * @param string    $index      name of index that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getTableIndexDefinition(&$db, $table, $index)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'Get table index definition: getting index definition is not supported'));
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
    function createSequence(&$db, $name, $start)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'Create Sequence: sequence creation not supported'));
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
    function dropSequence(&$db, $name)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'Drop Sequence: sequence dropping not supported'));
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all sequences in the current database
     *
     * @param object    $dbs        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listSequences(&$db)
    {
        return($db->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'List sequences: List sequences is not supported'));
    }

    // }}}
    // {{{ getSequenceDefinition()

    /**
     * get the stucture of a sequence into an array
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $sequence   name of sequence that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */

    // }}}
    // {{{ getSequenceDefinition()

    /**
     * get the stucture of a sequence into an array
     *
     * @param object    $dbs        database object that is extended by this class
     * @param string    $sequence   name of sequence that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getSequenceDefinition(&$db, $sequence)
    {
        $start = $db->currId($sequence);
        if (MDB::isError($start)) {
            return($start);
        }
        if ($db->support('CurrId')) {
            $start++;
        } else {
            $db->warnings[] = 'database does not support getting current
                sequence value,the sequence value was incremented';
        }
        $definition = array();
        if($start != 1) {
            $definition = array('start' => $start);
        }
        return($definition);
    }
}

};
?>