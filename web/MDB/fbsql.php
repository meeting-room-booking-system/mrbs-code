<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2004 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith, Frank M. Kromann                       |
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
// | Author: Lukas Smith <smith@dybnet.de>                                |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once('MDB/Common.php');

/**
 * MDB FrontBase driver
 *
 * Notes:
 * - This driver is fairly untested at this time and shoukd be considered
 *   alpha quality for this reason
 *
 * @package MDB
 * @category Database
 * @author  Lukas Smith <smith@dybnet.de>
 * @author  Frank M. Kromann <frank@kromann.info>
 */
class MDB_fbsql extends MDB_Common
{
    // {{{ properties

    var $connection = 0;
    var $connected_host;
    var $connected_user;
    var $connected_password;
    var $connected_port;
    var $opened_persistent = '';

    var $escape_quotes = "'";
    var $decimal_factor = 1.0;
    
    var $max_text_length = 32768;

    var $highest_fetched_row = array();
    var $columns = array();

    // }}}
    // {{{ constructor

    /**
    * Constructor
    */
    function MDB_fbsql()
    {
        $this->MDB_Common();
        $this->phptype = 'fbsql';
        $this->dbsyntax = 'fbsql';
        
        $this->supported['Sequences'] = 1;
        $this->supported['Indexes'] = 1;
        $this->supported['AffectedRows'] = 1;
        $this->supported['Summaryfunctions'] = 1;
        $this->supported['OrderByText'] = 1;
        $this->supported['CurrId'] = 1;
        $this->supported['SelectRowRanges'] = 1;
        $this->supported['LOBs'] = 1;
        $this->supported['Replace'] = 1;
        $this->supported['SubSelects'] = 1;
        
        $this->decimal_factor = pow(10.0, $this->decimal_places);
        
        $this->errorcode_map = array(
            1004 => MDB_ERROR_CANNOT_CREATE,
            1005 => MDB_ERROR_CANNOT_CREATE,
            1006 => MDB_ERROR_CANNOT_CREATE,
            1007 => MDB_ERROR_ALREADY_EXISTS,
            1008 => MDB_ERROR_CANNOT_DROP,
            1046 => MDB_ERROR_NODBSELECTED,
            1050 => MDB_ERROR_ALREADY_EXISTS,
            1051 => MDB_ERROR_NOSUCHTABLE,
            1054 => MDB_ERROR_NOSUCHFIELD,
            1062 => MDB_ERROR_ALREADY_EXISTS,
            1064 => MDB_ERROR_SYNTAX,
            1100 => MDB_ERROR_NOT_LOCKED,
            1136 => MDB_ERROR_VALUE_COUNT_ON_ROW,
            1146 => MDB_ERROR_NOSUCHTABLE,
        );
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int native FrontBase error code
     */
    function errorNative()
    {
        return @fbsql_errno($this->connection);
    }

    // }}}
    // {{{ fbsqlRaiseError()

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for MDB::raiseError
     * that checks for native error msgs.
     *
     * @param integer $errno error code
     * @return object a PEAR error object
     * @access public
     * @see PEAR_Error
     */
    function fbsqlRaiseError($errno = NULL)
    {
        if ($errno == NULL) {
            $errno = $this->errorCode(@fbsql_errno($this->connection));
        }
        return($this->raiseError($errno, NULL, NULL, NULL, @fbsql_error($this->connection)));
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit    flag that indicates whether the database
     *                                changes should be committed right after
     *                                executing every query statement. If this
     *                                argument is 0 a transaction implicitly
     *                                started. Otherwise, if a transaction is
     *                                in progress it is ended by committing any
     *                                database changes that were pending.
     *
     * @access public
     *
     * @return mixed MDB_OK on success, a MDB error on failure
     */
    function autoCommit($auto_commit)
    {
        $this->debug('AutoCommit: '.($auto_commit ? 'On' : 'Off'));
        if (!isset($this->supported['Transactions'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Auto-commit transactions: transactions are not in use'));
        }
        if ($this->auto_commit == $auto_commit) {
            return(MDB_OK);
        }
        if ($this->connection) {
            if ($auto_commit) {
                $result = $this->query('COMMIT');
                if (MDB::isError($result)) {
                    return($result);
                }
                $result = $this->query('SET COMMIT TRUE');
            } else {
                $result = $this->query('SET COMMIT FALSE');
            }
            if (MDB::isError($result)) {
                return($result);
            }
        }
        $this->auto_commit = $auto_commit;
        $this->in_transaction = !$auto_commit;
        return(MDB_OK);
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after committing the pending changes.
     *
     * @access public
     *
     * @return mixed MDB_OK on success, a MDB error on failure
     */
    function commit()
    {
        $this->debug('Commit Transaction');
        if (!isset($this->supported['Transactions'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Commit transactions: transactions are not in use'));
        }
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
            'Commit transactions: transaction changes are being auto commited'));
        }
        return($this->query('COMMIT'));
    }

    // }}}
    // {{{ rollback()

    /**
     * Cancel any database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after canceling the pending changes.
     *
     * @access public
     *
     * @return mixed MDB_OK on success, a MDB error on failure
     */
    function rollback()
    {
        $this->debug('Rollback Transaction');
        if (!isset($this->supported['Transactions'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Rollback transactions: transactions are not in use'));
        }
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Rollback transactions: transactions can not be rolled back when changes are auto commited'));
        }
        return($this->query('ROLLBACK'));
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return TRUE on success, MDB_Error on failure
     **/
    function connect()
    {
        $port = (isset($this->port) ? $this->port : 0);
        if($this->connection != 0) {
            if (!strcmp($this->connected_host, $this->host)
                && !strcmp($this->connected_user, $this->user)
                && !strcmp($this->connected_password, $this->password)
                && !strcmp($this->connected_port, $port)
                && $this->opened_persistent == $this->options['persistent'])
            {
                return(MDB_OK);
            }
            @fbsql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
        }
        if(!PEAR::loadExtension($this->phptype)) {
            return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND,
                NULL, NULL, 'extension '.$this->phptype.' is not compiled into PHP',
                'MDB_Error', TRUE));
        }

        $function = ($this->options['persistent'] ? 'fbsql_pconnect' : 'fbsql_connect');
        if(!function_exists($function)) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED));
        }

        @ini_set('track_errors', TRUE);
        $this->connection = @$function(
            $port > 0 ? $port : $this->host,
            $this->user, $this->password);
        @ini_restore('track_errors');
        if ($this->connection <= 0) {
            return($this->raiseError(MDB_ERROR_CONNECT_FAILED, NULL, NULL,
                $php_errormsg));
        }

        if (isset($this->supported['Transactions']) && !$this->auto_commit) {
            if (!@fbsql_query('SET AUTOCOMMIT FALSE;', $this->connection)) {
                @fbsql_close($this->connection);
                $this->connection = 0;
                $this->affected_rows = -1;
                return($this->raiseError());
            }
            $this->in_transaction = TRUE;
        }
        $this->connected_host = $this->host;
        $this->connected_user = $this->user;
        $this->connected_password = $this->password;
        $this->connected_port = $port;
        $this->opened_persistent = $this->options['persistent'];
        return(MDB_OK);
    }

    // }}}
    // {{{ _close()
    /**
     * all the RDBMS specific things needed close a DB connection
     *
     * @return boolean
     * @access private
     **/
    function _close()
    {
        if ($this->connection != 0) {
            if (isset($this->supported['Transactions']) && !$this->auto_commit) {
                $result = $this->autoCommit(TRUE);
            }
            @fbsql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            if (isset($result) && MDB::isError($result)) {
                return($result);
            }

            unset($GLOBALS['_MDB_databases'][$this->database]);
            return(TRUE);
        }
        return(FALSE);
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @access public
     *
     * @param string  $query  the SQL query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     *
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     */
    function query($query, $types = NULL)
    {
        $this->debug("Query: $query");
        $ismanip = MDB::isManip($query);
        $this->last_query = $query;
        $first = $this->first_selected_row;
        $limit = $this->selected_row_limit;
        $this->first_selected_row = $this->selected_row_limit = 0;

        $result = $this->connect();
        if (MDB::isError($result)) {
            return($result);
        }
        if($limit > 0) {
            if (!$ismanip) {
                $query = str_replace('SELECT', "SELECT TOP($first,$limit)", $query);
            }
        }

        if ($this->database_name != '') {
            if(!@fbsql_select_db($this->database_name, $this->connection)) {
                return($this->fbsqlRaiseError());
            }
        }

        // Add ; to the end of the query. This is required by FrontBase
        $query .= ';';
        if ($result = @fbsql_query($query, $this->connection)) {
            if ($ismanip) {
                $this->affected_rows = @fbsql_affected_rows($this->connection);
                return(MDB_OK);
            } else {
                $result_value = intval($result);
                $this->highest_fetched_row[$result_value] = -1;
                if ($types != NULL) {
                    if (!is_array($types)) {
                        $types = array($types);
                    }
                    if (MDB::isError($err = $this->setResultTypes($result, $types))) {
                        $this->freeResult($result);
                        return($err);
                    }
                }
                return($result);
            }
        }
        return($this->fbsqlRaiseError());
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @param resource   $result    result identifier
     * @return mixed                an associative array variable
     *                              that will hold the names of columns. The
     *                              indexes of the array are the column names
     *                              mapped to lower case and the values are the
     *                              respective numbers of the columns starting
     *                              from 0. Some DBMS may not return any
     *                              columns when the result set does not
     *                              contain any rows.
     *
     *                              a MDB error on failure
     * @access public
     */
    function getColumnNames($result)
    {
        $result_value = intval($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'Get column names: it was specified an inexisting result set'));
        }
        if (!isset($this->columns[$result_value])) {
            $this->columns[$result_value] = array();
            $columns = @fbsql_num_fields($result);
            for($column = 0; $column < $columns; $column++) {
                $field_name = @fbsql_field_name($result, $column);
                if ($this->options['optimize'] == 'portability') {
                    $field_name = strtolower($field_name);
                }
                $this->columns[$result_value][$field_name] = $column;
            }
        }
        return($this->columns[$result_value]);
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @param resource    $result        result identifier
     * @access public
     * @return mixed integer value with the number of columns, a MDB error
     *                       on failure
     */
    function numCols($result)
    {
        if (!isset($this->highest_fetched_row[intval($result)])) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'numCols: it was specified an inexisting result set'));
        }
        return(@fbsql_num_fields($result));
    }

    // }}}
    // {{{ endOfResult()

    /**
    * check if the end of the result set has been reached
    *
    * @param resource    $result result identifier
    * @return mixed TRUE or FALSE on sucess, a MDB error on failure
    * @access public
    */
    function endOfResult($result)
    {
        $result_value = intval($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'End of result: attempted to check the end of an unknown result'));
        }
        return($this->highest_fetched_row[$result_value] >= $this->numRows($result)-1);
    }

    // }}}
    // {{{ fetch()

    /**
    * fetch value from a result set
    *
    * @param resource    $result result identifier
    * @param int    $row    number of the row where the data can be found
    * @param int    $field    field number where the data can be found
    * @return mixed string on success, a MDB error on failure
    * @access public
    */
    function fetch($result, $row, $field)
    {
        $result_value = intval($result);
        $this->highest_fetched_row[$result_value] =
            max($this->highest_fetched_row[$result_value], $row);
        if (is_string($field)) {
            if (intval($field) != $field) {
                $field = strtoupper($field);
            }
            else
            {
                $field = intval($field);
            }
        }
        $res = @fbsql_result($result, $row, $field);
        if ($res === FALSE && $res != NULL) {
            return($this->fbsqlRaiseError($errno));
        }
        return($res);
    }

    // }}}
    // {{{ fetchClob()

    /**
    * fetch a clob value from a result set
    *
    * @param resource    $result result identifier
    * @param int    $row    number of the row where the data can be found
    * @param int    $field    field number where the data can be found
    * @return mixed content of the specified data cell, a MDB error on failure,
    *               a MDB error on failure
    * @access public
    */
    function fetchClob($result, $row, $field)
    {
        return($this->fetchLob($result, $row, $field));
    }

    // }}}
    // {{{ fetchBlob()

    /**
    * fetch a blob value from a result set
    *
    * @param resource    $result result identifier
    * @param int    $row    number of the row where the data can be found
    * @param int    $field    field number where the data can be found
    * @return mixed content of the specified data cell, a MDB error on failure
    * @access public
    */
    function fetchBlob($result, $row, $field)
    {
        return($this->fetchLob($result, $row, $field));
    }

    // }}}
    // {{{ convertResult()

    /**
    * convert a value to a RDBMS indepdenant MDB type
    *
    * @param mixed  $value   value to be converted
    * @param int    $type    constant that specifies which type to convert to
    * @return mixed converted value
    * @access public
    */
    function convertResult($value, $type)
    {
        switch($type) {
            case MDB_TYPE_BOOLEAN:
                return ($value == 'T') ? TRUE : FALSE;
            case MDB_TYPE_DECIMAL:
                return(sprintf('%.'.$this->decimal_places.'f', doubleval($value)/$this->decimal_factor));
            case MDB_TYPE_FLOAT:
                return(doubleval($value));
            case MDB_TYPE_TIME:
                if ($value[0] == '+') {
                    return (substr($value, 1));
                } else {
                    return($value);
                }
            default:
                return($this->_baseConvertResult($value, $type));
        }
    }

    // }}}
    // {{{ numRows()

    /**
    * returns the number of rows in a result object
    *
     * @param ressource $result a valid result ressouce pointer
    * @return mixed MDB_Error or the number of rows
    * @access public
    */
    function numRows($result)
    {
        return(@fbsql_num_rows($result));
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result result identifier
     * @return boolean TRUE on success, FALSE if $result is invalid
     * @access public
     */
    function freeResult($result)
    {
        $result_value = intval($result);
        if(isset($this->highest_fetched_row[$result_value])) {
            unset($this->highest_fetched_row[$result_value]);
        }
        if(isset($this->columns[$result_value])) {
            unset($this->columns[$result_value]);
        }
        if(isset($this->result_types[$result_value])) {
            unset($this->result_types[$result_value]);
        }
        return(@fbsql_free_result($result));
    }

    // }}}
    // {{{ getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       unsigned
     *                        Boolean flag that indicates whether the field
     *                        should be declared as unsigned integer if
     *                        possible.
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getIntegerDeclaration($name, $field)
    {
        if (isset($field['unsigned'])) {
            $this->warnings[] = "unsigned integer field \"$name\" is being
                declared as signed integer";
        }
        return("$name INT".(isset($field["default"])
            ? " DEFAULT ".$field["default"]
            : "").(isset($field["notnull"]) ? " NOT NULL" : ""));
    }

    // }}}
    // {{{ getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the text
     *           field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       default
     *           Text value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getTextDeclaration($name, $field)
    {
        return((isset($field["length"])
            ? "$name VARCHAR (".$field["length"].")"
            : "$name VARCHAR(".$this->max_text_length.")").(isset($field["default"])
                ? " DEFAULT ".$this->GetTextValue($field["default"])
                : "").(isset($field["notnull"]) ? " NOT NULL" : ""));
    }

    // }}}
    // {{{ getClobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the
     *                        properties of the field being declared as array
     *                        indexes. Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       length
     *                        Integer value that determines the maximum length
     *                        of the large object field. If this argument is
     *                        missing the field should be declared to have the
     *                        longest length allowed by the DBMS.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field
     *                        is constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getClobDeclaration($name, $field)
    {
        return("$name CLOB".
                 (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getBlobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       length
     *                        Integer value that determines the maximum length
     *                        of the large object field. If this argument is
     *                        missing the field should be declared to have the
     *                        longest length allowed by the DBMS.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getBlobDeclaration($name, $field)
    {
        return("$name BLOB".
                (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getBooleanDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a boolean type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Boolean value to be used as default for this field.
     *
     *       notnullL
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getBooleanDeclaration($name, $field)
    {
        return("$name BOOLEAN" . (isset($field['default'])
            ? ' DEFAULT ' . $this->getBooleanValue($field['default'])
            : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field properties
     *                        are as follows:
     *
     *                       default
     *                        Date value to be used as default for this field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDateDeclaration($name, $field)
    {
        return("$name DATE".
                (isset($field['default']) ? " DEFAULT DATE'".$field['default']."'" : '').
                (isset($field['notnull']) ? ' NOT NULL' : '')
               );
    }

    // }}}
    // {{{ getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an timestamp
     * type field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Time stamp value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getTimestampDeclaration($name, $field)
    {
        return("$name TIMESTAMP".
                (isset($field['default']) ? " DEFAULT TIMESTAMP'".$field['default']."'" : '').
                (isset($field['notnull']) ? ' NOT NULL' : '')
               );
    }

    // }}}
    // {{{ getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an time type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Time value to be used as default for this field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getTimeDeclaration($name, $field)
    {
        return("$name TIME".
                (isset($field['default']) ? " DEFAULT TIME'".$field['default']."'" : '').
                (isset($field['notnull']) ? ' NOT NULL' : '')
               );
    }

    // }}}
    // {{{ getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getFloatDeclaration($name, $field)
    {
        return("$name FLOAT".
                (isset($field['default']) ?
                 ' DEFAULT '.$this->getFloatValue($field['default']) : '').
                (isset($field['notnull']) ? ' NOT NULL' : '')
               );
    }

    // }}}
    // {{{ getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access public
     */
    function getDecimalDeclaration($name, $field)
    {
        return("$name REAL".
                (isset($field['default']) ?
                 ' DEFAULT '.$this->getDecimalValue($field['default']) : '').
                 (isset($field['notnull']) ? ' NOT NULL' : '')
               );
    }

    // }}}
    // {{{ getClobValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param           $parameter
     * @param           $clob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function getClobValue($prepared_query, $parameter, $clob)
    {
        $value = "'";
        while(!$this->endOfLob($clob)) {
            $result = $this->readLob($clob, $data, $this->options['lob_buffer_length']);
            if (MDB::isError($result)) {
                return($result);
            }
            $value .= $this->_quote($data);
        }
        $value .= "'";
        return($value);
    }

    // }}}
    // {{{ freeClobValue()

    /**
     * free a character large object
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param string    $clob
     * @return MDB_OK
     * @access public
     */
    function freeClobValue($prepared_query, $clob)
    {
        unset($this->lobs[$clob]);
        return(MDB_OK);
    }

    // }}}
    // {{{ getBlobValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param           $parameter
     * @param           $blob
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function getBlobValue($prepared_query, $parameter, $blob)
    {
        $value = "'";
        while(!$this->endOfLob($blob)) {
            $result = $this->readLob($blob, $data, $this->options['lob_buffer_length']);
            if (MDB::isError($result)) {
                return($result);
            }
            $value .= addslashes($data);
        }
        $value .= "'";
        return($value);
    }

    // }}}
    // {{{ freeBlobValue()

    /**
     * free a binary large object
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param string    $blob
     * @return MDB_OK
     * @access public
     */
    function freeBlobValue($prepared_query, $blob)
    {
        unset($this->lobs[$blob]);
        return(MDB_OK);
    }

    // }}}
    // {{{ getBooleanValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getBooleanValue($value)
    {
        return(($value === NULL) ? 'NULL' : ($value ? "True" : "False"));
    }

    // }}}
    // {{{ getDateValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     * 
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public 
     */
    function getDateValue($value)
    {
        return(($value === NULL) ? 'NULL' : "DATE'$value'");
    }

    // }}}
    // {{{ getTimestampValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     * 
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public 
     */
    function getTimestampValue($value)
    {
        return(($value === NULL) ? 'NULL' : "TIMESTAMP'$value'");
    }

    // }}}
    // {{{ getTimeValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *        compose query statements.
     * 
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public 
     */
    function getTimeValue($value)
    {
        return(($value === NULL) ? 'NULL' : "TIME'$value'");
    }

    // }}}
    // {{{ getFloatValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string  $value text string value that is intended to be converted.
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function getFloatValue($value)
    {
        return(($value === NULL) ? 'NULL' : (float)$value);
    }

    // }}}
    // {{{ getDecimalValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string  $value text string value that is intended to be converted.
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access public
     */
    function getDecimalValue($value)
    {
        return(($value === NULL)
            ? 'NULL'
            : strval(round(doubleval($value)*$this->decimal_factor)));
    }

    // }}}
    // {{{ nextId()

    /**
     * returns the next free id of a sequence
     *
     * @param string  $seq_name name of the sequence
     * @param boolean $ondemand when true the seqence is
     *                          automatic created, if it
     *                          not exists
     *
     * @return mixed MDB_Error or id
     * @access public
     */
    function nextId($seq_name, $ondemand = TRUE)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        $this->expectError(MDB_ERROR_NOSUCHTABLE);
        $result = $this->query("INSERT INTO $sequence_name (dummy) VALUES (1)");
        $this->popExpect();
        if ($ondemand && MDB::isError($result) &&
            $result->getCode() == MDB_ERROR_NOSUCHTABLE)
        {
            // Since we are create the sequence on demand
            // we know the first id = 1 so initialize the
            // sequence at 2
            $result = $this->createSequence($seq_name, 2);
            if (MDB::isError($result)) {
                return($this->raiseError(MDB_ERROR, NULL, NULL,
                    'Next ID: on demand sequence could not be created'));
            } else {
                // First ID of a newly created sequence is 1
                return(1);
            }
        }
        $value = intval(@fbsql_insert_id());
        $res = $this->query("DELETE FROM $sequence_name WHERE sequence < $value");
        if (MDB::isError($res)) {
            $this->warnings[] = 'Next ID: could not delete previous sequence table values';
        }
        return($value);
    }

    // }}}
    // {{{ currId()

    /**
     * returns the current id of a sequence
     *
     * @param string  $seq_name name of the sequence
     * @return mixed MDB_Error or id
     * @access public
     */
    function currId($seq_name)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        $result = $this->query("SELECT MAX(sequence) FROM $sequence_name", 'integer');
        if (MDB::isError($result)) {
            return($result);
        }

        return($this->fetchOne($result));
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and return data in an array.
     *
     * @param resource $result result identifier
     * @param int $fetchmode ignored
     * @param int $rownum the row number to fetch
     * @return mixed data array or NULL on success, a MDB error on failure
     * @access public
     */
    function fetchInto($result, $fetchmode = MDB_FETCHMODE_DEFAULT, $rownum = NULL)
    {
        $result_value = intval($result);
        if ($rownum == NULL) {
            ++$this->highest_fetched_row[$result_value];
        } else {
            if (!@fbsql_data_seek($result, $rownum)) {
                return(NULL);
            }
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value], $rownum);
        }
        if ($fetchmode == MDB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $row = @fbsql_fetch_assoc($result);
            if (is_array($row) && $this->options['optimize'] == 'portability') {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @fbsql_fetch_row($result);
        }
        if (!$row) {
            if($this->options['autofree']) {
                $this->freeResult($result);
            }
            return(NULL);
        }
        if (isset($this->result_types[$result_value])) {
            $row = $this->convertResultRow($result, $row);
        }
        return($row);
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal fbsql result pointer to the next available result
     *
     * @param a valid result resource
     * @return true if a result is available otherwise return false
     * @access public
     */
    function nextResult($result)
    {
        return(@fbsql_next_result($result));
    }

    // }}}
    // {{{ tableInfo()

    /**
    * returns meta data about the result set
    *
    * @param resource    $result    result identifier
    * @param mixed $mode depends on implementation
    * @return array an nested array, or a MDB error
    * @access public
    */
    function tableInfo($result, $mode = NULL) {
        $count = 0;
        $id     = 0;
        $res  = array();

        /*
         * depending on $mode, metadata returns the following values:
         *
         * - mode is false (default):
         * $result[]:
         *   [0]['table']  table name
         *   [0]['name']   field name
         *   [0]['type']   field type
         *   [0]['len']    field length
         *   [0]['flags']  field flags
         *
         * - mode is MDB_TABLEINFO_ORDER
         * $result[]:
         *   ['num_fields'] number of metadata records
         *   [0]['table']  table name
         *   [0]['name']   field name
         *   [0]['type']   field type
         *   [0]['len']    field length
         *   [0]['flags']  field flags
         *   ['order'][field name]  index of field named 'field name'
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) { ...
         *
         * - mode is MDB_TABLEINFO_ORDERTABLE
         *    the same as above. but additionally
         *   ['ordertable'][table name][field name] index of field
         *      named 'field name'
         *
         *      this is, because if you have fields from different
         *      tables with the same field name * they override each
         *      other with MDB_TABLEINFO_ORDER
         *
         *      you can combine MDB_TABLEINFO_ORDER and
         *      MDB_TABLEINFO_ORDERTABLE with MDB_TABLEINFO_ORDER |
         *      MDB_TABLEINFO_ORDERTABLE * or with MDB_TABLEINFO_FULL
         */

        // if $result is a string, then we want information about a
        // table without a resultset
        if (is_string($result)) {
            $id = @fbsql_list_fields($this->database_name,
                $result, $this->connection);
            if (empty($id)) {
                return($this->fbsqlRaiseError());
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return($this->fbsqlRaiseError());
            }
        }

        $count = @fbsql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {
            for ($i = 0; $i<$count; $i++) {
                $res[$i]['table'] = @fbsql_field_table ($id, $i);
                $res[$i]['name'] = @fbsql_field_name  ($id, $i);
                $res[$i]['type'] = @fbsql_field_type  ($id, $i);
                $res[$i]['len']  = @fbsql_field_len   ($id, $i);
                $res[$i]['flags'] = @fbsql_field_flags ($id, $i);
            }
        } else { // full
            $res['num_fields'] = $count;

            for ($i = 0; $i<$count; $i++) {
                $res[$i]['table'] = @fbsql_field_table ($id, $i);
                $res[$i]['name'] = @fbsql_field_name  ($id, $i);
                $res[$i]['type'] = @fbsql_field_type  ($id, $i);
                $res[$i]['len']  = @fbsql_field_len   ($id, $i);
                $res[$i]['flags'] = @fbsql_field_flags ($id, $i);
                if ($mode & MDB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result)) {
            @fbsql_free_result($id);
        }
        return($res);
    }
}

?>