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

require_once 'MDB/Common.php';

/**
 * MDB FireBird/InterBase driver
 *
 * Notes:
 * - when fetching in associative mode all keys are lowercased.
 *
 * - Currently, the driver relies on the Interbase server to use SQL dialect 3
 *   that was introduced with Interbase 6. Some versions of Interbase server,
 *   like the Super Server, do not seem to work by default with dialect 3.
 *   This may lead to errors when trying to create tables using Interbase SQL
 *   data types that are only available when using this dialect version.
 *
 * - Interbase does not support per field index sorting support. Indexes are
 *   either ascending, descending or both even when they are defined from more
 *   than one field. Currently Metabase Interbase driver uses the index sorting
 *   type given by the first field of the index for which it is specified the
 *   sorting type.
 *
 * - The numRows method is emulated by fetching all the rows into memory.
 *   Avoid using it if for queries with large result sets.
 *
 * - Interbase does not provide direct support for returning result sets
     restrictedto a given range. Such support is emulated in the MDB ibase driver.
 *
 * - Current Interbase versions do not support altering table field DEFAULT
 *   values and NOT NULL constraint. Text fields' length may only be raised in
 *   increments defined by Interbase, so the Metabase Interbase does not support
 *   altering text field length yet.
 *
 * - createDatabase and dropDatabase are not supported
 *
 * - MDB creates Interbase blobs before executing a prepared queries to insert
 *   or update large object fields. If such queries fail to execute, MDB
 *   Interbase driver class is not able to reclaim the database space allocated
 *   for the large object values because there is currently no PHP function to do so.
 *
 * @package MDB
 * @category Database
 * @author  Lorenzo Alberton <l.alberton@quipo.it>
 */

class MDB_ibase extends MDB_Common
{
    var $connection = 0;
    var $connected_host;
    var $connected_port;
    var $selected_database = '';
    var $selected_database_file = '';
    var $opened_persistent = '';
    var $transaction_id = 0;

    var $escape_quotes = "'";
    var $decimal_factor = 1.0;

    var $results = array();
    var $current_row = array();
    var $columns = array();
    var $rows = array();
    var $limits = array();
    var $row_buffer = array();
    var $highest_fetched_row = array();
    var $query_parameters = array();
    var $query_parameter_values = array();

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB_ibase()
    {
        $this->MDB_Common();
        $this->phptype  = 'ibase';
        $this->dbsyntax = 'ibase';

        $this->supported['Sequences'] = 1;
        $this->supported['Indexes'] = 1;
        $this->supported['SummaryFunctions'] = 1;
        $this->supported['OrderByText'] = 1;
        $this->supported['Transactions'] = 1;
        $this->supported['CurrId'] = 0;
        $this->supported['AffectedRows'] = 0;
        $this->supported['SelectRowRanges'] = 1;
        $this->supported['LOBs'] = 1;
        $this->supported['Replace'] = 1;
        $this->supported['SubSelects'] = 1;

        $this->decimal_factor = pow(10.0, $this->decimal_places);

        $this->options['DatabasePath'] = '';
        $this->options['DatabaseExtension'] = '.gdb';
        $this->options['DBAUser'] = FALSE;
        $this->options['DBAPassword'] = FALSE;

        $this->errorcode_map = array(
            -104 => MDB_ERROR_SYNTAX,
            -150 => MDB_ERROR_ACCESS_VIOLATION,
            -151 => MDB_ERROR_ACCESS_VIOLATION,
            -155 => MDB_ERROR_NOSUCHTABLE,
              88 => MDB_ERROR_NOSUCHTABLE,
            -157 => MDB_ERROR_NOSUCHFIELD,
            -158 => MDB_ERROR_VALUE_COUNT_ON_ROW,
            -170 => MDB_ERROR_MISMATCH,
            -171 => MDB_ERROR_MISMATCH,
            -172 => MDB_ERROR_INVALID,
            -204 => MDB_ERROR_INVALID,
            -205 => MDB_ERROR_NOSUCHFIELD,
            -206 => MDB_ERROR_NOSUCHFIELD,
            -208 => MDB_ERROR_INVALID,
            -219 => MDB_ERROR_NOSUCHTABLE,
            -297 => MDB_ERROR_CONSTRAINT,
            -530 => MDB_ERROR_CONSTRAINT,
            -551 => MDB_ERROR_ACCESS_VIOLATION,
            -552 => MDB_ERROR_ACCESS_VIOLATION,
            -607 => MDB_ERROR_NOSUCHTABLE,
            -803 => MDB_ERROR_CONSTRAINT,
            -913 => MDB_ERROR_DEADLOCK,
            -922 => MDB_ERROR_NOSUCHDB,
            -923 => MDB_ERROR_CONNECT_FAILED,
            -924 => MDB_ERROR_CONNECT_FAILED
        );

    }

    // }}}
    // {{{ errorCode()

    /**
     * Map native error codes to DB's portable ones.  Requires that
     * the DB implementation's constructor fills in the $errorcode_map
     * property.
     *
     * @param $nativecode the native error code, as returned by the backend
     * database extension (string or integer)
     * @return int a portable MDB error code, or FALSE if this DB
     * implementation has no mapping for the given error code.
     */
    function errorCode($errormsg)
    {
        static $error_regexps;
        if (empty($error_regexps)) {
            $error_regexps = array(
                '/[tT]able not found/' => MDB_ERROR_NOSUCHTABLE,
                '/[tT]able .* already exists/' => MDB_ERROR_ALREADY_EXISTS,
                '/validation error for column .* value "\*\*\* null/' => MDB_ERROR_CONSTRAINT_NOT_NULL,
                '/violation of [\w ]+ constraint/' => MDB_ERROR_CONSTRAINT,
                '/conversion error from string/' => MDB_ERROR_INVALID_NUMBER,
                '/no permission for/' => MDB_ERROR_ACCESS_VIOLATION,
                '/arithmetic exception, numeric overflow, or string truncation/' => MDB_ERROR_DIVZERO,
                '/deadlock/' => MDB_ERROR_DEADLOCK,
                '/attempt to store duplicate value/' => MDB_ERROR_CONSTRAINT
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return $code;
            }
        }
        // Fall back to MDB_ERROR if there was no mapping.
        return MDB_ERROR;
    }

