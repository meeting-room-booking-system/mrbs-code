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
// | Author: Paul Cooper <pgc@ucecom.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id$

if(!defined('MDB_MANAGER_PGSQL_INCLUDED'))
{
    define('MDB_MANAGER_PGSQL_INCLUDED', 1);

require_once('MDB/Modules/Manager/Common.php');

/**
 * MDB MySQL driver for the management modules
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Paul Cooper <pgc@ucecom.com>
 */
class MDB_Manager_pgsql extends MDB_Manager_common
{
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param object    $db        database object that is extended by this class
     * @param string $name name of the database that should be created
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function createDatabase(&$db, $name)
    {
        return($db->_standaloneQuery("CREATE DATABASE $name"));
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
     **/
    function dropDatabase(&$db, $name)
    {
        return($db->_standaloneQuery("DROP DATABASE $name"));
    }

    // }}}
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param object    $db        database object that is extended by this class
     * @param string $name Name of the database that should be created
     * @param array $fields Associative array that contains the definition of each field of the new table
     *                         The indexes of the array entries are the names of the fields of the table an
     *                         the array entry values are associative arrays like those that are meant to be
     *                          passed with the field definitions to get[Type]Declaration() functions.
     *
     *                         Example
     *                         array(
     *
     *                             'id' => array(
     *                                 'type' => 'integer',
     *                                 'unsigned' => 1
     *                                 'notnull' => 1
     *                                 'default' => 0
     *                             ),
     *                             'name' => array(
     *                                 'type' => 'text',
     *                                 'length' => 12
     *                             ),
     *                             'password' => array(
     *                                 'type' => 'text',
     *                                 'length' => 12
     *                             )
     *                         );
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     **/
    function createTable(&$db, $name, $fields)
    {
        if (!isset($name) || !strcmp($name, '')) {
            return($db->raiseError(MDB_ERROR_CANNOT_CREATE, NULL, NULL, 'no valid table name specified'));
        }
        if (count($fields) == 0) {
            return($db->raiseError(MDB_ERROR_CANNOT_CREATE, NULL, NULL, 'no fields specified for table "'.$name.'"'));
        }
        $query_fields = '';
        if (MDB::isError($query_fields = $db->getFieldDeclarationList($fields))) {
            return($db->raiseError(MDB_ERROR_CANNOT_CREATE, NULL, NULL, 'unkown error'));
        }
        return($db->query("CREATE TABLE $name ($query_fields)"));
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
        if ($check) {
            for ($change = 0, reset($changes); $change < count($changes); next($changes), $change++) {
                switch (key($changes)) {
                    case 'AddedFields':
                        break;
                    case 'RemovedFields':
                        return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'database server does not support dropping table columns'));
                    case 'name':
                    case 'RenamedFields':
                    case 'ChangedFields':
                    default:
                        return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'change type "'.key($changes).'\" not yet supported'));
                }
            }
            return(MDB_OK);
        } else {
            if (isSet($changes[$change = 'name']) || isSet($changes[$change = 'RenamedFields']) || isSet($changes[$change = 'ChangedFields'])) {
                return($db->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'change type "'.$change.'" not yet supported'));
            }
            $query = '';
            if (isSet($changes['AddedFields'])) {
                $fields = $changes['AddedFields'];
                for ($field = 0, reset($fields); $field < count($fields); next($fields), $field++) {
                    if (MDB::isError($result = $db->query("ALTER TABLE $name ADD ".$fields[key($fields)]['Declaration']))) {
                        return($result);
                    }
                }
            }
            if (isSet($changes['RemovedFields'])) {
                $fields = $changes['RemovedFields'];
                for ($field = 0, reset($fields); $field < count($fields); next($fields), $field++) {
                    if (MDB::isError($result = $db->query("ALTER TABLE $name DROP ".key($fields)))) {
                        return($result);
                    }
                }
            }
            return(MDB_OK);
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
     **/
    function listDatabases(&$db)
    {
        return($db->queryCol('SELECT datname FROM pg_database'));
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @param object    $db        database object that is extended by this class
     * @return mixed data array on success, a MDB error on failure
     * @access public
     **/
    function listUsers(&$db)
    {
        return($db->queryCol('SELECT usename FROM pg_user'));
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
        // gratuitously stolen from PEAR DB _getSpecialQuery in pgsql.php
        $sql = 'SELECT c.relname as "Name"
            FROM pg_class c, pg_user u
            WHERE c.relowner = u.usesysid AND c.relkind = \'r\'
            AND not exists (select 1 from pg_views where viewname = c.relname)
            AND c.relname !~ \'^pg_\'
            AND c.relname !~ \'^pga_\'
            UNION
            SELECT c.relname as "Name"
            FROM pg_class c
            WHERE c.relkind = \'r\'
            AND not exists (select 1 from pg_views where viewname = c.relname)
            AND not exists (select 1 from pg_user where usesysid = c.relowner)
            AND c.relname !~ \'^pg_\'
            AND c.relname !~ \'^pga_\'';
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
        $result = $db->query("SELECT * FROM $table");
        if(MDB::isError($result)) {
            return($result);
        }
        $columns = $db->getColumnNames($result);
        if(MDB::isError($columns)) {
            $db->freeResult($columns);
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
        $result = $db->query("SELECT 
                    attnum,attname,typname,attlen,attnotnull,
                    atttypmod,usename,usesysid,pg_class.oid,relpages,
                    reltuples,relhaspkey,relhasrules,relacl,adsrc
                    FROM pg_class,pg_user,pg_type,
                         pg_attribute left outer join pg_attrdef on
                         pg_attribute.attrelid=pg_attrdef.adrelid 
                    WHERE (pg_class.relname='$table') 
                        and (pg_class.oid=pg_attribute.attrelid) 
                        and (pg_class.relowner=pg_user.usesysid) 
                        and (pg_attribute.atttypid=pg_type.oid)
                        and attnum > 0
                        and attname = '$field_name'
                        ORDER BY attnum
                        ");
        if(MDB::isError($result)) {
            return($result);
        }
        $columns = $db->fetchInto($result, MDB_FETCHMODE_ASSOC);
        $field_column = $columns['attname'];
        $type_column = $columns['typname'];
        $db_type = preg_replace('/\d/','', strtolower($type_column) );
        $length = $columns['attlen'];
        if ($length == -1) {
            $length = $columns['atttypmod']-4;
        }
        //$decimal = strtok('(), '); = eh?
        $type = array();
        switch($db_type) {
            case 'int':
                $type[0] = 'integer';
                if($length == '1') {
                    $type[1] = 'boolean';
                }
                break;
            case 'text':
            case 'char':
            case 'varchar':
            case 'bpchar':
                $type[0] = 'text';
                
                if($length == '1') {
                    $type[1] = 'boolean';
                } elseif(strstr($db_type, 'text'))
                    $type[1] = 'clob';
                break;
/*                        
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
*/
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
            case 'money':
            case 'numeric':
                $type[0] = 'decimal';
                break;
            case 'oid':
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
         
        if ($columns['attnotnull'] == 'f') {
            $notnull = 1;
        }
        
        if (!preg_match("/nextval\('([^']+)'/",$columns['adsrc']))  {
            $default = substr($columns['adsrc'],1,-1);
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
        if (preg_match("/nextval\('([^']+)'/",$columns['adsrc'],$nextvals))  {
             
            $implicit_sequence = array();
            $implicit_sequence['on'] = array();
            $implicit_sequence['on']['table'] = $table;
            $implicit_sequence['on']['field'] = $field_name;
            $definition[1]['name'] = $nextvals[1];
            $definition[1]['definition'] = $implicit_sequence;
        }
 
        // check that its not just a unique field
        if(MDB::isError($indexes = $db->queryAll("SELECT 
                oid,indexrelid,indrelid,indkey,indisunique,indisprimary 
                FROm pg_index, pg_class 
                WHERE (pg_class.relname='$table') 
                    AND (pg_class.oid=pg_index.indrelid)", NULL, MDB_FETCHMODE_ASSOC))) {
            return $indexes;
        }
        $indkeys = explode(' ',$indexes['indkey']);
        if (in_array($columns['attnum'],$indkeys)) {
            if (MDB::isError($indexname = $db->queryAll("SELECT 
                    relname FROM pg_class WHERE oid={$columns['indexrelid']}")) ) {
                return $indexname;
            }
            
            $is_primary = ($indexes['isdisprimary'] == 't') ;
            $is_unique = ($indexes['isdisunique'] == 't') ;
        
            $implicit_index = array();
            $implicit_index['unique'] = 1;
            $implicit_index['FIELDS'][$field_name] = $indexname['relname'];
            $definition[2]['name'] = $field_name;
            $definition[2]['definition'] = $implicit_index;
        }
        
        $db->freeResult($result);
        return($definition);
    }


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
        // gratuitously stolen from PEAR DB _getSpecialQuery in pgsql.php
        return($db->queryCol('SELECT viewname FROM pg_views'));
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
    function listTableIndexes(&$db, $table) {
        $result = $db->query("SELECT relname 
                                FROM pg_class WHERE oid IN
                                  (SELECR indexrelid FROM pg_index, pg_class 
                                   WHERE (pg_class.relname='$table') 
                                   AND (pg_class.oid=pg_index.indrelid))");
        return $db->fetchCol($result);
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
        $row = $db->queryAll("SELECT * from pg_index, pg_class 
                                WHERE (pg_class.relname='$index_name') 
                                AND (pg_class.oid=pg_index.indexrelid)", NULL, MDB_FETCHMODE_ASSOC);
        if ($row[0]['relname'] != $index_name) {
            return($db->raiseError(MDB_ERROR_MANAGER, NULL, NULL, 'Get table index definition: it was not specified an existing table index'));
        }

        $columns = $this->listTableFields($db, $table);

        $definition = array();
        if ($row[0]['indisunique'] == 't') {
            $definition[$index_name]['unique'] = 1;
        }

        $index_column_numbers = explode(' ', $row[0]['indkey']);

        foreach ($index_column_numbers as $number) {
            $definition['FIELDS'][$columns[($number - 1)]] = array('sorting' => 'ascending');
        }
        return $definition;
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
        return($db->query("CREATE SEQUENCE $seqname INCREMENT 1".($start < 1 ? " MINVALUE $start" : '')." START $start"));
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
        return($db->query("DROP SEQUENCE $seqname"));
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
        // gratuitously stolen and adapted from PEAR DB _getSpecialQuery in pgsql.php
        $sql = 'SELECT c.relname as "Name"
            FROM pg_class c, pg_user u
            WHERE c.relowner = u.usesysid AND c.relkind = \'S\'
            AND not exists (select 1 from pg_views where viewname = c.relname)
            AND c.relname !~ \'^pg_\'
            UNION
            SELECT c.relname as "Name"
            FROM pg_class c
            WHERE c.relkind = \'S\'
            AND not exists (select 1 from pg_views where viewname = c.relname)
            AND not exists (select 1 from pg_user where usesysid = c.relowner)
            AND c.relname !~ \'^pg_\'';
        return($db->queryCol($sql));
    }
}

};
?>
