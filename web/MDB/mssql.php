<?php
// vim: set et ts=4 sw=4 fdm=marker:
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
// | Author: Frank M. Kromann <frank@kromann.info>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once('MDB/Common.php');

/**
 * MDB MSSQL Server driver
 *
 * Notes:
 * - Until at least version 6.5, the only kind of table changes that the
 *   ALTER TABLE SQL statement of Microsoft SQL server supports is new field
     and constraint additions.
 *
 * - The driver alterTable method does not implement table or column renaming,
 *   column definitions changes or column dropping. In the future versions of
 *   this driver those capabilities may be emulated using other SQL statements
 *   to recreate the tables with a new definition.
 *
 * @package MDB
 * @category Database
 * @author  Frank M. Kromann <frank@kromann.info>
 */
class MDB_mssql extends MDB_Common
{
    // {{{ properties

    var $connection = 0;
    var $connected_host;
    var $connected_user;
    var $connected_password;
    var $connected_port;
    var $opened_persistent = '';

    var $escape_quotes = "'";

    var $highest_fetched_row = array();
    var $columns = array();

    // }}}
    // {{{ constructor

    /**
    * Constructor
    */
    function MDB_mssql()
    {
        $this->MDB_Common();
        $this->phptype = 'mssql';
        $this->dbsyntax = 'mssql';

        $this->supported['Sequences'] = 1;
        $this->supported['Indexes'] = 1;
        $this->supported['AffectedRows'] = 1;
        $this->supported['Transactions'] = 1;
        $this->supported['Summaryfunctions'] = 1;
        $this->supported['OrderByText'] = 0;
        $this->supported['CurrId'] = 1;
        $this->supported['SelectRowRanges'] = 1;
        $this->supported['LOBs'] = 1;
        $this->supported['Replace'] = 1;
        $this->supported['SubSelects'] = 1;

        $this->errorcode_map = array(
            207   => MDB_ERROR_NOSUCHFIELD,
            208   => MDB_ERROR_NOSUCHTABLE,
            245   => MDB_ERROR_INVALID_NUMBER,
            515   => MDB_ERROR_CONSTRAINT_NOT_NULL,
            547   => MDB_ERROR_CONSTRAINT,
            1205  => MDB_ERROR_DEADLOCK,
            2627  => MDB_ERROR_CONSTRAINT,
            2714  => MDB_ERROR_ALREADY_EXISTS,
            3701  => MDB_ERROR_NOSUCHTABLE,
            8134  => MDB_ERROR_DIVZERO,
        );
    }

    // }}}
    // {{{ errorCode()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int native MSSQL error code
     */
    function errorCode()
    {
       $res = @mssql_query('select @@ERROR as ErrorCode', $this->connection);
       if (!$res) {
           return MDB_ERROR;
       }
       $row = @mssql_fetch_row($res);
       if ($row[0] > 0) {
           return $row[0];
       }
       else {
           return null;
       }
    }