    // }}}
    // {{{ ibaseRaiseError()

    /**
     * This method is used to communicate an error and invoke error
     * callbacks etc.  Basically a wrapper for MDB::raiseError
     * that checks for native error msgs.
     *
     * @param integer $db_errno error code
     * @return object a PEAR error object
     * @access public
     * @see PEAR_Error
     */
    function ibaseRaiseError($db_errno = NULL)
    {
        $native_errmsg = $this->errorNative();
        // memo for the interbase php module hackers: we need something similar
        // to mysql_errno() to retrieve error codes instead of this ugly hack
        if (preg_match('/^([^0-9\-]+)([0-9\-]+)\s+(.*)$/', $native_errmsg, $m)) {
            $native_errno = (int)$m[2];
        } else {
            $native_errno = NULL;
        }
        // try to map the native error to the DB one
        if (is_null($db_errno)) {
            if ($native_errno) {
                // try to interpret Interbase error code (that's why we need ibase_errno()
                // in the interbase module to return the real error code)
                switch ($native_errno) {
                    case -204:
                        if (is_int(strpos($m[3], 'Table unknown'))) {
                            $db_errno = MDB_ERROR_NOSUCHTABLE;
                        }
                    break;
                    default:
                        $db_errno = $this->errorCode($native_errno);
                }
            } else {
                $db_errno = $this->errorCode($native_errmsg);
            }
        }
        return $this->raiseError($db_errno, NULL, NULL, NULL, $native_errmsg);
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     * @return int native ibase error code
     */
    function errorNative()
    {
        return @ibase_errmsg();
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit flag that indicates whether the database
     *     changes should be committed right after executing every query
     *     statement. If this argument is 0 a transaction implicitly started.
     *     Otherwise, if a transaction is in progress it is ended by committing
     *     any database changes that were pending.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function autoCommit($auto_commit)
    {
        $this->debug('AutoCommit: '.($auto_commit ? 'On' : 'Off'));
        if ((!$this->auto_commit) == (!$auto_commit)) {
            return MDB_OK;
        }
        if ($this->connection && $auto_commit && MDB::isError($commit = $this->commit())) {
            return($commit);
        }
        $this->auto_commit = $auto_commit;
        $this->in_transaction = !$auto_commit;
        return MDB_OK;
    }

    // }}}
    // {{{ commit()

    /**
     * Commit the database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after committing the pending changes.
     *
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function commit()
    {
        $this->debug('Commit Transaction');
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Commit: transaction changes are being auto commited'));
        }
        return @ibase_commit($this->connection);
    }

    // }}}
    // {{{ rollback()

    /**
     * Cancel any database changes done during a transaction that is in
     * progress. This function may only be called when auto-committing is
     * disabled, otherwise it will fail. Therefore, a new transaction is
     * implicitly started after canceling the pending changes.
     *
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function rollback()
    {
        $this->debug('Rollback Transaction');
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Rollback: transactions can not be rolled back when changes are auto commited'));
        }

        //return ibase_rollback($this->connection);

        if ($this->transaction_id && !@ibase_rollback($this->connection)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Rollback: Could not rollback a pending transaction: '.@ibase_errmsg()));
        }
        if (!$this->transaction_id = @ibase_trans(IBASE_COMMITTED, $this->connection)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Rollback: Could not start a new transaction: '.@ibase_errmsg()));
        }
        return MDB_OK;
    }

    // }}}
    // {{{ getDatabaseFile()

    function getDatabaseFile($database_name)
    {
        if (isset($this->options['DatabasePath'])) {
            $this->database_path = $this->options['DatabasePath'];
        }
        if (isset($this->options['DatabaseExtension'])) {
            $this->database_extension = $this->options['DatabaseExtension'];
        }
        //$this->database_path = (isset($this->options['DatabasePath']) ? $this->options['DatabasePath'] : '');
        //$this->database_extension = (isset($this->options['DatabaseExtension']) ? $this->options['DatabaseExtension'] : '.gdb');

        //$database_path = (isset($this->options['DatabasePath']) ? $this->options['DatabasePath'] : '');
        //$database_extension = (isset($this->options['DatabaseExtension']) ? $this->options['DatabaseExtension'] : '.gdb');
        return $this->database_path.$database_name.$this->database_extension;
    }

    // }}}
    // {{{ _doConnect()

    /**
     * Does the grunt work of connecting to the database
     *
     * @return mixed connection resource on success, MDB_Error on failure
     * @access private
     **/
    function _doConnect($database_name, $persistent)
    {
        $function = ($persistent ? 'ibase_pconnect' : 'ibase_connect');
        if (!function_exists($function)) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'doConnect: FireBird/InterBase support is not available in this PHP configuration'));
        }

        $dbhost = $this->host ?
                  ($this->host . ':' . $database_name) :
                  $database_name;

