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

require_once('MDB/Common.php');

/**
 * MDB OCI8 driver
 *
 * Notes:
 * - when fetching in associative mode all keys are uppercased which is not the
 *   intenteded behavior. Due to BC issues this will not be changed in MDB 1.x
 *   however.
 *
 * - createDatabase and dropDatabase are not supported
 *
 * - Text fields with unspecified length limit are created as VARCHAR with an
 *   optional limit that may not exceed 4000 characters.
 *
 * - date fields are emulated with date fields with time set to 00:00:00.
     time fields are emulated with date fields with the day set to 0001-01-01.
 *
 * - The numRows method is emulated by fetching all the rows into memory.
 *   Avoid using it if for queries with large result sets.
 *
 * - Oracle does not provide direct support for returning result sets restricted
     to a given range. Such support is emulated in the MDB oci8 driver.
 *
 * - Storing data in large object fields has to be done in two phases: first the
     fields are initialized using a INSERT or UPDATE query that sets the fields
     to an empty value, then the data values are uploaded to the large objects
     returned by reference from the executed queries.
 *   Besides the fact that only INSERT and UPDATE queries are supported to
     upload large object data values, only UPDATE queries that affect only one
     row will set the large object fields correctly.
 *
 * - The driver alterTable method does not implement table or column renaming.
 *
 * @package MDB
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com> 
 */
class MDB_oci8 extends MDB_Common {
    var $connection = 0;
    var $connected_user;
    var $connected_password;

    var $escape_quotes = "'";

    var $uncommitedqueries = 0;

    var $results = array();
    var $current_row = array();
    var $columns = array();
    var $rows = array();
    var $limits = array();
    var $row_buffer = array();
    var $highest_fetched_row = array();

    // {{{ constructor

