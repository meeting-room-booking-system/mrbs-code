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

require_once('MDB/Parser.php');

define('MDB_MANAGER_DUMP_ALL',          0);
define('MDB_MANAGER_DUMP_STRUCTURE',    1);
define('MDB_MANAGER_DUMP_CONTENT',      2);

/**
 * The database manager is a class that provides a set of database
 * management services like installing, altering and dumping the data
 * structures of databases.
 *
 * @package MDB
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB_Manager extends PEAR
{
    // {{{ properties

    var $database;

    var $options = array(
            'fail_on_invalid_names' => 1,
            'debug' => 0
        );
    var $invalid_names = array(
        'user' => array(),
        'is' => array(),
        'file' => array(
            'oci' => array(),
            'oracle' => array()
        ),
        'notify' => array(
            'pgsql' => array()
        ),
        'restrict' => array(
            'mysql' => array()
        ),
        'password' => array(
            'ibase' => array()
        )
    );
    var $default_values = array(
        'integer' => 0,
        'float' => 0,
        'decimal' => 0,
        'text' => '',
        'timestamp' => '0001-01-01 00:00:00',
        'date' => '0001-01-01',
        'time' => '00:00:00'
    );

    var $warnings = array();

    var $database_definition = array(
        'name' => '',
        'create' => 0,
        'TABLES' => array()
    );

    // }}}
    // {{{ raiseError()

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for PEAR::raiseError
     * without the message string.
     *
     * @param mixed $code integer error code, or a PEAR error object (all
     *      other parameters are ignored if this parameter is an object
     * @param int $mode error mode, see PEAR_Error docs
     * @param mixed $options If error mode is PEAR_ERROR_TRIGGER, this is the
     *      error level (E_USER_NOTICE etc).  If error mode is
     *      PEAR_ERROR_CALLBACK, this is the callback function, either as a
     *      function name, or as an array of an object and method name. For
     *      other error modes this parameter is ignored.
     * @param string $userinfo Extra debug information.  Defaults to the last
     *      query and native error code.
     * @param mixed $nativecode Native error code, integer or string depending
     *      the backend.
     * @return object a PEAR error object
     * @access public
     * @see PEAR_Error
     */
    function &raiseError($code = MDB_MANAGER_ERROR, $mode = NULL, $options = NULL,
        $userinfo = NULL, $nativecode = NULL)
    {
        // The error is yet a MDB error object
        if(is_object($code)) {
            $err = PEAR::raiseError($code, NULL, NULL, NULL, NULL, NULL, TRUE);
            return($err);
        }
        
        $err = PEAR::raiseError(NULL, $code, $mode, $options, $userinfo,
            'MDB_Error', TRUE);
        return($err);
    }

    // }}}
    // {{{ captureDebugOutput()

    /**
     * set a debug handler
     *
     * @param string $capture name of the function that should be used in
     *     debug()
     * @access public
     * @see debug()
     */
    function captureDebugOutput($capture)
    {
        $this->options['debug'] = $capture;
        $this->database->captureDebugOutput(1);
    }

    // }}}
    // {{{ debugOutput()

    /**
     * output debug info
     *
     * @return string content of the debug_output class variable
     * @access public
     */
    function debugOutput()
    {
        return($this->database->debugOutput());
    }

    // }}}
    // {{{ resetWarnings()

    /**
     * reset the warning array
     *
     * @access public
     */
    function resetWarnings()
    {
        $this->warnings = array();
    }

    // }}}
    // {{{ getWarnings()

    /**
     * get all warnings in reverse order.
     * This means that the last warning is the first element in the array
     *
     * @return array with warnings
     * @access public
     * @see resetWarnings()
     */
    function getWarnings()
    {
        return array_reverse($this->warnings);
    }

    // }}}
    // {{{ setOption()

    /**
     * set the option for the db class
     *
     * @param string $option option name
     * @param mixed $value value for the option
     * @return mixed MDB_OK or MDB_Error
     * @access public
     */
    function setOption($option, $value)
    {
        if(isset($this->options[$option])) {
            $this->options[$option] = $value;
            return(MDB_OK);
        }
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, "unknown option $option"));
    }

    // }}}
    // {{{ getOption()

    /**
     * returns the value of an option
     *
     * @param string $option option name
     * @return mixed the option value or error object
     * @access public
     */
    function getOption($option)
    {
        if(isset($this->options[$option])) {
            return($this->options[$option]);
        }
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, "unknown option $option"));
    }

    // }}}
    // {{{ connect()

    /**
     * Create a new MDB connection object and connect to the specified
     * database
     *
     * @param   mixed   $dbinfo   'data source name', see the MDB::parseDSN
     *                            method for a description of the dsn format.
     *                            Can also be specified as an array of the
     *                            format returned by MDB::parseDSN.
     *                            Finally you can also pass an existing db
     *                            object to be used.
     * @param   mixed   $options  An associative array of option names and
     *                            their values.
     * @return  mixed MDB_OK on success, or a MDB error object
     * @access  public
     * @see     MDB::parseDSN
     */
    function &connect(&$dbinfo, $options = FALSE)
    {
        if(is_object($this->database) && !MDB::isError($this->database)) {
            $this->disconnect();
        }
        if(is_object($dbinfo)) {
             $this->database =& $dbinfo;
        } else {
            $this->database =& MDB::connect($dbinfo, $options);
            if(MDB::isError($this->database)) {
                return($this->database);
            }
        }
        if(is_array($options)) {
            $this->options = array_merge($options, $this->options);
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @access public
     */
    function disconnect()
    {
        if(is_object($this->database) && !MDB::isError($this->database)) {
            $this->database->disconnect();
            unset($this->database);
        }
    }

    // }}}
    // {{{ setDatabase()

    /**
     * Select a different database
     *
     * @param string $name name of the database that should be selected
     * @return string name of the database previously connected to
     * @access public
     */
    function setDatabase($name)
    {
        return($this->database->setDatabase($name));
    }

    // }}}
    // {{{ _createTable()

    /**
     * create a table and inititialize the table if data is available
     *
     * @param string $table_name  name of the table to be created
     * @param array  $table       multi dimensional array that containts the
     *                            structure and optional data of the table
     * @param boolean $overwrite  determine if the table/index should be
                                  overwritten if it already exists
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _createTable($table_name, $table, $overwrite = FALSE)
    {
        $this->expectError(MDB_ERROR_ALREADY_EXISTS);
        $result = $this->database->createTable($table_name, $table['FIELDS']);
        $this->popExpect();
        if(MDB::isError($result)) {
            if($result->getCode() === MDB_ERROR_ALREADY_EXISTS) {
                $this->warnings[] = 'Table already exists: '.$table_name;
                if($overwrite) {
                    $this->database->debug('Overwritting Table');
                    $result = $this->database->dropTable($table_name);
                    if(MDB::isError($result)) {
                        return($result);
                    }
                    $result = $this->database->createTable($table_name, $table['FIELDS']);
                    if(MDB::isError($result)) {
                        return($result);
                    }
                } else {
                    $result = MDB_OK;
                }
            } else {
                $this->database->debug('Create table error: '.$table_name);
                return($result);
            }
        }
        if(isset($table['initialization']) && is_array($table['initialization'])) {
            foreach($table['initialization'] as $instruction) {
                switch($instruction['type']) {
                    case 'insert':
                        $query_fields = $query_values = array();
                        if(isset($instruction['FIELDS']) && is_array($instruction['FIELDS'])) {
                            foreach($instruction['FIELDS'] as $field_name => $field) {
                                $query_fields[] = $field_name;
                                $query_values[] = '?';
                            }
                            $query_fields = implode(',',$query_fields);
                            $query_values = implode(',',$query_values);
                            $result = $prepared_query = $this->database->prepareQuery(
                                "INSERT INTO $table_name ($query_fields) VALUES ($query_values)");
                        }
                        if(!MDB::isError($prepared_query)) {
                            if(isset($instruction['FIELDS']) && is_array($instruction['FIELDS'])) {
                                $lobs = array();
                                $field_number = 0;
                                foreach($instruction['FIELDS'] as $field_name => $field) {
                                    $field_number++;
                                    $query = $field_name;
                                    switch($table['FIELDS'][$field_name]['type']) {
                                        case 'integer':
                                            $result = $this->database->setParamInteger($prepared_query,
                                                $field_number, intval($field));
                                            break;
                                        case 'text':
                                            $result = $this->database->setParamText($prepared_query,
                                                $field_number, $field);
                                            break;
                                        case 'clob':
                                            $lob_definition = array(
                                                'Database' => $this->database,
                                                'Error' => '',
                                                'Data' => $field
                                            );
                                            if(MDB::isError($result = $this->database->createLob($lob_definition)))
                                            {
                                                break;
                                            }
                                            $lob = count($lobs);
                                            $lobs[$lob] = $result;
                                            $result = $this->database->setParamClob($prepared_query,
                                                $field_number, $lobs[$lob], $field_name);
                                            break;
                                        case 'blob':
                                            $lob_definition = array(
                                                'Database' => $this->database,
                                                'Error' => '',
                                                'Data' => $field
                                            );
                                            if(MDB::isError($result = $this->database->createLob($lob_definition))) {
                                                break;
                                            }
                                            $lob = count($lobs);
                                            $lobs[$lob] = $result;
                                            $result = $this->database->setParamBlob($prepared_query,
                                                $field_number, $lobs[$lob], $field_name);
                                            break;
                                        case 'boolean':
                                            $result = $this->database->setParamBoolean($prepared_query,
                                                $field_number, intval($field));
                                            break;
                                        case 'date':
                                            $result = $this->database->setParamDate($prepared_query,
                                                $field_number, $field);
                                            break;
                                        case 'timestamp':
                                            $result = $this->database->setParamTimestamp($prepared_query,
                                                $field_number, $field);
                                            break;
                                        case 'time':
                                            $result = $this->database->setParamTime($prepared_query,
                                                $field_number, $field);
                                            break;
                                        case 'float':
                                            $result = $this->database->setParamFloat($prepared_query,
                                                $field_number, doubleval($field));
                                            break;
                                        case 'decimal':
                                            $result = $this->database->setParamDecimal($prepared_query,
                                                $field_number, $field);
                                            break;
                                        default:
                                            $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                                                'type "'.$field['type'].'" is not yet supported');
                                            break;
                                    }
                                    if(MDB::isError($result)) {
                                        break;
                                    }
                                }
                            }
                            if(!MDB::isError($result)) {
                                $result = $this->database->executeQuery($prepared_query);
                            }
                            for($lob = 0; $lob < count($lobs); $lob++) {
                                $this->database->destroyLOB($lobs[$lob]);
                            }
                            $this->database->freePreparedQuery($prepared_query);
                        }
                        break;
                }
            }
        };
        if(!MDB::isError($result) && isset($table['INDEXES']) && is_array($table['INDEXES'])) {
            if(!$this->database->support('Indexes')) {
                return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'indexes are not supported'));
            }
            foreach($table['INDEXES'] as $index_name => $index) {
                $this->expectError(MDB_ERROR_ALREADY_EXISTS);
                $result = $this->database->createIndex($table_name, $index_name, $index);
                $this->popExpect();
                if(MDB::isError($result)) {
                    if($result->getCode() === MDB_ERROR_ALREADY_EXISTS) {
                        $this->warnings[] = 'Index already exists: '.$index_name;
                        if($overwrite) {
                            $this->database->debug('Overwritting Index');
                            $result = $this->database->dropIndex($table_name, $index_name);
                            if(MDB::isError($result)) {
                                break;
                            }
                            $result = $this->database->createIndex($table_name, $index_name, $index);
                            if(MDB::isError($result)) {
                                break;
                            }
                        } else {
                            $result = MDB_OK;
                        }
                    } else {
                        $this->database->debug('Create index error: '.$table_name);
                        break;
                    }
                }
            }
        }
        if(MDB::isError($result)) {
            $result = $this->database->dropTable($table_name);
            if(MDB::isError($result)) {
                $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                    'could not drop the table ('
                    .$result->getMessage().' ('.$result->getUserinfo(),'))',
                    'MDB_Error', TRUE);
            }
            return($result);
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ _dropTable()

    /**
     * drop a table
     *
     * @param string $table_name    name of the table to be dropped
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _dropTable($table_name)
    {
        return($this->database->dropTable($table_name));
    }

    // }}}
    // {{{ _createSequence()

    /**
     * create a sequence
     *
     * @param string $sequence_name  name of the sequence to be created
     * @param array  $sequence       multi dimensional array that containts the
     *                               structure and optional data of the table
     * @param string $created_on_table
     * @param boolean $overwrite    determine if the sequence should be overwritten
                                    if it already exists
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _createSequence($sequence_name, $sequence, $created_on_table, $overwrite = FALSE)
    {
        if(!$this->database->support('Sequences')) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'sequences are not supported'));
        }
        if(!isset($sequence_name) || !strcmp($sequence_name, '')) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'no valid sequence name specified'));
        }
        $this->database->debug('Create sequence: '.$sequence_name);
        if(isset($sequence['start']) && $sequence['start'] != '') {
            $start = $sequence['start'];
        } else if(isset($sequence['on']) && !$created_on_table) {
            $table = $sequence['on']['table'];
            $field = $sequence['on']['field'];
            if($this->database->support('Summaryfunctions')) {
                $field = "MAX($field)";
            }
            $start = $this->database->queryOne("SELECT $field FROM $table");
            if(MDB::isError($start)) {
                return($start);
            }
        } else {
            $start = 1;
        }
        
        $this->expectError(MDB_ERROR_ALREADY_EXISTS);
        $result = $this->database->createSequence($sequence_name, $start);
        $this->popExpect();
        if(MDB::isError($result)) {
            if($result->getCode() === MDB_ERROR_ALREADY_EXISTS) {
                $this->warnings[] = 'Sequence already exists: '.$sequence_name;
                if($overwrite) {
                    $this->database->debug('Overwritting Sequence');
                    $result = $this->database->dropSequence($sequence_name);
                    if(MDB::isError($result)) {
                        return($result);
                    }
                    $result = $this->database->createSequence($sequence_name, $start);
                    if(MDB::isError($result)) {
                        return($result);
                    }
                } else {
                    return(MDB_OK);
                }
            } else {
                $this->database->debug('Create sequence error: '.$sequence_name);
                return($result);
            }
        }
    }

    // }}}
    // {{{ _dropSequence()

    /**
     * drop a table
     *
     * @param string $sequence_name    name of the sequence to be dropped
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _dropSequence($sequence_name)
    {
        if(!$this->database->support('Sequences')) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'sequences are not supported'));
        }
        $this->database->debug('Dropping sequence: '.$sequence_name);
        if(!isset($sequence_name) || !strcmp($sequence_name, '')) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'no valid sequence name specified'));
        }
        return($this->database->dropSequence($sequence_name));
    }

    // }}}
    // {{{ _createDatabase()

    /**
     * Create a database space within which may be created database objects
     * like tables, indexes and sequences. The implementation of this function
     * is highly DBMS specific and may require special permissions to run
     * successfully. Consult the documentation or the DBMS drivers that you
     * use to be aware of eventual configuration requirements.
     *
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _createDatabase()
    {
        if(!isset($this->database_definition['name'])
            || !strcmp($this->database_definition['name'], '')
        ) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'no valid database name specified'));
        }
        $create = (isset($this->database_definition['create']) && $this->database_definition['create']);
        $overwrite = (isset($this->database_definition['overwrite']) && $this->database_definition['overwrite']);
        if($create) {
            $this->database->debug('Create database: '.$this->database_definition['name']);
            $this->expectError(MDB_ERROR_ALREADY_EXISTS);
            $result = $this->database->createDatabase($this->database_definition['name']);
            $this->popExpect();
            if(MDB::isError($result)) {
                if($result->getCode() === MDB_ERROR_ALREADY_EXISTS) {
                    $this->warnings[] = 'Database already exists: '.$this->database_definition['name'];
                    if($overwrite) {
                        $this->database->debug('Overwritting Database');
                        $result = $this->database->dropDatabase($this->database_definition['name']);
                        if(MDB::isError($result)) {
                            return($result);
                        }
                        $result = $this->database->createDatabase($this->database_definition['name']);
                        if(MDB::isError($result)) {
                            return($result);
                        }
                    } else {
                        $result = MDB_OK;
                    }
                } else {
                    $this->database->debug('Create database error.');
                    return($result);
                }
            }
        }
        $previous_database_name = $this->database->setDatabase($this->database_definition['name']);
        if(($support_transactions = $this->database->support('Transactions'))
            && MDB::isError($result = $this->database->autoCommit(FALSE))
        ) {
            return($result);
        }

        $created_objects = 0;
        if(isset($this->database_definition['TABLES'])
            && is_array($this->database_definition['TABLES'])
        ) {
            foreach($this->database_definition['TABLES'] as $table_name => $table) {
                $result = $this->_createTable($table_name, $table, $overwrite);
                if(MDB::isError($result)) {
                    break;
                }
                $created_objects++;
            }
        }
        if(!MDB::isError($result) 
            && isset($this->database_definition['SEQUENCES'])
            && is_array($this->database_definition['SEQUENCES'])
        ) {
            foreach($this->database_definition['SEQUENCES'] as $sequence_name => $sequence) {
                $result = $this->_createSequence($sequence_name, $sequence, 0, $overwrite);
                
                if(MDB::isError($result)) {
                    break;
                }
                $created_objects++;
            }
        }
        
        if(MDB::isError($result)) {
            if($created_objects) {
                if($support_transactions) {
                    $res = $this->database->rollback();
                    if(MDB::isError($res))
                        $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                            'Could not rollback the partially created database alterations ('
                            .$result->getMessage().' ('.$result->getUserinfo(),'))',
                            'MDB_Error', TRUE);
                } else {
                    $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'the database was only partially created ('
                        .$result->getMessage().' ('.$result->getUserinfo(),'))',
                        'MDB_Error', TRUE);
                }
            }
        } else {
            if($support_transactions) {
                $res = $this->database->autoCommit(TRUE);
                if(MDB::isError($res))
                    $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Could not end transaction after successfully created the database ('
                        .$res->getMessage().' ('.$res->getUserinfo(),'))',
                        'MDB_Error', TRUE);
            }
        }
        
        $this->database->setDatabase($previous_database_name);
        
        if(MDB::isError($result)
            && $create
            && MDB::isError($res = $this->database->dropDatabase($this->database_definition['name']))
        ) {
            return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'Could not drop the created database after unsuccessful creation attempt ('
                .$res->getMessage().' ('.$res->getUserinfo(),'))',
                'MDB_Error', TRUE));
        }
        
        if(MDB::isError($result)) {
            return($result);
        }
        
        return(MDB_OK);
    }

    // }}}
    // {{{ _addDefinitionChange()

    /**
     * add change to an array of multiple changes
     *
     * @param array  &$changes
     * @param string $definition
     * @param string $item
     * @param array  $change
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _addDefinitionChange(&$changes, $definition, $item, $change)
    {
        if(!isset($changes[$definition][$item])) {
            $changes[$definition][$item] = array();
        }
        foreach($change as $change_data_name => $change_data) {
            if(isset($change_data) && is_array($change_data)) {
                if(!isset($changes[$definition][$item][$change_data_name])) {
                    $changes[$definition][$item][$change_data_name] = array();
                }
                foreach($change_data as $change_part_name => $change_part) {
                    $changes[$definition][$item][$change_data_name][$change_part_name] = $change_part;
                }
            } else {
                $changes[$definition][$item][$change_data_name] = $change_data;
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ _compareDefinitions()

    /**
     * compare a previous definition with the currenlty parsed definition
     *
     * @param array multi dimensional array that contains the previous definition
     * @return mixed array of changes on success, or a MDB error object
     * @access private
     */
    function _compareDefinitions($previous_definition)
    {
        $defined_tables = $changes = array();
        if(isset($this->database_definition['TABLES']) && is_array($this->database_definition['TABLES'])) {
            foreach($this->database_definition['TABLES'] as $table_name => $table) {
                $was_table_name = $table['was'];
                if(isset($previous_definition['TABLES'][$table_name])
                    && isset($previous_definition['TABLES'][$table_name]['was'])
                    && !strcmp($previous_definition['TABLES'][$table_name]['was'], $was_table_name)
                ) {
                    $was_table_name = $table_name;
                }
                if(isset($previous_definition['TABLES'][$was_table_name])) {
                    if(strcmp($was_table_name, $table_name)) {
                        $this->_addDefinitionChange($changes, 'TABLES', $was_table_name, array('name' => $table_name));
                        $this->database->debug("Renamed table '$was_table_name' to '$table_name'");
                    }
                    if(isset($defined_tables[$was_table_name])) {
                        return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                            'the table "'.$was_table_name.'" was specified as base of more than of table of the database',
                            'MDB_Error', TRUE));
                    }
                    $defined_tables[$was_table_name] = 1;
                    
                    $previous_fields = $previous_definition['TABLES'][$was_table_name]['FIELDS'];
                    $defined_fields = array();
                    if(isset($table['FIELDS']) && is_array($table['FIELDS'])) {
                        foreach($table['FIELDS'] as $field_name => $field) {
                            $was_field_name = $field['was'];
                            if(isset($previous_fields[$field_name])
                                && isset($previous_fields[$field_name]['was'])
                                && !strcmp($previous_fields[$field_name]['was'], $was_field_name)
                            ) {
                                $was_field_name = $field_name;
                            }
                            if(isset($previous_fields[$was_field_name])) {
                                if(strcmp($was_field_name, $field_name)) {
                                    $query = $this->database->getFieldDeclaration($field_name, $field);
                                    if(MDB::isError($query)) {
                                        return($query);
                                    }
                                    $this->_addDefinitionChange($changes, 'TABLES', $was_table_name,
                                        array(
                                            'RenamedFields' => array(
                                                $was_field_name => array(
                                                    'name' => $field_name,
                                                    'Declaration' => $query
                                                )
                                            )
                                        )
                                    );
                                    $this->database->debug("Renamed field '$was_field_name' to '$field_name' in table '$table_name'");
                                }
                                if(isset($defined_fields[$was_field_name])) {
                                    return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                                        'the field "'.$was_table_name.'" was specified as base of more than one field of table',
                                        'MDB_Error', TRUE));
                                }
                                $defined_fields[$was_field_name] = 1;
                                $change = array();
                                if($field['type'] == $previous_fields[$was_field_name]['type']) {
                                    switch($field['type']) {
                                        case 'integer':
                                            $previous_unsigned = isset($previous_fields[$was_field_name]['unsigned']);
                                            $unsigned = isset($fields[$field_name]['unsigned']);
                                            if(strcmp($previous_unsigned, $unsigned)) {
                                                $change['unsigned'] = $unsigned;
                                                $this->database->debug("Changed field '$field_name' type from '".($previous_unsigned ? 'unsigned ' : '').$previous_fields[$was_field_name]['type']."' to '".($unsigned ? 'unsigned ' : '').$field['type']."' in table '$table_name'");
                                            }
                                            break;
                                        case 'text':
                                        case 'clob':
                                        case 'blob':
                                            $previous_length = (isset($previous_fields[$was_field_name]['length']) ? $previous_fields[$was_field_name]['length'] : 0);
                                            $length = (isset($field['length']) ? $field['length'] : 0);
                                            if(strcmp($previous_length, $length)) {
                                                $change['length'] = $length;
                                                $this->database->debug("Changed field '$field_name' length from '".$previous_fields[$was_field_name]['type'].($previous_length == 0 ? ' no length' : "($previous_length)")."' to '".$field['type'].($length == 0 ? ' no length' : "($length)")."' in table '$table_name'");
                                            }
                                            break;
                                        case 'date':
                                        case 'timestamp':
                                        case 'time':
                                        case 'boolean':
                                        case 'float':
                                        case 'decimal':
                                            break;
                                        default:
                                            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                                                'type "'.$field['type'].'" is not yet supported',
                                                'MDB_Error', TRUE));
                                    }
                                    
                                    $previous_notnull = isset($previous_fields[$was_field_name]['notnull']);
                                    $notnull = isset($field['notnull']);
                                    if($previous_notnull != $notnull) {
                                        $change['ChangedNotNull'] = 1;
                                        if($notnull) {
                                            $change['notnull'] = isset($field['notnull']);
                                        }
                                        $this->database->debug("Changed field '$field_name' notnull from $previous_notnull to $notnull in table '$table_name'");
                                    }
                                    
                                    $previous_default = isset($previous_fields[$was_field_name]['default']);
                                    $default = isset($field['default']);
                                    if(strcmp($previous_default, $default)) {
                                        $change['ChangedDefault'] = 1;
                                        if($default) {
                                            $change['default'] = $field['default'];
                                        }
                                        $this->database->debug("Changed field '$field_name' default from ".($previous_default ? "'".$previous_fields[$was_field_name]['default']."'" : 'NULL').' TO '.($default ? "'".$fields[$field_name]['default']."'" : 'NULL')." IN TABLE '$table_name'");
                                    } else {
                                        if($default
                                            && strcmp($previous_fields[$was_field_name]['default'], $field['default'])
                                        ) {
                                            $change['ChangedDefault'] = 1;
                                            $change['default'] = $field['default'];
                                            $this->database->debug("Changed field '$field_name' default from '".$previous_fields[$was_field_name]['default']."' to '".$fields[$field_name]['default']."' in table '$table_name'");
                                        }
                                    }
                                } else {
                                    $change['type'] = $field['type'];
                                    $this->database->debug("Changed field '$field_name' type from '".$previous_fields[$was_field_name]['type']."' to '".$fields[$field_name]['type']."' in table '$table_name'");
                                }
                                if(count($change)) {
                                    $query = $this->database->getFieldDeclaration($field_name, $field);
                                    if(MDB::isError($query)) {
                                        return($query);
                                    }
                                    $change['Declaration'] = $query;
                                    $change['Definition'] = $field;
                                    $this->_addDefinitionChange($changes, 'TABLES', $was_table_name, array('ChangedFields' => array($field_name => $change)));
                                }
                            } else {
                                if(strcmp($field_name, $was_field_name)) {
                                    return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                                        'it was specified a previous field name ("'
                                        .$was_field_name.'") for field "'.$field_name.'" of table "'
                                        .$table_name.'" that does not exist',
                                        'MDB_Error', TRUE));
                                }
                                $query = $this->database->getFieldDeclaration($field_name, $field);
                                if(MDB::isError($query)) {
                                    return($query);
                                }
                                $change['Declaration'] = $query;
                                $this->_addDefinitionChange($changes, 'TABLES', $table_name, array('AddedFields' => array($field_name => $change)));
                                $this->database->debug("Added field '$field_name' to table '$table_name'");
                            }
                        }
                    }
                    if(isset($previous_fields) && is_array($previous_fields)) {
                        foreach ($previous_fields as $field_previous_name => $field_previous) {
                            if(!isset($defined_fields[$field_previous_name])) {
                                $this->_addDefinitionChange($changes, 'TABLES', $table_name, array('RemovedFields' => array($field_previous_name => array())));
                                $this->database->debug("Removed field '$field_name' from table '$table_name'");
                            }
                        }
                    }
                    $indexes = array();
                    if(isset($this->database_definition['TABLES'][$table_name]['INDEXES'])
                        && is_array($this->database_definition['TABLES'][$table_name]['INDEXES'])
                    ) {
                        $indexes = $this->database_definition['TABLES'][$table_name]['INDEXES'];
                    }
                    $previous_indexes = array();
                    if(isset($previous_definition['TABLES'][$was_table_name]['INDEXES'])
                        && is_array($previous_definition['TABLES'][$was_table_name]['INDEXES'])
                    ) {
                        $previous_indexes = $previous_definition['TABLES'][$was_table_name]['INDEXES'];
                    }
                    $defined_indexes = array();
                    foreach($indexes as $index_name => $index) {
                        $was_index_name = $index['was'];
                        if(isset($previous_indexes[$index_name])
                            && isset($previous_indexes[$index_name]['was'])
                            && !strcmp($previous_indexes[$index_name]['was'], $was_index_name)
                        ) {
                            $was_index_name = $index_name;
                        }
                        if(isset($previous_indexes[$was_index_name])) {
                            $change = array();
                            
                            if(strcmp($was_index_name, $index_name)) {
                                $change['name'] = $was_index_name;
                                $this->database->debug("Changed index '$was_index_name' name to '$index_name' in table '$table_name'");
                            }
                            if(isset($defined_indexes[$was_index_name])) {
                                return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                                    'the index "'.$was_index_name.'" was specified as base of'
                                    .' more than one index of table "'.$table_name.'"',
                                    'MDB_Error', TRUE));
                            }
                            $defined_indexes[$was_index_name] = 1;
                            
                            $previous_unique = isset($previous_indexes[$was_index_name]['unique']);
                            $unique = isset($index['unique']);
                            if($previous_unique != $unique) {
                                $change['ChangedUnique'] = 1;
                                if($unique) {
                                    $change['unique'] = $unique;
                                }
                                $this->database->debug("Changed index '$index_name' unique from $previous_unique to $unique in table '$table_name'");
                            }
                            $defined_fields = array();
                            $previous_fields = $previous_indexes[$was_index_name]['FIELDS'];
                            if(isset($index['FIELDS']) && is_array($index['FIELDS'])) {
                                foreach($index['FIELDS'] as $field_name => $field) {
                                    if(isset($previous_fields[$field_name])) {
                                        $defined_fields[$field_name] = 1;
                                        $sorting = (isset($field['sorting']) ? $field['sorting'] : '');
                                        $previous_sorting = (isset($previous_fields[$field_name]['sorting']) ? $previous_fields[$field_name]['sorting'] : '');
                                        if(strcmp($sorting, $previous_sorting)) {
                                            $this->database->debug("Changed index field '$field_name' sorting default from '$previous_sorting' to '$sorting' in table '$table_name'");
                                            $change['ChangedFields'] = 1;
                                        }
                                    } else {
                                        $change['ChangedFields'] = 1;
                                        $this->database->debug("Added field '$field_name' to index '$index_name' of table '$table_name'");
                                    }
                                }
                            }
                            if(isset($previous_fields) && is_array($previous_fields)) {
                                foreach($previous_fields as $field_name => $field) {
                                    if(!isset($defined_fields[$field_name])) {
                                        $change['ChangedFields'] = 1;
                                        $this->database->debug("Removed field '$field_name' from index '$index_name' of table '$table_name'");
                                    }
                                }
                            }
                            
                            if(count($change)) {
                                $this->_addDefinitionChange($changes, 'INDEXES', $table_name,array('ChangedIndexes' => array($index_name => $change)));
                            }
                        } else {
                            if(strcmp($index_name, $was_index_name)) {
                                return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                                    'it was specified a previous index name ("'.$was_index_name
                                    .') for index "'.$index_name.'" of table "'.$table_name.'" that does not exist',
                                    'MDB_Error', TRUE));
                            }
                            $this->_addDefinitionChange($changes, 'INDEXES', $table_name,array('AddedIndexes' => array($index_name => $indexes[$index_name])));
                            $this->database->debug("Added index '$index_name' to table '$table_name'");
                        }
                    }
                    foreach($previous_indexes as $index_previous_name => $index_previous) {
                        if(!isset($defined_indexes[$index_previous_name])) {
                            $this->_addDefinitionChange($changes, 'INDEXES', $table_name, array('RemovedIndexes' => array($index_previous_name => $was_table_name)));
                            $this->database->debug("Removed index '$index_name' from table '$table_name'");
                        }
                    }
                } else {
                    if(strcmp($table_name, $was_table_name)) {
                        return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                            'it was specified a previous table name ("'
                            .$was_table_name.'") for table "'.$table_name.'" that does not exist',
                            'MDB_Error', TRUE));
                    }
                    $this->_addDefinitionChange($changes, 'TABLES', $table_name,array('Add' => 1));
                    $this->database->debug("Added table '$table_name'");
                }
            }
            if(isset($previous_definition['TABLES']) && is_array($previous_definition['TABLES'])) {
                foreach ($previous_definition['TABLES'] as $table_name => $table) {
                    if(!isset($defined_tables[$table_name])) {
                        $this->_addDefinitionChange($changes, 'TABLES', $table_name, array('Remove' => 1));
                        $this->database->debug("Removed table '$table_name'");
                    }
                }
            }
            if(isset($this->database_definition['SEQUENCES']) && is_array($this->database_definition['SEQUENCES'])) {
                foreach ($this->database_definition['SEQUENCES'] as $sequence_name => $sequence) {
                    $was_sequence_name = $sequence['was'];
                    if(isset($previous_definition['SEQUENCES'][$sequence_name])
                        && isset($previous_definition['SEQUENCES'][$sequence_name]['was'])
                        && !strcmp($previous_definition['SEQUENCES'][$sequence_name]['was'], $was_sequence_name)
                    ) {
                        $was_sequence_name = $sequence_name;
                    }
                    if(isset($previous_definition['SEQUENCES'][$was_sequence_name])) {
                        if(strcmp($was_sequence_name, $sequence_name)) {
                            $this->_addDefinitionChange($changes, 'SEQUENCES', $was_sequence_name,array('name' => $sequence_name));
                            $this->database->debug("Renamed sequence '$was_sequence_name' to '$sequence_name'");
                        }
                        if(isset($defined_sequences[$was_sequence_name])) {
                            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                                'the sequence "'.$was_sequence_name.'" was specified as base'
                                .' of more than of sequence of the database',
                                'MDB_Error', TRUE));
                        }
                        $defined_sequences[$was_sequence_name] = 1;
                        $change = array();
                        if(strcmp($sequence['start'], $previous_definition['SEQUENCES'][$was_sequence_name]['start'])) {
                            $change['start'] = $this->database_definition['SEQUENCES'][$sequence_name]['start'];
                            $this->database->debug("Changed sequence '$sequence_name' start from '".$previous_definition['SEQUENCES'][$was_sequence_name]['start']."' to '".$this->database_definition['SEQUENCES'][$sequence_name]['start']."'");
                        }
                        if(strcmp($sequence['on']['table'], $previous_definition['SEQUENCES'][$was_sequence_name]['on']['table'])
                            || strcmp($sequence['on']['field'], $previous_definition['SEQUENCES'][$was_sequence_name]['on']['field'])
                        ) {
                            $change['on'] = $sequence['on'];
                            $this->database->debug("Changed sequence '$sequence_name' on table field from '".$previous_definition['SEQUENCES'][$was_sequence_name]['on']['table'].'.'.$previous_definition['SEQUENCES'][$was_sequence_name]['on']['field']."' to '".$this->database_definition['SEQUENCES'][$sequence_name]['on']['table'].'.'.$this->database_definition['SEQUENCES'][$sequence_name]['on']['field']."'");
                        }
                        if(count($change)) {
                            $this->_addDefinitionChange($changes, 'SEQUENCES', $was_sequence_name,array('Change' => array($sequence_name => array($change))));
                        }
                    } else {
                        if(strcmp($sequence_name, $was_sequence_name)) {
                            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                                'it was specified a previous sequence name ("'.$was_sequence_name
                                .'") for sequence "'.$sequence_name.'" that does not exist',
                                'MDB_Error', TRUE));
                        }
                        $this->_addDefinitionChange($changes, 'SEQUENCES', $sequence_name, array('Add' => 1));
                        $this->database->debug("Added sequence '$sequence_name'");
                    }
                }
            }
            if(isset($previous_definition['SEQUENCES']) && is_array($previous_definition['SEQUENCES'])) {
                foreach ($previous_definition['SEQUENCES'] as $sequence_name => $sequence) {
                    if(!isset($defined_sequences[$sequence_name])) {
                        $this->_addDefinitionChange($changes, 'SEQUENCES', $sequence_name, array('Remove' => 1));
                        $this->database->debug("Removed sequence '$sequence_name'");
                    }
                }
            }
        }
        return($changes);
    }

    // }}}
    // {{{ _alterDatabase()

    /**
     * Execute the necessary actions to implement the requested changes
     * in a database structure.
     *
     * @param array $previous_definition an associative array that contains
     * the definition of the database structure before applying the requested
     * changes. The definition of this array may be built separately, but
     * usually it is built by the Parse method the Metabase parser class.
     * @param array $changes an associative array that contains the definition of
     * the changes that are meant to be applied to the database structure.
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _alterDatabase($previous_definition, $changes)
    {
        $result = '';
        if(isset($changes['TABLES']) && is_array($changes['TABLES'])) {
            foreach($changes['TABLES'] as $table_name => $table) {
                if(isset($table['Add']) || isset($table['Remove'])) {
                    continue;
                }
                $result = $this->database->alterTable($table_name, $table, 1);
                if(MDB::isError($result)) {
                    return($result);
                }
            }
        }
        if(isset($changes['SEQUENCES']) && is_array($changes['SEQUENCES'])) {
            if(!$this->database->support('Sequences')) {
                return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'sequences are not supported'));
            }
            foreach($changes['SEQUENCES'] as $sequence) {
                if(isset($sequence['Add'])
                    || isset($sequence['Remove'])
                    || isset($sequence['Change'])
                ) {
                    continue;
                }
                return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'some sequences changes are not yet supported'));
            }
        }
        if(isset($changes['INDEXES']) && is_array($changes['INDEXES'])) {
            if(!$this->database->support('Indexes')) {
                return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'indexes are not supported'));
            }
            foreach($changes['INDEXES'] as $index) {
                $table_changes = count($index);
                if(isset($index['AddedIndexes'])) {
                    $table_changes--;
                }
                if(isset($index['RemovedIndexes'])) {
                    $table_changes--;
                }
                if(isset($index['ChangedIndexes'])) {
                    $table_changes--;
                }
                if($table_changes) {
                    return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                        'index alteration not yet supported'));
                }
            }
        }
        
        $previous_database_name = $this->database->setDatabase($this->database_definition['name']);
        if(($support_transactions = $this->database->support('Transactions'))
            && MDB::isError($result = $this->database->autoCommit(FALSE))
        ) {
            return($result);
        }
        $error = '';
        $alterations = 0;
        if(isset($changes['INDEXES']) && is_array($changes['INDEXES'])) {
            foreach($changes['INDEXES'] as $index_name => $index) {
                if(isset($index['RemovedIndexes']) && is_array($index['RemovedIndexes'])) {
                    foreach($index['RemovedIndexes'] as $index_remove_name => $index_remove) {
                        $result = $this->database->dropIndex($index_name,$index_remove_name);
                        if(MDB::isError($result)) {
                            break;
                        }
                        $alterations++;
                    }
                }
                if(!MDB::isError($result)
                    && is_array($index['ChangedIndexes'])
                ) {
                    foreach($index['ChangedIndexes'] as $index_changed_name => $index_changed) {
                        $was_name = (isset($indexes[$name]['name']) ? $indexes[$index_changed_name]['name'] : $index_changed_name);
                        $result = $this->database->dropIndex($index_name, $was_name);
                        if(MDB::isError($result)) {
                            break;
                        }
                        $alterations++;
                    }
                }
                if(MDB::isError($result)) {
                    break;
                }
            }
        }
        if(!MDB::isError($result) && isset($changes['TABLES'])
            && is_array($changes['TABLES'])
        ) {
            foreach($changes['TABLES'] as $table_name => $table) {
                if(isset($table['Remove'])) {
                    $result = $this->_dropTable($table_name);
                    if(!MDB::isError($result)) {
                        $alterations++;
                    }
                } else {
                    if(!isset($table['Add'])) {
                        $result = $this->database->alterTable($table_name, $changes['TABLES'][$table_name], 0);
                        if(!MDB::isError($result)) {
                            $alterations++;
                        }
                    }
                }
                if(MDB::isError($result)) {
                    break;
                }
            }
            foreach($changes['TABLES'] as $table_name => $table) {
                if(isset($table['Add'])) {
                    $result = $this->_createTable($table_name, $this->database_definition['TABLES'][$table_name]);
                    if(!MDB::isError($result)) {
                        $alterations++;
                    }
                }
                if(MDB::isError($result)) {
                    break;
                }
            }
        }
        if(!MDB::isError($result) && isset($changes['SEQUENCES']) && is_array($changes['SEQUENCES'])) {
            foreach($changes['SEQUENCES'] as $sequence_name => $sequence) {
                if(isset($sequence['Add'])) {
                    $created_on_table = 0;
                    if(isset($this->database_definition['SEQUENCES'][$sequence_name]['on'])) {
                        $table = $this->database_definition['SEQUENCES'][$sequence_name]['on']['table'];
                        if(isset($changes['TABLES'])
                            && isset($changes['TABLES'][$table_name])
                            && isset($changes['TABLES'][$table_name]['Add'])
                        ) {
                            $created_on_table = 1;
                        }
                    }
                    
                    $result = $this->_createSequence($sequence_name,
                        $this->database_definition['SEQUENCES'][$sequence_name], $created_on_table);
                    if(!MDB::isError($result)) {
                        $alterations++;
                    }
                } else {
                    if(isset($sequence['Remove'])) {
                        if(!strcmp($error = $this->_dropSequence($sequence_name), '')) {
                            $alterations++;
                        }
                    } else {
                        if(isset($sequence['Change'])) {
                            $created_on_table = 0;
                            if(isset($this->database_definition['SEQUENCES'][$sequence_name]['on'])) {
                                $table = $this->database_definition['SEQUENCES'][$sequence_name]['on']['table'];
                                if(isset($changes['TABLES'])
                                    && isset($changes['TABLES'][$table_name])
                                    && isset($changes['TABLES'][$table_name]['Add'])
                                ) {
                                    $created_on_table = 1;
                                }
                            }
                            if(!MDB::isError($result = $this->_dropSequence(
                                    $this->database_definition['SEQUENCES'][$sequence_name]['was']), '')
                                && !MDB::isError($result = $this->_createSequence(
                                    $sequence_name, $this->database_definition['SEQUENCES'][$sequence_name], $created_on_table), '')
                            ) {
                                $alterations++;
                            }
                        } else {
                            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                                'changing sequences is not yet supported'));
                        }
                    }
                }
                if(MDB::isError($result)) {
                    break;
                }
            }
        }
        if(!MDB::isError($result) && isset($changes['INDEXES']) && is_array($changes['INDEXES'])) {
            foreach($changes['INDEXES'] as $table_name => $indexes) {
                if(isset($indexes['ChangedIndexes'])) {
                    $changedindexes = $indexes['ChangedIndexes'];
                    foreach($changedindexes as $index_name => $index) {
                        $result = $this->database->createIndex($table_name, $index_name,
                            $this->database_definition['TABLES'][$table_name]['INDEXES'][$index_name]);
                        if(MDB::isError($result)) {
                            break;
                        }
                        $alterations++;
                    }
                }
                if(!MDB::isError($result)
                    && isset($indexes['AddedIndexes'])
                ) {
                    $addedindexes = $indexes['AddedIndexes'];
                    foreach($addedindexes as $index_name => $index) {
                        $result = $this->database->createIndex($table_name, $index_name,
                            $this->database_definition['TABLES'][$table_name]['INDEXES'][$index_name]);
                        if(MDB::isError($result)) {
                            break;
                        }
                        $alterations++;
                    }
                }
                if(MDB::isError($result)) {
                    break;
                }
            }
        }
        if($alterations && MDB::isError($result)) {
            if($support_transactions) {
                $res = $this->database->rollback();
                if(MDB::isError($res))
                    $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'Could not rollback the partially created database alterations ('
                        .$result->getMessage().' ('.$result->getUserinfo(),'))',
                        'MDB_Error', TRUE);
            } else {
                $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                    'the requested database alterations were only partially implemented ('
                    .$result->getMessage().' ('.$result->getUserinfo(),'))',
                    'MDB_Error', TRUE);
            }
        }
        if($support_transactions) {
            $result = $this->database->autoCommit(TRUE);
            if(MDB::isError($result)) {
                $result = $this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                    'Could not end transaction after successfully implemented the requested database alterations ('
                    .$result->getMessage().' ('.$result->getUserinfo(),'))',
                    'MDB_Error', TRUE);
            }
        }
        $this->database->setDatabase($previous_database_name);
        return($result);
    }

    // }}}
    // {{{ _escapeSpecialCharacters()

    /**
     * add escapecharacters to all special characters in a string
     *
     * @param string $string string that should be escaped
     * @return string escaped string
     * @access private
     */
    function _escapeSpecialCharacters($string)
    {
        if(gettype($string) != 'string') {
            $string = strval($string);
        }
        for($escaped = '', $character = 0;
            $character < strlen($string);
            $character++)
        {
            switch($string[$character]) {
                case '\"':
                case '>':
                case '<':
                case '&':
                    $code = ord($string[$character]);
                    break;
                default:
                    $code = ord($string[$character]);
                    if($code < 32 || $code>127) {
                        break;
                    }
                    $escaped .= $string[$character];
                    continue 2;
            }
            $escaped .= "&#$code;";
        }
        return($escaped);
    }

    // }}}
    // {{{ _dumpSequence()

    /**
     * dump the structure of a sequence
     *
     * @param string  $sequence_name
     * @param string  $eol
     * @return mixed string with xml seqeunce definition on success, or a MDB error object
     * @access private
     */
    function _dumpSequence($sequence_name, $eol, $dump = MDB_MANAGER_DUMP_ALL)
    {
        $sequence_definition = $this->database_definition['SEQUENCES'][$sequence_name];
        $buffer = "$eol <sequence>$eol  <name>$sequence_name</name>$eol";
        if($dump == MDB_MANAGER_DUMP_ALL || $dump == MDB_MANAGER_DUMP_CONTENT) {
            if(isset($sequence_definition['start'])) {
                $start = $sequence_definition['start'];
                $buffer .= "  <start>$start</start>$eol";
            }
        }
        if(isset($sequence_definition['on'])) {
            $buffer .= "  <on>$eol   <table>".$sequence_definition['on']['table']."</table>$eol   <field>".$sequence_definition['on']['field']."</field>$eol  </on>$eol";
        }
        $buffer .= " </sequence>$eol";
        return($buffer);
    }

    // }}}
    // {{{ parseDatabaseDefinitionFile()

    /**
     * Parse a database definition file by creating a Metabase schema format
     * parser object and passing the file contents as parser input data stream.
     *
     * @param string $input_file the path of the database schema file.
     * @param array $variables an associative array that the defines the text
     * string values that are meant to be used to replace the variables that are
     * used in the schema description.
     * @param bool $fail_on_invalid_names (optional) make function fail on invalid
     * names
     * @return mixed MDB_OK on success, or a MDB error object
     * @access public
     */
    function parseDatabaseDefinitionFile($input_file, $variables, $fail_on_invalid_names = 1)
    {
        $parser =& new MDB_Parser($variables, $fail_on_invalid_names);
        $result = $parser->setInputFile($input_file);
        if(MDB::isError($result)) {
            return($result);
        };
        $result = $parser->parse();
        if(MDB::isError($result)) {
            return($result);
        };
        if(MDB::isError($parser->error)) {
            return($parser->error);
        }
        return($parser->database_definition);
    }

    // }}}
    // {{{ _debugDatabaseChanges()

    /**
     * Dump the changes between two database definitions.
     *
     * @param array $changes an associative array that specifies the list
     * of database definitions changes as returned by the _compareDefinitions
     * manager class function.
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _debugDatabaseChanges($changes)
    {
        if(isset($changes['TABLES'])) {
            foreach($changes['TABLES'] as $table_name => $table)
            {
                $this->database->debug("$table_name:");
                if(isset($table['Add'])) {
                    $this->database->debug("\tAdded table '$table_name'");
                } elseif(isset($table['Remove'])) {
                    $this->database->debug("\tRemoved table '$table_name'");
                } else {
                    if(isset($table['name'])) {
                        $this->database->debug("\tRenamed table '$table_name' to '".$table['name']."'");
                    }
                    if(isset($table['AddedFields'])) {
                        foreach($table['AddedFields'] as $field_name => $field) {
                            $this->database->debug("\tAdded field '".$field_name."'");
                        }
                    }
                    if(isset($table['RemovedFields'])) {
                        foreach($table['RemovedFields'] as $field_name => $field) {
                            $this->database->debug("\tRemoved field '".$field_name."'");
                        }
                    }
                    if(isset($table['RenamedFields'])) {
                        foreach($table['RenamedFields'] as $field_name => $field) {
                            $this->database->debug("\tRenamed field '".$field_name."' to '".$field['name']."'");
                        }
                    }
                    if(isset($table['ChangedFields'])) {
                        foreach($table['ChangedFields'] as $field_name => $field) {
                            if(isset($field['type'])) {
                                $this->database->debug(
                                    "\tChanged field '$field_name' type to '".$field['type']."'");
                            }
                            if(isset($field['unsigned'])) {
                                $this->database->debug(
                                    "\tChanged field '$field_name' type to '".
                                    ($field['unsigned'] ? '' : 'not ')."unsigned'");
                            }
                            if(isset($field['length'])) {
                                $this->database->debug(
                                    "\tChanged field '$field_name' length to '".
                                    ($field['length'] == 0 ? 'no length' : $field['length'])."'");
                            }
                            if(isset($field['ChangedDefault'])) {
                                $this->database->debug(
                                    "\tChanged field '$field_name' default to ".
                                    (isset($field['default']) ? "'".$field['default']."'" : 'NULL'));
                            }
                            if(isset($field['ChangedNotNull'])) {
                                $this->database->debug(
                                   "\tChanged field '$field_name' notnull to ".(isset($field['notnull']) ? "'1'" : '0'));
                            }
                        }
                    }
                }
            }
        }
        if(isset($changes['SEQUENCES'])) {
            foreach($changes['SEQUENCES'] as $sequence_name => $sequence)
            {
                $this->database->debug("$sequence_name:");
                if(isset($sequence['Add'])) {
                    $this->database->debug("\tAdded sequence '$sequence_name'");
                } elseif(isset($sequence['Remove'])) {
                    $this->database->debug("\tRemoved sequence '$sequence_name'");
                } else {
                    if(isset($sequence['name'])) {
                        $this->database->debug("\tRenamed sequence '$sequence_name' to '".$sequence['name']."'");
                    }
                    if(isset($sequence['Change'])) {
                        foreach($sequence['Change'] as $sequence_name => $sequence) {
                            if(isset($sequence['start'])) {
                                $this->database->debug(
                                    "\tChanged sequence '$sequence_name' start to '".$sequence['start']."'");
                            }
                        }
                    }
                }
            }
        }
        if(isset($changes['INDEXES'])) {
            foreach($changes['INDEXES'] as $table_name => $table)
            {
                $this->database->debug("$table_name:");
                if(isset($table['AddedIndexes'])) {
                    foreach($table['AddedIndexes'] as $index_name => $index) {
                        $this->database->debug("\tAdded index '".$index_name."' of table '$table_name'");
                    }
                }
                if(isset($table['RemovedIndexes'])) {
                    foreach($table['RemovedIndexes'] as $index_name => $index) {
                        $this->database->debug("\tRemoved index '".$index_name."' of table '$table_name'");
                    }
                }
                if(isset($table['ChangedIndexes'])) {
                    foreach($table['ChangedIndexes'] as $index_name => $index) {
                        if(isset($index['name'])) {
                            $this->database->debug(
                                "\tRenamed index '".$index_name."' to '".$index['name']."' on table '$table_name'");
                        }
                        if(isset($index['ChangedUnique'])) {
                            $this->database->debug(
                                "\tChanged index '".$index_name."' unique to '".
                                isset($index['unique'])."' on table '$table_name'");
                        }
                        if(isset($index['ChangedFields'])) {
                            $this->database->debug("\tChanged index '".$index_name."' on table '$table_name'");
                        }
                    }
                }
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ _dumpDatabaseContents()

    /**
     * Parse a database schema definition file and dump the respective structure
     * and contents.
     *
     * @param string $schema_file path of the database schema file.
     * @param mixed $setup_arguments an associative array that takes pairs of tag names and values
     * that define the setup arguments that are passed to the
     * MDB_Manager::connect function.
     * @param array $dump_arguments an associative array that takes pairs of tag names and values
     * that define dump options as defined for the MDB_Manager::DumpDatabase
     * function.
     * @param array $variables an associative array that the defines the text string values
     * that are meant to be used to replace the variables that are used in the
     * schema description as defined for the
     * MDB_Manager::parseDatabaseDefinitionFile function.
     * @return mixed MDB_OK on success, or a MDB error object
     * @access private
     */
    function _dumpDatabaseContents($schema_file, $setup_arguments, $dump_arguments, $variables)
    {
        $database_definition = $this->parseDatabaseDefinitionFile($schema_file,
            $variables, $this->options['fail_on_invalid_names']);
        if(MDB::isError($database_definition)) {
            return($database_definition);
        }
        
        $this->database_definition = $database_definition;
        
        $result = $this->connect($setup_arguments);
        if(MDB::isError($result)) {
            return($result);
        }
        
        return($this->dumpDatabase($dump_arguments));
    }

    // }}}
    // {{{ getDefinitionFromDatabase()

    /**
     * Attempt to reverse engineer a schema structure from an existing MDB
     * This method can be used if no xml schema file exists yet.
     * The resulting xml schema file may need some manual adjustments.
     *
     * @return mixed MDB_OK or array with all ambiguities on success, or a MDB error object
     * @access public
     */
    function getDefinitionFromDatabase()
    {
        $database = $this->database->database_name;
        if(strlen($database) == 0) {
            return('it was not specified a valid database name');
        }
        $this->database_definition = array(
            'name' => $database,
            'create' => 1,
            'TABLES' => array()
        );
        $tables = $this->database->listTables();
        if(MDB::isError($tables)) {
            return($tables);
        }
        for($table = 0; $table < count($tables); $table++) {
            $table_name = $tables[$table];
            $fields = $this->database->listTableFields($table_name);
            if(MDB::isError($fields)) {
                return($fields);
            }
            $this->database_definition['TABLES'][$table_name] = array('FIELDS' => array());
            for($field = 0; $field < count($fields); $field++)
            {
                $field_name = $fields[$field];
                $definition = $this->database->getTableFieldDefinition($table_name, $field_name);
                if(MDB::isError($definition)) {
                    return($definition);
                }
                $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_name] = $definition[0][0];
                $field_choices = count($definition[0]);
                if($field_choices > 1) {
                    $warning = "There are $field_choices type choices in the table $table_name field $field_name (#1 is the default): ";
                    $field_choice_cnt = 1;
                    $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_name]['CHOICES'] = array();
                    foreach($definition[0] as $field_choice) {
                        $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_name]['CHOICES'][] = $field_choice;
                        $warning .= 'choice #'.($field_choice_cnt).': '.serialize($field_choice);
                        $field_choice_cnt++;
                    }
                    $this->warnings[] = $warning;
                }
                if(isset($definition[1])) {
                    $sequence = $definition[1]['definition'];
                    $sequence_name = $definition[1]['name'];
                    $this->database->debug('Implicitly defining sequence: '.$sequence_name);
                    if(!isset($this->database_definition['SEQUENCES'])) {
                        $this->database_definition['SEQUENCES'] = array();
                    }
                    $this->database_definition['SEQUENCES'][$sequence_name] = $sequence;
                }
                if(isset($definition[2])) {
                    $index = $definition[2]['definition'];
                    $index_name = $definition[2]['name'];
                    $this->database->debug('Implicitly defining index: '.$index_name);
                    if(!isset($this->database_definition['TABLES'][$table_name]['INDEXES'])) {
                        $this->database_definition['TABLES'][$table_name]['INDEXES'] = array();
                    }
                    $this->database_definition['TABLES'][$table_name]['INDEXES'][$index_name] = $index;
                }
            }
            $indexes = $this->database->listTableIndexes($table_name);
            if(MDB::isError($indexes)) {
                return($indexes);
            }
            if(is_array($indexes) && count($indexes) > 0 && !isset($this->database_definition['TABLES'][$table_name]['INDEXES'])) {
                $this->database_definition['TABLES'][$table_name]['INDEXES'] = array();
            }
            for($index = 0, $index_cnt = count($indexes); $index < $index_cnt; $index++)
            {
                $index_name = $indexes[$index];
                $definition = $this->database->getTableIndexDefinition($table_name, $index_name);
                if(MDB::isError($definition)) {
                    return($definition);
                }
               $this->database_definition['TABLES'][$table_name]['INDEXES'][$index_name] = $definition;
            }
            // ensure that all fields that have an index on them are set to not null
            if(isset($this->database_definition['TABLES'][$table_name]['INDEXES'])
                && is_array($this->database_definition['TABLES'][$table_name]['INDEXES'])
                && count($this->database_definition['TABLES'][$table_name]['INDEXES']) > 0
            ) {
                foreach($this->database_definition['TABLES'][$table_name]['INDEXES'] as $index_check_null) {
                    foreach($index_check_null['FIELDS'] as $field_name_check_null => $field_check_null) {
                        $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_name_check_null]['notnull'] = 1;
                    }
                }
            }
            // ensure that all fields that are set to not null also have a default value
            if(is_array($this->database_definition['TABLES'][$table_name]['FIELDS'])
                && count($this->database_definition['TABLES'][$table_name]['FIELDS']) > 0
            ) {
                foreach($this->database_definition['TABLES'][$table_name]['FIELDS'] as $field_set_default_name => $field_set_default) {
                    if(isset($field_set_default['notnull']) && $field_set_default['notnull']
                        && !isset($field_set_default['default'])
                    ) {
                        if(isset($this->default_values[$field_set_default['type']])) {
                            $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_set_default_name]['default'] = $this->default_values[$field_set_default['type']];
                        } else {
                            $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_set_default_name]['default'] = 0;
                        }
                    }
                    if(isset($field_set_default['CHOICES']) && is_array($field_set_default['CHOICES'])) {
                        foreach($field_set_default['CHOICES'] as $field_choices_set_default_name => $field_choices_set_default) {
                            if(isset($field_choices_set_default['notnull'])
                                && $field_choices_set_default['notnull']
                                && !isset($field_choices_set_default['default'])
                            ) {
                                if(isset($this->default_values[$field_choices_set_default['type']])) {
                                    $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_set_default_name]['CHOICES']
                                        [$field_choices_set_default_name]['default'] = $this->default_values[$field_choices_set_default['type']];
                                } else {
                                    $this->database_definition['TABLES'][$table_name]['FIELDS'][$field_set_default_name]['CHOICES']
                                        [$field_choices_set_default_name]['default'] = 0;
                                }
                            }
                        }
                    }
                }
            }
        }
        $sequences = $this->database->listSequences();
        if(MDB::isError($sequences)) {
            return($sequences);
        }
        if(is_array($sequences) && count($sequences) > 0 && !isset($this->database_definition['SEQUENCES'])) {
            $this->database_definition['SEQUENCES'] = array();
        }
        for($sequence = 0; $sequence < count($sequences); $sequence++) {
            $sequence_name = $sequences[$sequence];
            $definition = $this->database->getSequenceDefinition($sequence_name);
            if(MDB::isError($definition)) {
                return($definition);
            }
            $this->database_definition['SEQUENCES'][$sequence_name] = $definition;
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ dumpDatabase()

    /**
     * Dump a previously parsed database structure in the Metabase schema
     * XML based format suitable for the Metabase parser. This function
     * may optionally dump the database definition with initialization
     * commands that specify the data that is currently present in the tables.
     *
     * @param array $arguments an associative array that takes pairs of tag
     * names and values that define dump options.
     *                 array (
     *                     'Definition'    =>    Boolean
     *                         TRUE   :  dump currently parsed definition
     *                         default:  dump currently connected database
     *                     'Output_Mode'    =>    String
     *                         'file' :   dump into a file
     *                         default:   dump using a function
     *                     'Output'        =>    String
     *                         depending on the 'Output_Mode'
     *                                  name of the file
     *                                  name of the function
     *                     'EndOfLine'        =>    String
     *                         end of line delimiter that should be used
     *                         default: "\n"
     *                 );
     * @param integer $dump constant that determines what data to dump
     *                      MDB_MANAGER_DUMP_ALL       : the entire db
     *                      MDB_MANAGER_DUMP_STRUCTURE : only the structure of the db
     *                      MDB_MANAGER_DUMP_CONTENT   : only the content of the db
     * @return mixed MDB_OK on success, or a MDB error object
     * @access public
     */
    function dumpDatabase($arguments, $dump = MDB_MANAGER_DUMP_ALL)
    {
        if(isset($arguments['Definition']) && $arguments['Definition']) {
            $dump_definition = TRUE;
        } else {
            if(!$this->database) {
                return($this->raiseError(MDB_ERROR_NODBSELECTED,
                    NULL, NULL, 'please connect to a RDBMS first'));
            }
            $this->getDefinitionFromDatabase();
            $dump_definition = FALSE;
        }
        if(isset($arguments['Output'])) {
            if(isset($arguments['Output_Mode']) && $arguments['Output_Mode'] == 'file') {
                $fp = fopen($arguments['Output'], 'w');
                $output = FALSE;
            } elseif(function_exists($arguments['Output'])) {
                $output = $arguments['Output'];
            } else {
                return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                        'no valid output function specified'));
            }
        } else {
            return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'no output method specified'));
        }
        if(isset($arguments['EndOfLine'])) {
            $eol = $arguments['EndOfLine'];
        } else {
            $eol = "\n";
        }
        
        $sequences = array();
        if(isset($this->database_definition['SEQUENCES'])
            && is_array($this->database_definition['SEQUENCES'])
        ) {
            foreach($this->database_definition['SEQUENCES'] as $sequence_name => $sequence) {
                if(isset($sequence['on'])) {
                    $table = $sequence['on']['table'];
                } else {
                    $table = '';
                }
                $sequences[$table][] = $sequence_name;
            }
        }
        $previous_database_name = (strcmp($this->database_definition['name'], '') ? $this->database->setDatabase($this->database_definition['name']) : '');
        $buffer = ('<?xml version="1.0" encoding="ISO-8859-1" ?>'.$eol);
        $buffer .= ("<database>$eol$eol <name>".$this->database_definition['name']."</name>$eol <create>".$this->database_definition['create']."</create>$eol");
        
        if($output) {
            $output($buffer);
        } else {
            fwrite($fp, $buffer);
        }
        $buffer = '';
        if(isset($this->database_definition['TABLES']) && is_array($this->database_definition['TABLES'])) {
            foreach($this->database_definition['TABLES'] as $table_name => $table) {
                $buffer = ("$eol <table>$eol$eol  <name>$table_name</name>$eol");
                if($dump == MDB_MANAGER_DUMP_ALL || $dump == MDB_MANAGER_DUMP_STRUCTURE) {
                    $buffer .= ("$eol  <declaration>$eol");
                    if(isset($table['FIELDS']) && is_array($table['FIELDS'])) {
                        foreach($table['FIELDS'] as $field_name => $field) {
                            if(!isset($field['type'])) {
                                return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                                    'it was not specified the type of the field "'.$field_name.'" of the table "'.$table_name));
                            }
                            $buffer .=("$eol   <field>$eol    <name>$field_name</name>$eol    <type>".$field['type']."</type>$eol");
                            if(in_array($field_name, array_keys($this->invalid_names))) {
                                $this->warnings[] = "invalid field name: $field_name. You will need to set the class var \$fail_on_invalid_names to FALSE or change the field name.";
                            }
                            switch($field['type']) {
                                case 'integer':
                                    if(isset($field['unsigned'])) {
                                        $buffer .=("    <unsigned>1</unsigned>$eol");
                                    }
                                    break;
                                case 'text':
                                case 'clob':
                                case 'blob':
                                    if(isset($field['length'])) {
                                        $buffer .=('    <length>'.$field['length']."</length>$eol");
                                    }
                                    break;
                                case 'boolean':
                                case 'date':
                                case 'timestamp':
                                case 'time':
                                case 'float':
                                case 'decimal':
                                    break;
                                default:
                                    return('type "'.$field['type'].'" is not yet supported');
                            }
                            if(isset($field['notnull'])) {
                                $buffer .=("    <notnull>1</notnull>$eol");
                            }
                            if(isset($field['default'])) {
                                $buffer .=('    <default>'.$this->_escapeSpecialCharacters($field['default'])."</default>$eol");
                            }
                            $buffer .=("   </field>$eol");
                        }
                    }
                    if(isset($table['INDEXES']) && is_array($table['INDEXES'])) {
                        foreach($table['INDEXES'] as $index_name => $index) {
                            $buffer .=("$eol   <index>$eol    <name>$index_name</name>$eol");
                            if(isset($index['unique'])) {
                                $buffer .=("    <unique>1</unique>$eol");
                            }
                            foreach($index['FIELDS'] as $field_name => $field) {
                                $buffer .=("    <field>$eol     <name>$field_name</name>$eol");
                                if(is_array($field) && isset($field['sorting'])) { 
                                    $buffer .=('     <sorting>'.$field['sorting']."</sorting>$eol");
                                }
                                $buffer .=("    </field>$eol");
                            }
                            $buffer .=("   </index>$eol");
                        }
                    }
                    $buffer .= ("$eol  </declaration>$eol");
                }
                if($output) {
                    $output($buffer);
                } else {
                    fwrite($fp, $buffer);
                }
                $buffer = '';
                if($dump == MDB_MANAGER_DUMP_ALL || $dump == MDB_MANAGER_DUMP_CONTENT) {
                    if($dump_definition) {
                        if(isset($table['initialization']) && is_array($table['initialization'])) {
                            $buffer = ("$eol  <initialization>$eol");
                            foreach($table['initialization'] as $instruction_name => $instruction) {
                                switch($instruction['type']) {
                                    case 'insert':
                                        $buffer .= ("$eol   <insert>$eol");
                                        foreach($instruction['FIELDS'] as $field_name => $field) {
                                            $buffer .= ("$eol    <field>$eol     <name>$field_name</name>$eol     <value>".$this->_escapeSpecialCharacters($field)."</value>$eol   </field>$eol");
                                        }
                                        $buffer .= ("$eol   </insert>$eol");
                                        break;
                                }
                            }
                            $buffer .= ("$eol  </initialization>$eol");
                        }
                    } else {
                        $types = array();
                        foreach($table['FIELDS'] as $field) {
                            $types[] = $field['type'];
                        }
                        $query = 'SELECT '.implode(',',array_keys($table['FIELDS']))." FROM $table_name";
                        $result = $this->database->queryAll($query, $types, MDB_FETCHMODE_ASSOC);
                        if(MDB::isError($result)) {
                            return($result);
                        }
                        $rows = count($result);
                        if($rows > 0) {
                            $buffer = ("$eol  <initialization>$eol");
                            if($output) {
                                $output($buffer);
                            } else {
                                fwrite($fp, $buffer);
                            }
                            
                            for($row = 0; $row < $rows; $row++) {
                                $buffer = ("$eol   <insert>$eol");
                                $values = $result[$row];
                                if(!is_array($values)) {
                                    break;
                                } else {
                                    foreach($values as $field_name => $field) {
                                            $buffer .= ("$eol   <field>$eol     <name>$field_name</name>$eol     <value>");
                                            $buffer .= $this->_escapeSpecialCharacters($values[$field_name]);
                                            $buffer .= ("</value>$eol   </field>$eol");
                                    }
                                }
                                $buffer .= ("$eol   </insert>$eol");
                                if($output) {
                                    $output($buffer);
                                } else {
                                    fwrite($fp, $buffer);
                                }
                                $buffer = '';
                            }
                            $buffer = ("$eol  </initialization>$eol");
                            if($output) {
                                $output($buffer);
                            } else {
                                fwrite($fp, $buffer);
                            }
                            $buffer = '';
                        }
                    }
                }
                $buffer .= ("$eol </table>$eol");
                if($output) {
                    $output($buffer);
                } else {
                    fwrite($fp, $buffer);
                }
                if(isset($sequences[$table_name])) {
                    for($sequence = 0, $j = count($sequences[$table_name]);
                        $sequence < $j;
                        $sequence++)
                    {
                        $result = $this->_dumpSequence($sequences[$table_name][$sequence], $eol, $dump);
                        if(MDB::isError($result)) {
                            return($result);
                        }
                        if($output) {
                            $output($result);
                        } else {
                            fwrite($fp, $result);
                        }
                    }
                }
            }
        }
        if(isset($sequences[''])) {
            for($sequence = 0;
                $sequence < count($sequences['']);
                $sequence++)
            {
                $result = $this->_dumpSequence($sequences[''][$sequence], $eol, $dump);
                if(MDB::isError($result)) {
                    return($result);
                }
                if($output) {
                       $output($result);
                   } else {
                       fwrite($fp, $result);
                }
            }
        }
        
        $buffer = ("$eol</database>$eol");
        if($output) {
            $output($buffer);
        } else {
            fwrite($fp, $buffer);
            fclose($fp);
        }
        
        if(strcmp($previous_database_name, '')) {
            $this->database->setDatabase($previous_database_name);
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ updateDatabase()

    /**
     * Compare the correspondent files of two versions of a database schema
     * definition: the previously installed and the one that defines the schema
     * that is meant to update the database.
     * If the specified previous definition file does not exist, this function
     * will create the database from the definition specified in the current
     * schema file.
     * If both files exist, the function assumes that the database was previously
     * installed based on the previous schema file and will update it by just
     * applying the changes.
     * If this function succeeds, the contents of the current schema file are
     * copied to replace the previous schema file contents. Any subsequent schema
     * changes should only be done on the file specified by the $current_schema_file
     * to let this function make a consistent evaluation of the exact changes that
     * need to be applied.
     *
     * @param string $current_schema_file name of the updated database schema
     * definition file.
     * @param string $previous_schema_file name the previously installed database
     * schema definition file.
     * @param array $variables an associative array that is passed to the argument
     * of the same name to the parseDatabaseDefinitionFile function. (there third
     * param)
     * @return mixed MDB_OK on success, or a MDB error object
     * @access public
     */
    function updateDatabase($current_schema_file, $previous_schema_file = FALSE, $variables = array())
    {
        $database_definition = $this->parseDatabaseDefinitionFile($current_schema_file,
            $variables, $this->options['fail_on_invalid_names']);
        if(MDB::isError($database_definition)) {
            return($database_definition);
        }
        $this->database_definition = $database_definition;
        $copy = 0;
/*
        $this->expectError(MDB_ERROR_UNSUPPORTED);
        $databases = $this->database->listDatabases();
        $this->popExpect();
        if((MDB::isError($databases) || (is_array($databases) && in_array($this->database_definition['name'], $databases)))
            && $previous_schema_file && file_exists($previous_schema_file))
        {
*/
        if($previous_schema_file && file_exists($previous_schema_file)) {
            $previous_definition = $this->parseDatabaseDefinitionFile($previous_schema_file, $variables, 0);
            if(MDB::isError($previous_definition)) {
                return($previous_definition);
            }
            $changes = $this->_compareDefinitions($previous_definition);
            if(MDB::isError($changes)) {
                return($changes);
            }
            if(isset($changes) && is_array($changes)) {
                $result = $this->_alterDatabase($previous_definition, $changes);
                if(MDB::isError($result)) {
                    return($result);
                }
                $copy = 1;
                if($this->options['debug']) {
                    $result = $this->_debugDatabaseChanges($changes);
                    if(MDB::isError($result)) {
                        return($result);
                    }
                }
            }
        } else {
            $result = $this->_createDatabase();
            if(MDB::isError($result)) {
                return($result);
            }
            $copy = 1;
        }
        if($copy && $previous_schema_file && !copy($current_schema_file, $previous_schema_file)) {
            return($this->raiseError(MDB_ERROR_MANAGER, NULL, NULL,
                'Could not copy the new database definition file to the current file'));
        }
        return(MDB_OK);
    }

    // }}}
}
?>