        $params = array();
        $params[] = $dbhost;
        $params[] = !empty($this->user) ? $this->user : NULL;
        $params[] = !empty($this->password) ? $this->password : NULL;

        $connection = @call_user_func_array($function, $params);
        if ($connection > 0) {
            @ibase_timefmt("%Y-%m-%d %H:%M:%S", IBASE_TIMESTAMP);
            @ibase_timefmt("%Y-%m-%d", IBASE_DATE);
            return $connection;
        }
        if (isset($php_errormsg)) {
            $error_msg = $php_errormsg;
        } else {
            $error_msg = 'Could not connect to FireBird/InterBase server';
        }
        return($this->raiseError(MDB_ERROR_CONNECT_FAILED, NULL, NULL,
            'doConnect: '.$error_msg));
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     *
     * @return TRUE on success, MDB_Error on failure
     * @access public
     **/
    function connect()
    {
        $port = (isset($this->options['port']) ? $this->options['port'] : '');

        $database_file = $this->getDatabaseFile($this->database_name);

        if ($this->connection != 0) {
            if (!strcmp($this->connected_host, $this->host)
                && !strcmp($this->connected_port, $port)
                && !strcmp($this->selected_database_file, $database_file)
                && ($this->opened_persistent == $this->options['persistent']))
            {
                return MDB_OK;
            }
            @ibase_close($this->connection);
            $this->affected_rows = -1;
            $this->connection = 0;
        }
        $connection = $this->_doConnect($database_file, $this->options['persistent']);
        if (MDB::isError($connection)) {
            return $connection;
        }
        $this->connection = $connection;

        //the if below was added after PEAR::DB. Review me!!
        if ($this->dbsyntax == 'fbird') {
            $this->supported['limit'] = 'alter';
        }

        if (!$this->auto_commit && MDB::isError($trans_result = $this->_doQuery('BEGIN'))) {
            @ibase_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            return $trans_result;
        }
        $this->connected_host = $this->host;
        $this->connected_port = $port;
        $this->selected_database_file = $database_file;
        $this->opened_persistent = $this->options['persistent'];
        return MDB_OK;
    }

    // }}}
    // {{{ _close()
    /**
     * Close the database connection
     *
     * @return boolean
     * @access private
     **/
    function _close()
    {
        if ($this->connection != 0) {
            if (!$this->auto_commit) {
                $this->_doQuery('END');
            }
            @ibase_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;

            unset($GLOBALS['_MDB_databases'][$this->database]);
            return true;
        }
        return false;
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query the SQL query
     * @return mixed result identifier if query executed, else MDB_error
     * @access private
     **/
    function _doQuery($query, $first=0, $limit=0, $prepared_query=0)  // function _doQuery($query)
    {
        $connection = ($this->auto_commit ? $this->connection : $this->transaction_id);
        if ($prepared_query
            && isset($this->query_parameters[$prepared_query])
            && count($this->query_parameters[$prepared_query]) > 2)
        {

            $this->query_parameters[$prepared_query][0] = $connection;
            $this->query_parameters[$prepared_query][1] = $query;
            $result = @call_user_func_array("ibase_query", $this->query_parameters[$prepared_query]);
        } else {
            //Not Prepared Query
            $result = @ibase_query($connection, $query);
            while (@ibase_errmsg() == 'Query argument missed') { //ibase_errcode() only available in PHP5
                //connection lost, try again...
                $this->connect();
                //rollback the failed transaction to prevent deadlock and execute the query again
                if ($this->transaction_id) {
                    $this->rollback();
                }
                $result = @ibase_query($this->connection, $query);
            }
        }
        if ($result) {
            if (!MDB::isManip($query)) {
                $result_value = intval($result);
                $this->current_row[$result_value] = -1;
                if ($limit > 0) {
                    $this->limits[$result_value] = array($first, $limit, 0);
                }
                $this->highest_fetched_row[$result_value] = -1;
            } else {
                $this->affected_rows = -1;
            }
        } else {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                '_doQuery: Could not execute query ("'.$query.'"): ' . @ibase_errmsg()));
        }
        return $result;
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in
     *                         the result set
     * @return mixed result identifier if query executed, else MDB_error
     * @access public
     **/
    function query($query, $types = NULL)
    {
        $this->debug('Query: '.$query);
        $this->last_query = $query;
        $first = $this->first_selected_row;
        $limit = $this->selected_row_limit;
        $this->first_selected_row = $this->selected_row_limit = 0;
        $connected = $this->connect();
        if (MDB::isError($connected)) {
            return $connected;
        }

        if (!MDB::isError($result = $this->_doQuery($query, $first, $limit, 0))) {
            if ($types != NULL) {
                if (!is_array($types)) {
                    $types = array($types);
                }
                if (MDB::isError($err = $this->setResultTypes($result, $types))) {
                    $this->freeResult($result);
                    return $err;
                }
            }
            return $result;
        }
        return $this->ibaseRaiseError();

    }

    // }}}
    // {{{ _executePreparedQuery()

    /**
     * Execute a prepared query statement.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param string $query query to be executed
     * @param array $types array that contains the types of the columns in
     *       the result set
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     * @access private
     */
    function _executePreparedQuery($prepared_query, $query)
    {
        $first = $this->first_selected_row;
        $limit = $this->selected_row_limit;
        $this->first_selected_row = $this->selected_row_limit = 0;
        if (MDB::isError($connect = $this->connect())) {
            return $connect;
        }
        return($this->_doQuery($query, $first, $limit, $prepared_query));
    }

    // }}}
    // {{{ _skipLimitOffset()