    /**
     * Constructor
     */
    function MDB_oci8()
    {
        $this->MDB_Common();
        $this->phptype = 'oci8';
        $this->dbsyntax = 'oci8';
        
        $this->supported['Sequences'] = 1;
        $this->supported['Indexes'] = 1;
        $this->supported['SummaryFunctions'] = 1;
        $this->supported['OrderByText'] = 1;
        $this->supported["AffectedRows"]= 1;
        $this->supported['Transactions'] = 1;
        $this->supported['SelectRowRanges'] = 1;
        $this->supported['LOBs'] = 1;
        $this->supported['Replace'] = 1;
        $this->supported['SubSelects'] = 1;
        
        $this->options['DBAUser'] = FALSE;
        $this->options['DBAPassword'] = FALSE;
        
        $this->errorcode_map = array(
            1 => MDB_ERROR_CONSTRAINT,
            900 => MDB_ERROR_SYNTAX,
            904 => MDB_ERROR_NOSUCHFIELD,
            921 => MDB_ERROR_SYNTAX,
            923 => MDB_ERROR_SYNTAX,
            942 => MDB_ERROR_NOSUCHTABLE,
            955 => MDB_ERROR_ALREADY_EXISTS,
            1476 => MDB_ERROR_DIVZERO,
            1722 => MDB_ERROR_INVALID_NUMBER,
            2289 => MDB_ERROR_NOSUCHTABLE,
            2291 => MDB_ERROR_CONSTRAINT,
            2449 => MDB_ERROR_CONSTRAINT,
        );
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     * 
     * @access public 
     * @return int native oci8 error code
     */
    function errorNative($statement = NULL)
    {
        if (is_resource($statement)) {
            $error = @OCIError($statement);
        } else {
            $error = @OCIError($this->connection);
        }
        if (is_array($error)) {
            return($error['code']);
        }
        return(FALSE);
    }

    // }}}
    // {{{ oci8RaiseError()

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
    function oci8RaiseError($errno = NULL, $message = NULL)
    {
        if ($errno === NULL) {
            $error = @OCIError($this->connection);
            return($this->raiseError($this->errorCode($error['code']),
                NULL, NULL, NULL, $error['message']));
        } elseif (is_resource($errno)) {
            $error = @OCIError($errno);
            return($this->raiseError($this->errorCode($error['code']),
                NULL, NULL, NULL, $error['message']));
        }
        return($this->raiseError($this->errorCode($errno), NULL, NULL, $message));
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     * 
     * @param boolean $auto_commit flag that indicates whether the database
     *                                 changes should be committed right after
     *                                 executing every query statement. If this
     *                                 argument is 0 a transaction implicitly
     *                                 started. Otherwise, if a transaction is
     *                                 in progress it is ended by committing any
     *                                 database changes that were pending.
     * @access public 
     * @return mixed MDB_OK on success, a MDB error on failure
     */
    function autoCommit($auto_commit)
    {
        $this->debug('AutoCommit: '.($auto_commit ? 'On' : 'Off'));
        if ($this->auto_commit == $auto_commit) {
            return(MDB_OK);
        }
        if ($this->connection && $auto_commit && MDB::isError($commit = $this->commit())) {
            return($commit);
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
        if ($this->uncommitedqueries) {
            if (!@OCICommit($this->connection)) {
                return($this->oci8RaiseError(NULL,
                    'Commit transactions: Could not commit pending transaction: '."$message. Error: ".$error['code'].' ('.$error['message'].')'));
            }
            $this->uncommitedqueries = 0;
        }
        return(MDB_OK);
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
     * @return mixed MDB_OK on success, a MDB error on failure
     */
    function rollback()
    {
        $this->debug('Rollback Transaction');
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Rollback transactions: transactions can not be rolled back when changes are auto commited'));
        }
        if ($this->uncommitedqueries) {
            if (!@OCIRollback($this->connection)) {
                return($this->oci8RaiseError(NULL,
                    'Rollback transaction: Could not rollback pending transaction'));
            }
            $this->uncommitedqueries = 0;
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ connect()

    /**
     * Connect to the database
     * 
     * @return TRUE on success, MDB_Error on failure
     */
    function connect($user = NULL , $password = NULL, $persistent = NULL)
    {
        if($user === NULL) {
            $user = $this->user;
        }
        if($password === NULL) {
            $password = $this->password;
        }
        if($persistent === NULL) {
            $persistent = $this->getOption('persistent');
        }
        if (isset($this->host)) {
            $sid = $this->host;
        } else {
            $sid = getenv('ORACLE_SID');
        }
        if(!strcmp($sid, '')) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Connect: it was not specified a valid Oracle Service IDentifier (SID)'));
        }
        if($this->connection != 0) {
            if (!strcmp($this->connected_user, $user)
                && !strcmp($this->connected_password, $password)
                && $this->opened_persistent == $persistent)
            {
                return(MDB_OK);
            }
            $this->_close();
        }

        if(!PEAR::loadExtension($this->phptype)) {
            return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND,
                NULL, NULL, 'extension '.$this->phptype.' is not compiled into PHP',
                'MDB_Error', TRUE));
        }

        if (isset($this->options['HOME'])) {
            putenv('ORACLE_HOME='.$this->options['HOME']);
        }
        putenv('ORACLE_SID='.$sid);
        $function = ($persistent ? 'OCIPLogon' : 'OCINLogon');
        if (!function_exists($function)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Connect: Oracle OCI API support is not available in this PHP configuration'));
        }
        if (!($this->connection = @$function($user, $password, $sid))) {
            return($this->oci8RaiseError(NULL,
                'Connect: Could not connect to Oracle server'));
        }
        if (MDB::isError($doquery = $this->_doQuery("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'"))) {
            $this->_close();
            return($doquery);
        }
        if (MDB::isError($doquery = $this->_doQuery("ALTER SESSION SET NLS_NUMERIC_CHARACTERS='. '"))) {
            $this->_close();
            return($doquery);
        }

        $this->connected_user = $user;
        $this->connected_password = $password;
        $this->opened_persistent = $persistent;
        return(MDB_OK);
    }

    // }}}
    // {{{ _close()

    /**
     * all the RDBMS specific things needed close a DB connection
     * 
     * @access private 
     */
    function _close()
    {
        if ($this->connection != 0) {
            @OCILogOff($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            $this->uncommitedqueries = 0;
        }
    }

    // }}}
    // {{{ _doQuery()

    /**
     * all the RDBMS specific things needed close a DB connection
     * 
     * @access private
     */
    function _doQuery($query, $first = 0, $limit = 0, $prepared_query = 0)
    {
        $lobs = 0;
        $success = MDB_OK;
        $result = 0;
        $descriptors = array();
        if ($prepared_query) {
            $columns = '';
            $variables = '';
            for(reset($this->clobs[$prepared_query]), $clob = 0;
                $clob < count($this->clobs[$prepared_query]);
                $clob++, next($this->clobs[$prepared_query]))
            {
                $position = key($this->clobs[$prepared_query]);
                if (gettype($descriptors[$position] = @OCINewDescriptor($this->connection, OCI_D_LOB)) != 'object') {
                    $success = $this->raiseError(MDB_ERROR, NULL, NULL,
                        'Do query: Could not create descriptor for clob parameter');
                    break;
                }
                $columns.= ($lobs == 0 ? ' RETURNING ' : ',').$this->prepared_queries[$prepared_query-1]['Fields'][$position-1];
                $variables.= ($lobs == 0 ? ' INTO ' : ',').':clob'.$position;
                $lobs++;
            }
            if (!MDB::isError($success)) {
                for(reset($this->blobs[$prepared_query]), $blob = 0;$blob < count($this->blobs[$prepared_query]);$blob++, next($this->blobs[$prepared_query])) {
                    $position = key($this->blobs[$prepared_query]);
                    if (gettype($descriptors[$position] = @OCINewDescriptor($this->connection, OCI_D_LOB)) != 'object') {
                        $success = $this->raiseError(MDB_ERROR, NULL, NULL,
                            'Do query: Could not create descriptor for blob parameter');
                        break;
                    }
                    $columns.= ($lobs == 0 ? ' RETURNING ' : ',').$this->prepared_queries[$prepared_query-1]['Fields'][$position-1];
                    $variables.= ($lobs == 0 ? ' INTO ' : ',').':blob'.$position;
                    $lobs++;
                }
                $query.= $columns.$variables;
            }
        }
        if (!MDB::isError($success)) {
            if (($statement = @OCIParse($this->connection, $query))) {
                if ($lobs) {
                    for(reset($this->clobs[$prepared_query]), $clob = 0;$clob < count($this->clobs[$prepared_query]);$clob++, next($this->clobs[$prepared_query])) {
                        $position = key($this->clobs[$prepared_query]);
                        if (!@OCIBindByName($statement, ':clob'.$position, $descriptors[$position], -1, OCI_B_CLOB)) {
                            $success = $this->oci8RaiseError(NULL,
                                'Do query: Could not bind clob upload descriptor');
                            break;
                        }
                    }
                    if (!MDB::isError($success)) {
                        for(reset($this->blobs[$prepared_query]), $blob = 0;
                            $blob < count($this->blobs[$prepared_query]);
                            $blob++, next($this->blobs[$prepared_query]))
                        {
                            $position = key($this->blobs[$prepared_query]);
                            if (!@OCIBindByName($statement, ':blob'.$position, $descriptors[$position], -1, OCI_B_BLOB)) {
                                $success = $this->oci8RaiseError(NULL,
                                    'Do query: Could not bind blob upload descriptor');
                                break;
                            }
                        }
                    }
                }
                if (!MDB::isError($success)) {
                    if (($result = @OCIExecute($statement, ($lobs == 0 && $this->auto_commit) ? OCI_COMMIT_ON_SUCCESS : OCI_DEFAULT))) {
                        if ($lobs) {
                            for(reset($this->clobs[$prepared_query]), $clob = 0;
                                $clob < count($this->clobs[$prepared_query]);
                                $clob++, next($this->clobs[$prepared_query]))
                            {
                                $position = key($this->clobs[$prepared_query]);
                                $clob_stream = $this->prepared_queries[$prepared_query-1]['Values'][$position-1];
                                for($value = '';!$this->endOfLOB($clob_stream);) {
                                    if ($this->readLOB($clob_stream, $data, $this->getOption('lob_buffer_length')) < 0) {
                                        $success = $this->raiseError();
                                        break;
                                    }
                                    $value.= $data;
                                }
                                if (!MDB::isError($success) && !$descriptors[$position]->save($value)) {
                                    $success = $this->oci8RaiseError(NULL,
                                        'Do query: Could not upload clob data');
                                }
                            }
                            if (!MDB::isError($success)) {
                                for(reset($this->blobs[$prepared_query]), $blob = 0;$blob < count($this->blobs[$prepared_query]);$blob++, next($this->blobs[$prepared_query])) {
                                    $position = key($this->blobs[$prepared_query]);
                                    $blob_stream = $this->prepared_queries[$prepared_query-1]['Values'][$position-1];
                                    for($value = '';!$this->endOfLOB($blob_stream);) {
                                        if ($this->readLOB($blob_stream, $data, $this->getOption('lob_buffer_length')) < 0) {
                                            $success = $this->raiseError();
                                            break;
                                        }
                                        $value.= $data;
                                    }
                                    if (!MDB::isError($success) && !$descriptors[$position]->save($value)) {
                                        $success = $this->oci8RaiseError(NULL,
                                                'Do query: Could not upload blob data');
                                    }
                                }
                            }
                        }
                        if ($this->auto_commit) {
                            if ($lobs) {
                                if (MDB::isError($success)) {
                                    if (!@OCIRollback($this->connection)) {
                                        $success = $this->oci8RaiseError(NULL,
                                            'Do query: '.$success->getUserinfo().' and then could not rollback LOB updating transaction');
                                    }
                                } else {
                                    if (!@OCICommit($this->connection)) {
                                        $success = $this->oci8RaiseError(NULL,
                                            'Do query: Could not commit pending LOB updating transaction');
                                    }
                                }
                            }
                        } else {
                            $this->uncommitedqueries++;
                        }
                        if (!MDB::isError($success)) {
                            switch (@OCIStatementType($statement)) {
                                case 'SELECT':
                                    $result_value = intval($statement);
                                    $this->current_row[$result_value] = -1;
                                    if ($limit > 0) {
                                        $this->limits[$result_value] = array($first, $limit, 0);
                                    }
                                    $this->highest_fetched_row[$result_value] = -1;
                                    break;
                                default:
                                    $this->affected_rows = @OCIRowCount($statement);
                                    @OCIFreeCursor($statement);
                                    break;
                            }
                            $result = $statement;
                        }
                    } else {
                        return($this->oci8RaiseError($statement, 'Do query: Could not execute query'));
                    }
                }
            } else {
                return($this->oci8RaiseError(NULL, 'Do query: Could not parse query'));
            }
        }
        for(reset($descriptors), $descriptor = 0;
            $descriptor < count($descriptors);
            $descriptor++, next($descriptors))
        {
            @OCIFreeDesc($descriptors[key($descriptors)]);
        }
        return($result);
    }

    // }}}
    // {{{ query()

   /**
     * Send a query to the database and return any results
     * 
     * @access public 
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in
     *                         the result set
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     */
    function query($query, $types = NULL)
    {
        $this->debug("Query: $query");
        $this->last_query = $query;
        $first = $this->first_selected_row;
        $limit = $this->selected_row_limit;
        $this->first_selected_row = $this->selected_row_limit = 0;
        if (MDB::isError($connect = $this->connect())) {
            return($connect);
        }
        if(!MDB::isError($result = $this->_doQuery($query, $first, $limit))) {
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
        return($this->oci8RaiseError());
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
            return($connect);
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
        for(;$this->limits[$result_value][2] < $first;$this->limits[$result_value][2]++) {
            if (!@OCIFetch($result)) {
                $this->limits[$result_value][2] = $first;
                return($this->raiseError(MDB_ERROR, NULL, NULL,
                    'Skip first rows: could not skip a query result row'));
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     * 
     * @param resource $result result identifier
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
            $columns = @OCINumCols($result);
            for($column = 0; $column < $columns; $column++) {
                $field_name = @OCIColumnName($result, $column + 1);
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
     * @param resource $result result identifier
     * @return mixed integer value with the number of columns, a MDB error
     *      on failure
     * @access public
     */
    function numCols($result)
    {
        $result_value = intval($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Number of columns: it was specified an inexisting result set'));
        }
        return(@OCINumCols($result));
    }

    // }}}
    // {{{ endOfResult()

    /**
     * check if the end of the result set has been reached
     * 
     * @param resource $result result identifier
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
                return(FALSE);
            }
            return(TRUE);
        }
        if (isset($this->limits[$result_value])) {
            if (MDB::isError($this->_skipLimitOffset($result))
                || $this->current_row[$result_value] > $this->limits[$result_value][1]
            ) {
                return(TRUE);
            }
        }
        if (@OCIFetchInto($result, $this->row_buffer[$result_value], OCI_RETURN_NULLS)) {
            return(FALSE);
        }
        $this->row_buffer[$result_value] = FALSE;
        return(TRUE);
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
            unset($lob_object);
            $result = $this->lobs[$lob]['Result'];
            $row = $this->lobs[$lob]['Row'];
            $field = $this->lobs[$lob]['Field'];
            $lob_object = $this->fetch($result, $row, $field);
            if (MDB::isError($lob_object)) {
                return $lob_object;
            }
            if (gettype($lob_object) != 'object') {
                return($this->raiseError(MDB_ERROR, NULL, NULL,
                    'Retrieve LOB: attemped to retrieve LOB from non existing or NULL column'));
            }
            $this->lobs[$lob]['Value'] = $lob_object->load();
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ fetch()

    /**
     * fetch value from a result set
     * 
     * @param resource $result result identifier
     * @param int $rownum number of the row where the data can be found
     * @param int $colnum field number where the data can be found
     * @return mixed string on success, a MDB error on failure
     * @access public 
     */
    function fetch($result, $rownum, $colnum)
    {
        $fetchmode = is_numeric($colnum) ? MDB_FETCHMODE_ORDERED : MDB_FETCHMODE_ASSOC;
        $row = $this->fetchInto($result, $fetchmode, $rownum);
        if (MDB::isError($row)) {
            return($row);
        }
        if (!array_key_exists($colnum, $row)) {
            return(NULL);
        }
        return($row[$colnum]);
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
     *                a MDB error on failure
     * @access public 
     */
    function fetchClob($result, $row, $field)
    {
        return($this->fetchLOB($result, $row, $field));
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
        return($this->fetchLOB($result, $row, $field));
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
            return($value);
        }
        return(!isset($value));
    }

    // }}}
    // {{{ convertResult()

    /**
     * convert a value to a RDBMS indepdenant MDB type
     * 
     * @param mixed $value value to be converted
     * @param int $type constant that specifies which type to convert to
     * @return mixed converted value
     * @access public 
     */
    function convertResult($value, $type)
    {
        switch ($type) {
            case MDB_TYPE_DATE:
                return(substr($value, 0, strlen('YYYY-MM-DD')));
            case MDB_TYPE_TIME:
                return(substr($value, strlen('YYYY-MM-DD '), strlen('HH:MI:SS')));
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
        $result_value = intval($result);
        if (!isset($this->current_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Number of rows: attemped to obtain the number of rows contained in an unknown query result'));
        }
        if (!isset($this->results[$result_value][$this->highest_fetched_row[$result_value]])
            || $this->results[$result_value][$this->highest_fetched_row[$result_value]] !== false
        ) {
            if (isset($this->limits[$result_value])) {
                if (MDB::isError($skipfirstrow = $this->_skipLimitOffset($result))) {
                     return($skipfirstrow);
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
                while((!isset($this->limits[$result_value]) || $this->highest_fetched_row[$result_value] >= $this->limits[$result_value][1])
                    && @OCIFetchInto($result, $buffer, OCI_RETURN_NULLS)
                ) {
                    ++$this->highest_fetched_row[$result_value];
                    $this->results[$result_value][$this->current_row[$result_value]] = $buffer;
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
     * @param  $result result identifier
     * @return bool TRUE on success, FALSE if $result is invalid
     * @access public 
     */
    function freeResult($result)
    {
        $result_value = intval($result);
        if (!isset($this->current_row[$result_value])) {
           return($this->raiseError(MDB_ERROR, NULL, NULL,
               'Free result: attemped to free an unknown query result'));
        }
        if(isset($this->highest_fetched_row[$result_value])) {
            unset($this->highest_fetched_row[$result_value]);
        }
        if(isset($this->row_buffer[$result_value])) {
            unset($this->row_buffer[$result_value]);
        }
        if(isset($this->limits[$result_value])) {
            unset($this->limits[$result_value]);
        }
        if(isset($this->current_row[$result_value])) {
            unset($this->current_row[$result_value]);
        }
        if(isset($this->results[$result_value])) {
            unset($this->results[$result_value]);
        }
        if(isset($this->columns[$result_value])) {
            unset($this->columns[$result_value]);
        }
        if(isset($this->result_types[$result_value])) {
            unset($this->result_types[$result_value]);
        }
        return(@OCIFreeCursor($result));
    }

    // }}}
    // {{{ getTypeDeclaration()

    /**
     * Obtain DBMS specific native datatype as a string
     * 
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     * @return string with the correct RDBMS native type
     * @access public 
     */
    function getTypeDeclaration($field)
    {
        switch ($field['type']) {
            case 'integer':
                return('INT');
            case 'text':
                return('VARCHAR ('.(isset($field['length'])
                    ? $field['length'] : (isset($this->options['DefaultTextFieldLength'])
                        ? $this->options['DefaultTextFieldLength'] : 4000)).')');
            case 'boolean':
                return('CHAR (1)');
            case 'date':
            case 'time':
            case 'timestamp':
                return('DATE');
            case 'float':
                return('NUMBER');
            case 'decimal':
                return('NUMBER(*,'.$this->decimal_places.')');
        }
        return('');
    }

    // }}}
    // {{{ getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Id
     * ently, the types
     *        of supported field properties are as follows:
     * 
     *        unsigned
     *            Boolean flag that indicates whether the field should be
     *            declared as unsigned integer if possible.
     * 
     *        default
     *            Integer value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getIntegerDeclaration($name, $field)
    {
        if (isset($field['unsigned']))
            $this->warning = "unsigned integer field \"$name\" is being declared as signed integer";
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '.$field['default'] : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getTextDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        length
     *            Integer value that determines the maximum length of the text
     *            field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     * 
     *        default
     *            Text value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getTextDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '.$this->getTextValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getClobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getClobDeclaration($name, $field)
    {
        return("$name CLOB".(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getBlobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getBlobDeclaration($name, $field)
    {
        return("$name BLOB".(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getBooleanDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a boolean type
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        default
     *            Boolean value to be used as default for this field.
     * 
     *        notnullL
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getBooleanDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '
            .$this->getBooleanValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        default
     *            Date value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getDateDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '
            .$this->getDateValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a timestamp
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        default
     *            Timestamp value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getTimestampDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '
            .$this->getTimestampValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        default
     *            Time value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getTimeDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '
            .$this->getTimeValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        default
     *            Float value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getFloatDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '
            .$this->getFloatValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     * 
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     * 
     *        default
     *            Decimal value to be used as default for this field.
     * 
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public 
     */
    function getDecimalDeclaration($name, $field)
    {
        return("$name ".$this->getTypeDeclaration($field)
            .(isset($field['default']) ? ' DEFAULT '
            .$this->getDecimalValue($field['default']) : '')
            .(isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getClobValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     * 
     * @param resource $prepared_query query handle from prepare()
     * @param  $parameter 
     * @param  $clob 
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public 
     */
    function getClobValue($prepared_query, $parameter, $clob)
    {
        return('EMPTY_CLOB()');
    }

    // }}}
    // {{{ freeClobValue()

    /**
     * free a character large object
     * 
     * @param resource $prepared_query query handle from prepare()
     * @param string $blob 
     * @param string $value 
     * @access public 
     */
    function freeClobValue($prepared_query, $clob, &$value)
    {
        unset($value);
    }

    // }}}
    // {{{ getBlobValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     * 
     * @param resource $prepared_query query handle from prepare()
     * @param  $parameter 
     * @param  $blob 
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
     * @access public 
     */
    function getBlobValue($prepared_query, $parameter, $blob)
    {
        return('EMPTY_BLOB()');
    }

    // }}}
    // {{{ freeBlobValue()

    /**
     * free a binary large object
     * 
     * @param resource $prepared_query query handle from prepare()
     * @param string $blob 
     * @param string $value 
     * @access public 
     */
    function freeBlobValue($prepared_query, $blob, &$value)
    {
        unset($value);
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
        return(($value === NULL) ? 'NULL' : "TO_DATE('$value','YYYY-MM-DD')");
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
        return(($value === NULL) ? 'NULL' : "TO_DATE('$value','YYYY-MM-DD HH24:MI:SS')");
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
        return(($value === NULL) ? 'NULL' : "TO_DATE('0001-01-01 $value','YYYY-MM-DD HH24:MI:SS')");
    }

    // }}}
    // {{{ getFloatValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     * 
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
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
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *        a DBMS specific format.
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
     * @param string $seq_name name of the sequence
     * @param boolean $ondemand when TRUE the seqence is
     *                           automatic created, if it
     *                           not exists
     * @return mixed MDB_Error or id
     * @access public 
     */
    function nextId($seq_name, $ondemand = TRUE)
    {
        if (MDB::isError($connect = $this->connect())) {
            return($connect);
        }
        $sequence_name = $this->getSequenceName($seq_name);
        $this->expectError(MDB_ERROR_NOSUCHTABLE);
        $result = $this->_doQuery("SELECT $sequence_name.nextval FROM DUAL");
        $this->popExpect();
        if ($ondemand && MDB::isError($result)
            && $result->getCode() == MDB_ERROR_NOSUCHTABLE)
        {
            $result = $this->createSequence($seq_name, 1);
            if (MDB::isError($result)) {
                return $result;
            }
            return $this->nextId($seq_name, false);
        }
        return($this->fetchOne($result));
    }

    // }}}
    // {{{ currId()

    /**
     * returns the current id of a sequence
     *
     * @param string $seq_name name of the sequence
     * @return mixed MDB_Error or id
     * @access public
     */
    function currId($seq_name)
    {
        $sequence_name = $this->getSequenceName($seq_name);
        $result = $this->_doQuery("SELECT $sequence_name.currval FROM DUAL");
        if (MDB::isError($result)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'currId: unable to select from ' . $seq_name) );
        }
        $result = $this->fetchOne($result);
        if (!is_numeric($result)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'currId: could not find value in sequence table'));
        }
        return($result);
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
    function fetchInto($result, $fetchmode = MDB_FETCHMODE_DEFAULT, $rownum = NULL)
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
            $rownum = $this->highest_fetched_row[$result_value] + 1;
        }
        if (!isset($this->results[$result_value][$rownum])) {
            if (isset($this->limits[$result_value])) {
                if ($rownum >= $this->limits[$result_value][1]) {
                    return(NULL);
                }
                if (MDB::isError($this->_skipLimitOffset($result))) {
                    return(NULL);
                }
            }
            if (isset($this->row_buffer[$result_value])) {
                $this->current_row[$result_value]++;
                $this->results[$result_value][$this->current_row[$result_value]] = $this->row_buffer[$result_value];
                unset($this->row_buffer[$result_value]);
            }
            if (!isset($this->results[$result_value][$this->current_row[$result_value]])
                || $this->results[$result_value][$this->current_row[$result_value]] !== false
            ) {
                while($this->current_row[$result_value] < $rownum
                    && @OCIFetchInto($result, $buffer, OCI_RETURN_NULLS)
                ) {
                    $this->current_row[$result_value]++;
                    $this->results[$result_value][$this->current_row[$result_value]] = $buffer;
                }
                if ($this->current_row[$result_value] > $rownum) {
                    $this->current_row[$result_value]++;
                    $this->results[$result_value][$this->current_row[$result_value]] = false;
                }
            }
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value],
                    $this->current_row[$result_value]);
        }
        if (isset($this->results[$result_value][$rownum])) {
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value], $rownum);
            $row = $this->results[$result_value][$rownum];
        } else {
            return(NULL);
        }
        if (!$row) {
            if($this->options['autofree']) {
                $this->freeResult($result);
            }
            return(NULL);
        }
        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $column_names = $this->getColumnNames($result);
            foreach($column_names as $name => $i) {
                $column_names[$name] = $row[$i];
            }
            $row = $column_names;
        }
        if (isset($this->result_types[$result_value])) {
            $row = $this->convertResultRow($result, $row);
        }
        return($row);
    }

    // }}}
    // {{{ nextResult()

    /**
     * Move the internal oracle result pointer to the next available result
     * Currently not supported
     * 
     * @param $result a oracle valid result resource
     * @return TRUE if a result is available otherwise return FALSE
     * @access public 
     */
    function nextResult($result)
    {
        return(FALSE);
    }

    // }}}
    // {{{ tableInfo()

    /**
     * returns meta data about the result set
     * 
     * @param resource $result result identifier
     * @param mixed $mode depends on implementation
     * @return array an nested array, or a MDB error
     * @access public 
     */
    function tableInfo($result, $mode = NULL)
    {
        $count = 0;
        $res = array();
        /**
         * depending on $mode, metadata returns the following values:
         * 
         * - mode is FALSE (default):
         * $res[]:
         *    [0]['table']       table name
         *    [0]['name']        field name
         *    [0]['type']        field type
         *    [0]['len']         field length
         *    [0]['nullable']    field can be NULL (boolean)
         *    [0]['format']      field precision if NUMBER
         *    [0]['default']     field default value
         * 
         * - mode is MDB_TABLEINFO_ORDER
         * $res[]:
         *    ['num_fields']     number of fields
         *    [0]['table']       table name
         *    [0]['name']        field name
         *    [0]['type']        field type
         *    [0]['len']         field length
         *    [0]['nullable']    field can be NULL (boolean)
         *    [0]['format']      field precision if NUMBER
         *    [0]['default']     field default value
         *    ['order'][field name] index of field named 'field name'
         *    The last one is used, if you have a field name, but no index.
         *    Test:  if (isset($result['order']['myfield'])) { ...
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
         *       you can combine DB_TABLEINFO_ORDER and
         *       MDB_TABLEINFO_ORDERTABLE with MDB_TABLEINFO_ORDER |
         *       MDB_TABLEINFO_ORDERTABLE * or with MDB_TABLEINFO_FULL
         */ 
        // if $result is a string, we collect info for a table only
        if (is_string($result)) {
            if (MDB::isError($connect = $this->connect())) {
                return($connect);
            }
            $result = strtoupper($result);
            $q_fields = "select column_name, data_type, data_length, data_precision,
                     nullable, data_default from user_tab_columns
                     where table_name='$result' order by column_id";
            if (!$stmt = @OCIParse($this->connection, $q_fields)) {
                return($this->oci8RaiseError());
            }
            if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                return($this->oci8RaiseError($stmt));
            } while (@OCIFetch($stmt)) {
                $res[$count]['table'] = strtolower($result);
                $res[$count]['name'] = strtolower(@OCIResult($stmt, 1));
                $res[$count]['type'] = strtolower(@OCIResult($stmt, 2));
                $res[$count]['len'] = @OCIResult($stmt, 3);
                $res[$count]['format'] = @OCIResult($stmt, 4);
                $res[$count]['nullable'] = (@OCIResult($stmt, 5) == 'Y') ? TRUE : FALSE;
                $res[$count]['default'] = @OCIResult($stmt, 6);
                if ($mode & MDB_TABLEINFO_ORDER) {
                    $res['order'][$res[$count]['name']] = $count;
                }
                if ($mode & MDB_TABLEINFO_ORDERTABLE) {
                    $res['ordertable'][$res[$count]['table']][$res[$count]['name']] = $count;
                }
                $count++;
            }
            if ($mode) {
                $res['num_fields'] = $count;
            }
            @OCIFreeStatement($stmt);
        } else { // else we want information about a resultset
            #if ($result === $this->last_stmt) {
                $count = @OCINumCols($result);
                for ($i = 0; $i < $count; $i++) {
                    $res[$i]['name'] = strtolower(@OCIColumnName($result, $i + 1));
                    $res[$i]['type'] = strtolower(@OCIColumnType($result, $i + 1));
                    $res[$i]['len'] = @OCIColumnSize($result, $i + 1);

                    $q_fields = "SELECT table_name, data_precision, nullable, data_default from user_tab_columns WHERE column_name='".$res[$i]['name']."'";
                    if (!$stmt = @OCIParse($this->connection, $q_fields)) {
                        return($this->oci8RaiseError());
                    }
                    if (!@OCIExecute($stmt, OCI_DEFAULT)) {
                        return($this->oci8RaiseError($stmt));
                    }
                    @OCIFetch($stmt);
                    $res[$i]['table'] = strtolower(@OCIResult($stmt, 1));
                    $res[$i]['format'] = @OCIResult($stmt, 2);
                    $res[$i]['nullable'] = (@OCIResult($stmt, 3) == 'Y') ? TRUE : FALSE;
                    $res[$i]['default'] = @OCIResult($stmt, 4);
                    @OCIFreeStatement($stmt);

                    if ($mode & MDB_TABLEINFO_ORDER) {
                        $res['order'][$res[$i]['name']] = $i;
                    }
                    if ($mode & MDB_TABLEINFO_ORDERTABLE) {
                        $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
                    }
                }
                if ($mode) {
                    $res['num_fields'] = $count;
                }
            #} else {
            #    return($this->raiseError(MDB_ERROR_NOT_CAPABLE));
            #}
        }
        return($res);
    }
}
?>