    // }}}
    // {{{ mssqlRaiseError()

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
    function mssqlRaiseError($code = null)
    {
        $native_msg = @mssql_get_last_message();
        $native_code = $this->errorCode();
        if ($code === null) {
            if (isset($this->errorcode_map[$native_code])) {
                $code = $this->errorcode_map[$native_code];
            } else {
                $code = MDB_ERROR;
            }
        }
        return $this->raiseError($code, null, null, null, $native_code . ' - ' . $native_msg);
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
        $this->debug("AutoCommit: ".($auto_commit ? "On" : "Off"));
        if (!isset($this->supported['Transactions'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Auto-commit transactions: transactions are not in use'));
        }
        if ($this->auto_commit == $auto_commit) {
            return(MDB_OK);
        }
        if ($this->connection) {
            if ($auto_commit) {
                $result = $this->query('COMMIT TRANSACTION');
            } else {
                $result = $this->query('BEGIN TRANSACTION');
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
        $this->debug("Commit Transaction");
        if (!isset($this->supported['Transactions'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Commit transactions: transactions are not in use'));
        }
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
            'Commit transactions: transaction changes are being auto commited'));
        }
        $result = $this->query('COMMIT TRANSACTION');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->query('BEGIN TRANSACTION'));
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
        $this->debug("Rollback Transaction");
        if (!isset($this->supported['Transactions'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Rollback transactions: transactions are not in use'));
        }
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Rollback transactions: transactions can not be rolled back when changes are auto commited'));
        }
        $result = $this->query('ROLLBACK TRANSACTION');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->query('BEGIN TRANSACTION'));
    }

    function _doQuery($query)
    {
        $this->current_row = $this->affected_rows = -1;
        return(@mssql_query($query, $this->connection));
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
        $port = (isset($this->port) ? $this->port : '');
        if($this->connection != 0) {
            if (!strcmp($this->connected_host, $this->host)
                && !strcmp($this->connected_user, $this->user)
                && !strcmp($this->connected_password, $this->password)
                && !strcmp($this->connected_port, $port)
                && $this->opened_persistent == $this->options['persistent'])
            {
                return(MDB_OK);
            }
            @mssql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
        }

        if(!PEAR::loadExtension($this->phptype)) {
            return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND,
                NULL, NULL, 'extension '.$this->phptype.' is not compiled into PHP',
                'MDB_Error', TRUE));
        }

        $function = ($this->options['persistent'] ? 'mssql_pconnect' : 'mssql_connect');
        if (!function_exists($function)) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED));
        }

        @ini_set('track_errors', TRUE);
        $this->connection = @$function(
            $this->host.(!strcmp($port,'') ? '' : ':'.$port),
            $this->user, $this->password);
        @ini_restore('track_errors');
        if ($this->connection <= 0) {
            return($this->raiseError(MDB_ERROR_CONNECT_FAILED, NULL, NULL,
                $php_errormsg));
        }

        if(isset($this->supported['Transactions']) && !$this->auto_commit
            && !$this->_doQuery("BEGIN TRANSACTION"))
        {
            @mssql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            return($this->raiseError("Connect: Could not begin the initial transaction"));
        }
        $this->connected_host = $this->host;
        $this->connected_user = $this->user;
        $this->connected_password = $this->password;
        $this->connected_port = $port;
        $this->selected_database = $this->database_name;
        $this->opened_persistent = $this->getoption('persistent');
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
                $result = $this->_doQuery("ROLLBACK TRANSACTION");
            }
            @mssql_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = $this->current_row = -1;

            if (isset($result) && MDB::isError($result)) {
                return($result);
            }
            unset($GLOBALS['_MDB_databases'][$this->database]);
            return(TRUE);
        }
        return(FALSE);
    }

    function standaloneQuery($query)
    {
        if(!PEAR::loadExtension($this->phptype)) {
            return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND,
                NULL, NULL, 'extension '.$this->phptype.' is not compiled into PHP',
                'MDB_Error', TRUE));
        }
        $connection = @mssql_connect($this->host,$this->user,$this->password);
        if($connection == 0) {
            return($this->mssqlRaiseError("Query: Could not connect to the Microsoft SQL server"));
        }
        $result = @mssql_query($query, $connection);
        if(!$result) {
            return($this->mssqlRaiseError("Query: Could not query a Microsoft SQL server"));
        }
        @mssql_close($connection);
        return(MDB_OK);
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
        if ($this->database_name) {
            $ismanip = MDB::isManip($query);
            $this->last_query = $query;
            $first = $this->first_selected_row;
            $limit = $this->selected_row_limit;
            $this->first_selected_row = $this->selected_row_limit = 0;

            $last_connection = $this->connection;
            $result = $this->connect();
            if (MDB::isError($result)) {
                return($result);
            }
            if($limit > 0) {
                $fetch = $first + $limit;
                if (!$ismanip) {
                    $query = str_replace('SELECT', "SELECT TOP $fetch", $query);
                }
            }
            if( $last_connection != $this->connection
                || !strcmp($this->selected_database, '')
                || strcmp($this->selected_database, $this->database_name))
            {
                if(!@mssql_select_db($this->database_name, $this->connection)) {
                    return($this->mssqlRaiseError());
                }
            }
            if ($result = $this->_doQuery($query)) {
                if ($ismanip) {
                    $this->affected_rows = @mssql_rows_affected($this->connection);
                    return(MDB_OK);
                } else {
                    $result_value = intval($result);
                    if($first > 0 || $limit > 0) {
                        $this->limits[$result_value] = array($first, $limit);
                    }
                    $this->highest_fetched_row[$result_value] = -1;
                    if ($types != NULL) {
                        if (!is_array($types)) {
                            $types = array($types);
                        }
                        $err = $this->setResultTypes($result, $types);
                        if (MDB::isError($err)) {
                            $this->freeResult($result);
                            return($err);
                        }
                    }
                    return($result);
                }
            }
        }

        return($this->mssqlRaiseError());
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
            $columns = @mssql_num_fields($result);
            for($column = 0; $column < $columns; $column++) {
                $field_name = @mssql_field_name($result, $column);
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
        return(@mssql_num_fields($result));
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
        $this->highest_fetched_row[$result_value] = max($this->highest_fetched_row[$result_value], $row);
        if (isset($this->limits[$result_value])) {
            $row += $this->limits[$result_value][0];
        }
        $res = @mssql_result($result, $row, $field);
        if ($res === FALSE && $res != NULL) {
            return($this->mssqlRaiseError($errno));
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
                return ($value == '1') ? TRUE : FALSE;
            case MDB_TYPE_DATE:
                if(strlen($value) > 10) {
                    $value=substr($value,0,10);
                }
                return($value);
            case MDB_TYPE_TIME:
                if(strlen($value) > 8) {
                    $value=substr($value,11,8);
                }
                return($value);
            case MDB_TYPE_TIMESTAMP:
                return($value);
            default:
                return($this->_baseConvertResult($value,$type));
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
        $result_value = intval($result);
        $rows = @mssql_num_rows($result);
        if (isset($this->limits[$result_value])) {
            $rows -= $this->limits[$result_value][0];
            if ($rows < 0) $rows = 0;
        }
        return($rows);
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
        if(isset($this->fetched_row[$result_value])) {
            unset($this->fetched_row[$result_value]);
        }
        if(isset($this->highest_fetched_row[$result_value])) {
            unset($this->highest_fetched_row[$result_value]);
        }
        if(isset($this->columns[$result_value])) {
            unset($this->columns[$result_value]);
        }
        if(isset($this->result_types[$result_value])) {
            unset($this->result_types[$result_value]);
        }
        return(@mssql_free_result($result));
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
        return("$name INT".(isset($field["default"]) ? " DEFAULT ".$field["default"] : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return((isset($field["length"]) ? "$name VARCHAR (".$field["length"].")" : "$name TEXT").(isset($field["default"]) ? " DEFAULT ".$this->GetTextValue($field["default"]) : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        if (isset($field["length"])) {
            $length = $field["length"];
            if ($length <= 8000) {
                $type = "VARCHAR($length)";
            } else {
                $type = "TEXT";
            }
        } else {
            $type = "TEXT";
        }
        return("$name $type".(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        if(isset($field["length"])) {
            $length = $field["length"];
            if($length <= 8000) {
                $type = "VARBINARY($length)";
            } else {
                $type = "IMAGE";
            }
        } else {
            $type = "IMAGE";
        }
        return("$name $type".(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return("$name BIT".(isset($field["default"]) ? " DEFAULT ".$field["default"] : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return("$name CHAR (".strlen("YYYY-MM-DD").")".(isset($field["default"]) ? " DEFAULT ".$this->getDateValue($field["default"]) : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return("$name CHAR (".strlen("YYYY-MM-DD HH:MM:SS").")".(isset($field["default"]) ? " DEFAULT ".$this->getTimestampValue($field["default"]) : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return("$name CHAR (".strlen("HH:MM:SS").")".(isset($field["default"]) ? " DEFAULT ".$this->getTimeValue($field["default"]) : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return("$name FLOAT".(isset($field["default"]) ? " DEFAULT ".$field["default"] : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        return("$name DECIMAL(18,".$this->decimal_places.")".(isset($field["default"]) ? " DEFAULT ".$this->getDecimalValue($field["default"]) : "").(isset($field["notnull"]) ? " NOT NULL" : " NULL"));
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
        $value="'";
        while(!$this->endOfLob($clob)) {
            if (MDB::isError($result = $this->readLob($clob, $data, $this->options['lob_buffer_length']))) {
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
        $value = "0x";
        while(!$this->endOfLob($blob))
        {
            if (MDB::isError($result = $this->readLob($blob, $data, $this->options['lob_buffer_length']))) {
                return($result);
            }
            $value.= Bin2Hex($data);
        }
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
        return(($value === NULL) ? 'NULL' : $value);
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
        return(($value === NULL) ? 'NULL' : $value);
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
        return(($value === NULL) ? 'NULL' : $value);
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
        $result = $this->query("INSERT INTO $sequence_name DEFAULT VALUES");
        $this->popExpect();
        if ($ondemand && MDB::isError($result) &&
            $result->getCode() == MDB_ERROR_NOSUCHTABLE)
        {
            // Since we are creating the sequence on demand
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
        $value = $this->queryOne("SELECT @@IDENTITY FROM $sequence_name", 'integer');
        if (MDB::isError($value)) {
            return($value);
        }
        $result = $this->query("DELETE FROM $sequence_name WHERE sequence < $value");
        if (MDB::isError($result)) {
            $this->warnings[] = 'nextID: could not delete previous sequence table values';
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
     * Fetch a row and insert the data into an existing array.
     *
     * @param resource  $result     result identifier
     * @param int       $fetchmode  how the array data should be indexed
     * @param int       $rownum     the row number to fetch
     * @return int data array on success, a MDB error on failure
     * @access public
     */
    function fetchInto($result, $fetchmode = MDB_FETCHMODE_DEFAULT, $rownum = NULL)
    {
        $result_value = intval($result);
        if (is_null($rownum)) {
            ++$this->highest_fetched_row[$result_value];
        } else {
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value], $rownum);
            if (isset($this->limits[$result_value])) {
                $rownum = $rownum + $this->limits[$result_value][0];
            }
            if (!@mssql_data_seek($result, $rownum)) {
                return(NULL);
            }
        }
        if ($fetchmode == MDB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $row = @mssql_fetch_assoc($result);
            if (is_array($row) && $this->options['optimize'] == 'portability') {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @mssql_fetch_row($result);
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
    // {{{ tableInfo()

  /**
     * Returns information about a table or a result set
     *
     * NOTE: doesn't support table name and flags if called from a db_result
     *
     * @param  mixed $resource SQL Server result identifier or table name
     * @param  int $mode A valid tableInfo mode (MDB_TABLEINFO_ORDERTABLE or
     *                   MDB_TABLEINFO_ORDER)
     *
     * @return array An array with all the information
     */
    function tableInfo($result, $mode = NULL)
    {

        $count = 0;
        $id    = 0;
        $res   = array();

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
         *   ["num_fields"] number of metadata records
         *   [0]['table']  table name
         *   [0]['name']   field name
         *   [0]['type']   field type
         *   [0]['len']    field length
         *   [0]['flags']  field flags
         *   ['order'][field name]  index of field named "field name"
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) { ...
         *
         * - mode is MDB_TABLEINFO_ORDERTABLE
         *    the same as above. but additionally
         *   ["ordertable"][table name][field name] index of field
         *      named "field name"
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
            if (!@mssql_select_db($this->database_name, $this->connection)) {
                return $this->mssqlRaiseError(MDB_ERROR_NODBSELECTED);
            }
            $id = @mssql_query("SELECT * FROM $result", $this->connection);
            if (empty($id)) {
                return($this->mssqlRaiseError());
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return($this->mssqlRaiseError());
            }
        }

        $count = @mssql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @mssql_field_name($id, $i);
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = is_string($result) ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';
            }

        } else { // full
            $res['num_fields']= $count;

            for ($i=0; $i<$count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name']  = @mssql_field_name($id, $i);
                $res[$i]['type']  = @mssql_field_type($id, $i);
                $res[$i]['len']   = @mssql_field_length($id, $i);
                // We only support flags for tables
                $res[$i]['flags'] = is_string($result) ? $this->_mssql_field_flags($result, $res[$i]['name']) : '';
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
            @mssql_free_result($id);
        }
        return($res);
    }

    // }}}
    // {{{ _mssql_field_flags()
    /**
    * Get the flags for a field, currently only supports "isnullable" and "primary_key"
    *
    * @param string The table name
    * @param string The field
    * @access private
    */
    function _mssql_field_flags($table, $column)
    {
        static $current_table = NULL;
        static $flags;
        // At the first call we discover the flags for all fields
        if ($table != $current_table) {
            $flags = array();
            // find nullable fields
            $q_nulls = "SELECT syscolumns.name, syscolumns.isnullable
                        FROM sysobjects
                        INNER JOIN syscolumns ON sysobjects.id = syscolumns.id
                        WHERE sysobjects.name ='$table' AND syscolumns.isnullable = 1";
            $res = $this->query($q_nulls, NULL, FALSE);
            $res = $this->fetchAll($res, MDB_FETCHMODE_ASSOC);
            foreach ($res as $data) {
                if ($data['isnullable'] == 1) {
                    $flags[$data['name']][] = 'isnullable';
                }
            }
            // find primary keys
            $res2 = $this->query("EXEC SP_PKEYS[$table]", NULL, FALSE);
            $res2 = $this->fetchAll($res, MDB_FETCHMODE_ASSOC);
            foreach ($res2 as $data) {
                if (!empty($data['COLUMN_NAME'])) {
                    $flags[$data['COLUMN_NAME']][] = 'primary_key';
                }
            }
            $current_table = $table;
        }
        if (isset($flags[$column])) {
            return(implode(',', $flags[$column]));
        }
        return('');
    }
    // }}}
}

?>