    /**
     * Skip the first row of a result set.
     *
     * @param resource $result
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     * @access private
     */
    function _skipLimitOffset($result)
    {
        $result_value = intval($result);
        $first = $this->limits[$result_value][0];
        for (; $this->limits[$result_value][2] < $first; $this->limits[$result_value][2]++) {
            if (!is_array(@ibase_fetch_row($result))) {
                $this->limits[$result_value][2] = $first;
                return($this->raiseError(MDB_ERROR, NULL, NULL,
                    'Skip first rows: could not skip a query result row'));
            }
        }
        return MDB_OK;
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @param resource $result  result identifier
     * @return mixed an associative array variable
     *                               that will hold the names of columns.The
     *                               indexes of the array are the column names
     *                               mapped to lower case and the values are the
     *                               respective numbers of the columns starting
     *                               from 0. Some DBMS may not return any
     *                               columns when the result set does not
     *                               contain any rows.
     *
     *                               a MDB error on failure
     * @access public
     */
    function getColumnNames($result)
    {
        $result_value = intval($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Get column names: it was specified an inexisting result set'));
        }
        if (!isset($this->columns[$result_value])) {
            $this->columns[$result_value] = array();
            $columns = @ibase_num_fields($result);
            for ($column=0; $column < $columns; $column++) {
                $column_info = @ibase_field_info($result, $column);
                $field_name = $column_info['name'];
                if ($this->options['optimize'] == 'portability') {
                    $field_name = strtolower($field_name);
                }
                $this->columns[$result_value][$field_name] = $column;
            }
        }
        return $this->columns[$result_value];
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @param resource $result result identifier
     * @return mixed integer value with the number of columns, a MDB error
     *      on failure
     * @access public
     */
    function numCols($result)
    {
        if (!isset($this->highest_fetched_row[intval($result)])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Number of columns: it was specified an inexisting result set'));
        }
        return @ibase_num_fields($result);
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
        if (!isset($this->current_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'End of result: attempted to check the end of an unknown result'));
        }
        if (isset($this->results[$result_value]) && end($this->results[$result_value]) === false) {
            return($this->highest_fetched_row[$result_value] >= $this->current_row[$result_value]-1);
        }
        if (isset($this->row_buffer[$result_value])) {
            if ($this->row_buffer[$result_value]) {
                return false;
            }
            return true;
        }
        if (isset($this->limits[$result_value])) {
            if (MDB::isError($this->_skipLimitOffset($result))
                || $this->current_row[$result_value] + 1 >= $this->limits[$result_value][1]
            ) {
                return true;
            }
        }
        if (is_array($this->row_buffer[$result_value] = @ibase_fetch_row($result))) {
            return false;
        }
        $this->row_buffer[$result_value] = false;
        return true;
    }

    // }}}
    // {{{ fetch()

    /**
     * fetch value from a result set
     *
     * @param resource $result result identifier
     * @param int $rownum number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function fetch($result, $rownum, $field)
    {
        $fetchmode = is_numeric($field) ? MDB_FETCHMODE_ORDERED : MDB_FETCHMODE_ASSOC;
        $row = $this->fetchInto($result, $fetchmode, $rownum);
        if (MDB::isError($row)) {
            return $row;
        }
        if (!array_key_exists($field, $row)) {
            return null;
        }
        return $row[$field];
    }

    // }}}
    // {{{ fetchInto()

    /**
     * Fetch a row and return data in an array.
     *
     * @param resource $result result identifier
     * @param int $fetchmode how the array data should be indexed
     * @param int $rownum the row number to fetch
     * @return mixed data array or NULL on success, a MDB error on failure
     * @access public
     */
    function fetchInto($result, $fetchmode=MDB_FETCHMODE_DEFAULT, $rownum=null)
    {
        $result_value = intval($result);
        if (!isset($this->current_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'fetchInto: attemped to fetch on an unknown query result'));
        }
        if ($fetchmode == MDB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        if (is_null($rownum)) {
            $rownum = $this->current_row[$result_value] + 1;
        }
        if (!isset($this->results[$result_value][$rownum])
            && (!isset($this->results[$result_value][$this->highest_fetched_row[$result_value]])
                || $this->results[$result_value][$this->highest_fetched_row[$result_value]] !== false)
        ) {
            if (isset($this->limits[$result_value])) {
                //upper limit
                if ($rownum >= $this->limits[$result_value][1]) {
                    // are all previous rows fetched so that we can set the end
                    // of the result set and not have any "holes" in between?
                    if ($rownum == 0
                        || (isset($this->results[$result_value])
                            && count($this->results[$result_value]) == $rownum)
                    ) {
                        $this->highest_fetched_row[$result_value] = $rownum;
                        $this->current_row[$result_value] = $rownum;
                        $this->results[$result_value][$rownum] = false;
                    }
                    if ($this->options['autofree']) {
                        $this->freeResult($result);
                    }
                    return null;
                }
                // offset skipping
                if (MDB::isError($this->_skipLimitOffset($result))) {
                    $this->current_row[$result_value] = 0;
                    $this->results[$result_value] = array(false);
                    if ($this->options['autofree']) {
                        $this->freeResult($result);
                    }
                    return null;
                }
            }
            if (isset($this->row_buffer[$result_value])) {
                $this->current_row[$result_value]++;
                $this->results[$result_value][$this->current_row[$result_value]] =
                    $this->row_buffer[$result_value];
                unset($this->row_buffer[$result_value]);
            }
            if (!isset($this->results[$result_value][$rownum])
                && (!isset($this->results[$result_value][$this->highest_fetched_row[$result_value]])
                    || $this->results[$result_value][$this->highest_fetched_row[$result_value]] !== false)
            ) {
                while ($this->current_row[$result_value] < $rownum
                    && is_array($buffer = @ibase_fetch_row($result))
                ) {
                    $this->current_row[$result_value]++;
                    $this->results[$result_value][$this->current_row[$result_value]] = $buffer;
                }
                // end of result set reached
                if ($this->current_row[$result_value] < $rownum) {
                    $this->current_row[$result_value]++;
                    $this->results[$result_value][$this->current_row[$result_value]] = false;
                }
            }
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value],
                    $this->current_row[$result_value]);
        } else {
            $this->current_row[$result_value]++;
        }
        if (isset($this->results[$result_value][$rownum])
            && $this->results[$result_value][$rownum]
        ) {
            $row = $this->results[$result_value][$rownum];
        } else {
            if ($this->options['autofree']) {
                $this->freeResult($result);
            }
            return null;
        }
        foreach ($row as $key => $value_with_space) {
            if (!is_null($value_with_space)) {
                $row[$key] = rtrim($value_with_space, ' ');
            }
        }
        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $column_names = $this->getColumnNames($result);
            foreach ($column_names as $name => $i) {
                $column_names[$name] = $row[$i];
            }
            $row = $column_names;
        }
        if (isset($this->result_types[$result_value])) {
            $row = $this->convertResultRow($result, $row);
        }
        return $row;
    }

    // }}}
    // {{{ _retrieveLob()

    /**
     * fetch a lob value from a result set
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access private
     */
    function _retrieveLob($lob)
    {
        if (!isset($this->lobs[$lob])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Retrieve LOB: it was not specified a valid lob'));
        }

        if (!isset($this->lobs[$lob]['Value'])) {
            $this->lobs[$lob]['Value'] = $this->fetch($this->lobs[$lob]['Result'],
                                                      $this->lobs[$lob]['Row'],
                                                      $this->lobs[$lob]['Field']);

            if (!$this->lobs[$lob]['Handle'] = @ibase_blob_open($this->lobs[$lob]['Value'])) {
                unset($this->lobs[$lob]['Value']);
                return($this->raiseError(MDB_ERROR, NULL, NULL,
                    'Retrieve LOB: Could not open fetched large object field' . @ibase_errmsg()));
            }
        }
        return MDB_OK;
    }

    // }}}
    // {{{ endOfResultLob()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param int    $lob handle to a lob created by the createLob() function
     * @return mixed TRUE or FALSE on success, a MDB error on failure
     * @access public
     */
    function endOfResultLob($lob)
    {
        if (MDB::isError($lobresult = $this->_retrieveLob($lob))) {
            return($lobresult);
        }
        return(isset($this->lobs[$lob]['EndOfLOB']));
    }

    // }}}
    // {{{ _readResultLob()

    /**
     * Read data from large object input stream.
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @param blob $data reference to a variable that will hold data to be
     *      read from the large object input stream
     * @param int $length integer value that indicates the largest ammount of
     *      data to be read from the large object input stream.
     * @return mixed length on success, a MDB error on failure
     * @access private
     */
    function _readResultLob($lob, &$data, $length)
    {
        if (MDB::isError($lobresult = $this->_retrieveLob($lob))) {
            return $lobresult;
        }
        $data = @ibase_blob_get($this->lobs[$lob]['Handle'], $length);
        if (!is_string($data)) {
            $this->raiseError(MDB_ERROR, NULL, NULL,
                'Read Result LOB: ' . @ibase_errmsg());
        }
        if (($length = strlen($data)) == 0) {
            $this->lobs[$lob]['EndOfLOB'] = 1;
        }
        return $length;
    }

    // }}}
    // {{{ _destroyResultLob()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @access private
     */
    function _destroyResultLob($lob)
    {
        if (isset($this->lobs[$lob])) {
            if (isset($this->lobs[$lob]['Value'])) {
               @ibase_blob_close($this->lobs[$lob]['Handle']);
            }
            $this->lobs[$lob] = '';
        }
    }

    // }}}
    // {{{ fetchClob()

    /**
     * fetch a clob value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure,
     *       a MDB error on failure
     * @access public
     */
    function fetchClob($result, $row, $field)
    {
        return $this->fetchLob($result, $row, $field);
    }

    // }}}
    // {{{ fetchBlob()

    /**
     * fetch a blob value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchBlob($result, $row, $field)
    {
        return $this->fetchLob($result, $row, $field);
    }

    // }}}
    // {{{ convertResult()

    /**
     * convert a value to a RDBMS indepdenant MDB type
     *
     * @param mixed $value value to be converted
     * @param int $type constant that specifies which type to convert to
     * @return mixed converted value or a MDB error on failure
     * @access public
     */
    function convertResult($value, $type)
    {
        switch ($type) {
            case MDB_TYPE_DECIMAL:
                return sprintf('%.'.$this->decimal_places.'f', doubleval($value)/$this->decimal_factor);
            case MDB_TYPE_TIMESTAMP:
                return substr($value, 0, strlen('YYYY-MM-DD HH:MM:SS'));
            default:
                return $this->_baseConvertResult($value, $type);
        }
    }

    // }}}
    // {{{ resultIsNull()

    /**
     * Determine whether the value of a query result located in given row and
     *    field is a NULL.
     *
     * @param resource $result result identifier
     * @param int $rownum number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed TRUE or FALSE on success, a MDB error on failure
     * @access public
     */
    function resultIsNull($result, $rownum, $field)
    {
        $value = $this->fetch($result, $rownum, $field);
        if (MDB::isError($value)) {
            return $value;
        }
        return(!isset($value));
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
        if (!isset($this->current_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Number of rows: attemped to obtain the number of rows contained in an unknown query result'));
        }
        if (!isset($this->rows[$result_value][$this->highest_fetched_row[$result_value]])
            || $this->rows[$result_value][$this->highest_fetched_row[$result_value]] !== false
        ) {
            if (isset($this->limits[$result_value])) {
                if (MDB::isError($skipfirstrow = $this->_skipLimitOffset($result))) {
                    //$this->rows[$result_value] = 0;
                    return $skipfirstrow;
                }
            }
            if (isset($this->row_buffer[$result_value])) {
                $this->highest_fetched_row[$result_value]++;
                $this->results[$result_value][$this->highest_fetched_row[$result_value]]
                    = $this->row_buffer[$result_value];
                unset($this->row_buffer[$result_value]);
            }
            if (!isset($this->results[$result_value][$this->highest_fetched_row[$result_value]])
                || $this->results[$result_value][$this->highest_fetched_row[$result_value]] !== false
            ) {
                while((!isset($this->limits[$result_value])
                    || $this->highest_fetched_row[$result_value] >= $this->limits[$result_value][1]
                )
                    && (is_array($buffer = @ibase_fetch_row($result)))
                ) {
                    ++$this->highest_fetched_row[$result_value];
                    $this->results[$result_value][$this->highest_fetched_row[$result_value]] = $buffer;
                }
                ++$this->highest_fetched_row[$result_value];
                $this->results[$result_value][$this->highest_fetched_row[$result_value]] = false;
            }
        }
        return(max(0, $this->highest_fetched_row[$result_value]));
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
        if (!isset($this->current_row[$result_value])) {
           return($this->raiseError(MDB_ERROR, NULL, NULL,
               'Free result: attemped to free an unknown query result'));
        }
        if (isset($this->highest_fetched_row[$result_value])) {
            unset($this->highest_fetched_row[$result_value]);
        }
        if (isset($this->row_buffer[$result_value])) {
            unset($this->row_buffer[$result_value]);
        }
        if (isset($this->limits[$result_value])) {
            unset($this->limits[$result_value]);
        }
        if (isset($this->current_row[$result_value])) {
            unset($this->current_row[$result_value]);
        }
        if (isset($this->results[$result_value])) {
            unset($this->results[$result_value]);
        }
        if (isset($this->columns[$result_value])) {
            unset($this->columns[$result_value]);
        }
        if (isset($this->rows[$result_value])) {
            unset($this->rows[$result_value]);
        }
        if (isset($this->result_types[$result_value])) {
            unset($this->result_types[$result_value]);
        }
        if (is_resource($result)) {
            return @ibase_free_result($result);
        }
        return true;
    }
    // }}}
    // {{{ getTypeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getTypeDeclaration($field)
    {
        switch($field['type'])
        {
            case 'text':
                return('VARCHAR ('.(isset($field['length']) ? $field['length'] : (isset($this->options['DefaultTextFieldLength']) ? $this->options['DefaultTextFieldLength'] : 4000)).')');
            case 'clob':
                return 'BLOB SUB_TYPE 1';
            case 'blob':
                return 'BLOB SUB_TYPE 0';
            case 'integer':
                return 'INTEGER';
            case 'boolean':
                return 'CHAR (1)';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'TIMESTAMP';
            case 'float':
                return 'DOUBLE PRECISION';
            case 'decimal':
                return 'DECIMAL(18,'.$this->decimal_places.')';
        }
        return '';
    }

    // }}}
    // {{{ getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getTextDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['default']) ? ' DEFAULT '.$this->getTextValue($field['default']) : '').(IsSet($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getClobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the large
     *          object field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getClobDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getBlobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the large
     *          object field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getBlobDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Date value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getDateDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['default']) ? ' DEFAULT "'.$field['default'].'"' : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Time value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getTimeDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['default']) ? ' DEFAULT "'.$field['default'].'"' : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Float value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getFloatDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['default']) ? ' DEFAULT '.$this->getFloatValue($field['default']) : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param string $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      default
     *          Decimal value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to NULL.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getDecimalDeclaration($name, $field)
    {
        return($name.' '.$this->getTypeDeclaration($field).(isset($field['default']) ? ' DEFAULT '.$this->getDecimalValue($field['default']) : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ _getLobValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param           $parameter
     * @param           $lob
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access private
     */
    function _getLobValue($prepared_query, $parameter, $lob)
    {
        if (MDB::isError($connect = $this->connect())) {
            return $connect;
        }
        $value   = '';  // DEAL WITH ME
        if (!$this->transaction_id = @ibase_trans(IBASE_COMMITTED, $this->connection)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL, '_getLobValue: Could not start a new transaction: '.@ibase_errmsg()));
        }

        if (($lo = @ibase_blob_create($this->auto_commit ? $this->connection : $this->transaction_id))) {
            while (!$this->endOfLob($lob)) {
                if (MDB::isError($result = $this->readLob($lob, $data, $this->options['lob_buffer_length']))) {
                    break;
                }
                if (@ibase_blob_add($lo, $data) === false) {
                    $result = $this->raiseError(MDB_ERROR, NULL, NULL,
                        '_getLobValue - Could not add data to a large object: ' . @ibase_errmsg());
                    break;
                }
            }
            if (MDB::isError($result)) {
                @ibase_blob_cancel($lo);
            } else {
                $value = @ibase_blob_close($lo);
            }
        } else {
            $result = $this->raiseError(MDB_ERROR, NULL, NULL,
                'Get LOB field value' . @ibase_errmsg());
        }
        if (!isset($this->query_parameters[$prepared_query])) {
            $this->query_parameters[$prepared_query]       = array(0, '');
            $this->query_parameter_values[$prepared_query] = array();
        }
        $query_parameter = count($this->query_parameters[$prepared_query]);
        $this->query_parameter_values[$prepared_query][$parameter] = $query_parameter;
        $this->query_parameters[$prepared_query][$query_parameter] = $value;
        $value = '?';

        if (!$this->auto_commit) {
            $this->commit();
        }
        return $value;
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
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access public
     */
    function getClobValue($prepared_query, $parameter, $clob)
    {
        return $this->_getLobValue($prepared_query, $parameter, $clob);
    }


    // }}}
    // {{{ freeLobValue()

    /**
     * free a large object
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param string    $lob
     * @param string    $value
     * @return MDB_OK
     * @access public
     */
    function freeLobValue($prepared_query, $lob, &$value)
    {
        $query_parameter=$this->query_parameter_values[$prepared_query][$lob];

        unset($this->query_parameters[$prepared_query][$query_parameter]);
        unset($this->query_parameter_values[$prepared_query][$lob]);
        if (count($this->query_parameter_values[$prepared_query]) == 0) {
            unset($this->query_parameters[$prepared_query]);
            unset($this->query_parameter_values[$prepared_query]);
        }
        unset($value);
    }

    // }}}
    // {{{ freeClobValue()

    /**
     * free a character large object
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param string    $clob
     * @param string    $value
     * @return MDB_OK
     * @access public
     */
    function freeClobValue($prepared_query, $clob, &$value)
    {
        $this->freeLobValue($prepared_query, $clob, $value);
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
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access public
     */
    function getBlobValue($prepared_query, $parameter, $blob)
    {
        return $this->_getLobValue($prepared_query, $parameter, $blob);
    }

    // }}}
    // {{{ freeBlobValue()

    /**
     * free a binary large object
     *
     * @param resource  $prepared_query query handle from prepare()
     * @param string    $blob
     * @param string    $value
     * @return MDB_OK
     * @access public
     */
    function freeBlobValue($prepared_query, $blob, &$value)
    {
        $this->freeLobValue($prepared_query, $blob, $value);
    }

    // }}}
    // {{{ getFloatValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access public
     */
    function getFloatValue($value)
    {
        return (($value === null) ? 'NULL' : $value);
    }

    // }}}
    // {{{ getDecimalValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *      a DBMS specific format.
     * @access public
     */
    function getDecimalValue($value)
    {
        return (($value === null) ? 'NULL' : strval(round($value*$this->decimal_factor)));
    }

    // }}}
    // {{{ affectedRows()

    /**
     * returns the affected rows of a query
     *
     * @return mixed MDB Error Object or number of rows
     * @access public
     */
    function affectedRows()
    {
        if (function_exists('ibase_affected_rows')) { //PHP5 only
            $affected_rows = @ibase_affected_rows($this->connection);
            if ($affected_rows === false) {
                return $this->raiseError(MDB_ERROR_NEED_MORE_DATA);
            }
            return $affected_rows;
        }
        return parent::affectedRows();
    }

    // }}}
    // {{{ nextId()

    /**
     * returns the next free id of a sequence
     *
     * @param string  $seq_name name of the sequence
     * @param boolean $ondemand when TRUE the seqence is
     *                          automatic created, if it
     *                          not exists
     * @return mixed MDB_Error or id
     * @access public
     */
    function nextId($seq_name, $ondemand = true)
    {
        if (MDB::isError($connect = $this->connect())) {
            return $connect;
        }
        //$sequence_name = $this->getSequenceName($seq_name);
        $sequence_name = strtoupper($this->getSequenceName($seq_name));
        $this->expectError(MDB_ERROR_NOSUCHTABLE);
        $query = "SELECT GEN_ID($sequence_name, 1) as the_value FROM RDB\$DATABASE";
        $result = $this->_doQuery($query);
        $this->popExpect();
        if ($ondemand && MDB::isError($result)) {
            $result = $this->createSequence($seq_name, 1);
            if (MDB::isError($result)) {
                return $result;
            }
            return $this->nextId($seq_name, false);
        }
        return $this->fetchOne($result);
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
        $sequence_name = strtoupper($this->getSequenceName($seq_name));
        //$sequence_name = $this->getSequenceName($seq_name);
        $query = "SELECT RDB\$GENERATOR_ID FROM RDB\$GENERATORS WHERE RDB\$GENERATOR_NAME='$sequence_name'";
        if (MDB::isError($result = $this->queryOne($query))) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'currId: Unable to select from ' . $seqname) );
        }
        if (!is_numeric($result)) {
            //var_dump($result); ==> null
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'currId: could not find value in sequence table'));
        }
        return $result;
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal ibase result pointer to the next available result
     *
     * @param $result a valid ibase result resource
     * @return TRUE if a result is available otherwise return FALSE
     * @access public
     */
    function nextResult($result)
    {
        return false;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * returns meta data about the result set
     *
     * @param  mixed $resource FireBird/InterBase result identifier or table name
     * @param mixed $mode depends on implementation
     * @return array an nested array, or a MDB error
     * @access public
     */
    function tableInfo($result, $mode = NULL)
    {
        $count = 0;
        $id = 0;
        $res = array();

        /**
         * depending on $mode, metadata returns the following values:
         *
         * - mode is FALSE (default):
         * $result[]:
         *    [0]['table']  table name
         *    [0]['name']   field name
         *    [0]['type']   field type
         *    [0]['len']    field length
         *    [0]['flags']  field flags
         *
         * - mode is MDB_TABLEINFO_ORDER
         * $result[]:
         *    ['num_fields'] number of metadata records
         *    [0]['table']  table name
         *    [0]['name']   field name
         *    [0]['type']   field type
         *    [0]['len']    field length
         *    [0]['flags']  field flags
         *    ['order'][field name]  index of field named 'field name'
         *    The last one is used, if you have a field name, but no index.
         *    Test:  if (isset($result['meta']['myfield'])) { ...
         *
         * - mode is MDB_TABLEINFO_ORDERTABLE
         *     the same as above. but additionally
         *    ['ordertable'][table name][field name] index of field
         *       named 'field name'
         *
         *       this is, because if you have fields from different
         *       tables with the same field name * they override each
         *       other with MDB_TABLEINFO_ORDER
         *
         *       you can combine MDB_TABLEINFO_ORDER and
         *       MDB_TABLEINFO_ORDERTABLE with MDB_TABLEINFO_ORDER |
         *       MDB_TABLEINFO_ORDERTABLE * or with MDB_TABLEINFO_FULL
         **/

        // if $result is a string, then we want information about a
        // table without a resultset
        if (is_string($result)) {
            $id = @ibase_query($this->connection,"SELECT * FROM $result");
            if (empty($id)) {
                return $this->ibaseRaiseError();
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return $this->ibaseRaiseError();
            }
        }

        $count = @ibase_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {
            for ($i=0; $i<$count; $i++) {
                $info = @ibase_field_info($id, $i);
                //$res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['table'] = (is_string($result)) ? $result : $info['relation'];
                $res[$i]['name']  = $info['name'];
                $res[$i]['type']  = $info['type'];
                $res[$i]['len']   = $info['length'];
                //$res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
                $res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($id, $i, $result) : '';
            }
        } else { // full
            $res['num_fields'] = $count;

            for ($i=0; $i<$count; $i++) {
                $info = @ibase_field_info($id, $i);
                //$res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['table'] = (is_string($result)) ? $result : $info['relation'];
                $res[$i]['name']  = $info['name'];
                $res[$i]['type']  = $info['type'];
                $res[$i]['len']   = $info['length'];
                //$res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($info['name'], $result) : '';
                $res[$i]['flags'] = (is_string($result)) ? $this->_ibaseFieldFlags($id, $i, $result) : '';
                if ($mode & MDB_TABLEINFO_ORDER) {
                    $res['order'][$res[$i]['name']] = $i;
                }
                if ($mode & MDB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                }
            }
        }

        // free the result only if we were called on a table
        if (is_string($result) && is_resource($id)) {
            @ibase_free_result($id);
        }
        return $res;
    }

    // }}}
    // {{{ _ibaseFieldFlags()

    /**
     * get the Flags of a Field
     *
     * @param int $resource FireBird/InterBase result identifier
     * @param int $num_field the field number
     * @return string The flags of the field ('not_null', 'default_xx', 'primary_key',
     *                 'unique' and 'multiple_key' are supported)
     * @access private
     **/
    function _ibaseFieldFlags($resource, $num_field, $table_name)
    {
        $field_name = @ibase_field_info($resource, $num_field);
        $field_name = @$field_name['name'];
        $sql = 'SELECT  R.RDB$CONSTRAINT_TYPE CTYPE'
               .' FROM  RDB$INDEX_SEGMENTS I'
               .' JOIN  RDB$RELATION_CONSTRAINTS R ON I.RDB$INDEX_NAME=R.RDB$INDEX_NAME'
              .' WHERE  I.RDB$FIELD_NAME=\''.$field_name.'\''
                 .' AND UPPER(R.RDB$RELATION_NAME)=\''.strtoupper($table_name).'\'';
        $result = @ibase_query($this->connection, $sql);
        if (empty($result)) {
            return $this->ibaseRaiseError();
        }
        $flags = '';
        if ($obj = @ibase_fetch_object($result)) {
            @ibase_free_result($result);
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'PRIMARY KEY') {
                $flags = 'primary_key ';
            }
            if (isset($obj->CTYPE)  && trim($obj->CTYPE) == 'UNIQUE') {
                $flags .= 'unique_key ';
            }
        }

        $sql = 'SELECT  R.RDB$NULL_FLAG AS NFLAG,'
                     .' R.RDB$DEFAULT_SOURCE AS DSOURCE,'
                     .' F.RDB$FIELD_TYPE AS FTYPE,'
                     .' F.RDB$COMPUTED_SOURCE AS CSOURCE'
               .' FROM  RDB$RELATION_FIELDS R '
               .' JOIN  RDB$FIELDS F ON R.RDB$FIELD_SOURCE=F.RDB$FIELD_NAME'
              .' WHERE  UPPER(R.RDB$RELATION_NAME)=\''.strtoupper($table_name).'\''
                .' AND  R.RDB$FIELD_NAME=\''.$field_name.'\'';
        $result = @ibase_query($this->connection, $sql);
        if (empty($result)) {
            return $this->ibaseRaiseError();
        }
        if ($obj = @ibase_fetch_object($result)) {
            @ibase_free_result($result);
            if (isset($obj->NFLAG)) {
                $flags .= 'not_null ';
            }
            if (isset($obj->DSOURCE)) {
                $flags .= 'default ';
            }
            if (isset($obj->CSOURCE)) {
                $flags .= 'computed ';
            }
            if (isset($obj->FTYPE)  && $obj->FTYPE == 261) {
                $flags .= 'blob ';
            }
        }

         return trim($flags);
    }
}

?>