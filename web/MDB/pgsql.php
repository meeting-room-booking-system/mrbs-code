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

require_once('MDB/Common.php');

/**
 * MDB PostGreSQL driver
 *
 * Notes:
 * - Creation of new databases is based on database template1.
 *
 * - The decimal type fields are emulated with integer fields.
 *
 * - PostgreSQL stores large objects in files managed by the server.
 *   Tables with large object fields only store identifiers pointing to those
 *   files. If you delete or update rows of those tables, the actual large
 *   object files are not deleted from the server file system. Therefore you may
 *   need to reclaim large object field space by deleting those files manually.
 *
 * @package MDB
 * @category Database
 * @author  Paul Cooper <pgc@ucecom.com>
 */

class MDB_pgsql extends MDB_Common
{
    var $connection = 0;
    var $connected_host;
    var $connected_port;
    var $selected_database = '';
    var $opened_persistent = '';

    var $escape_quotes = "\\";
    var $decimal_factor = 1.0;

    var $highest_fetched_row = array();
    var $columns = array();

    // }}}
    // {{{ constructor

    /**
    * Constructor
    */
    function MDB_pgsql()
    {
        $this->MDB_Common();
        $this->phptype = 'pgsql';
        $this->dbsyntax = 'pgsql';

        $this->supported['Sequences'] = 1;
        $this->supported['Indexes'] = 1;
        $this->supported['SummaryFunctions'] = 1;
        $this->supported['OrderByText'] = 1;
        $this->supported['Transactions'] = 1;
        $this->supported['CurrId'] = 1;
        $this->supported['SelectRowRanges'] = 1;
        $this->supported['LOBs'] = 1;
        $this->supported['Replace'] = 1;
        $this->supported['SubSelects'] = 1;

        $this->decimal_factor = pow(10.0, $this->decimal_places);
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
                '/([Tt]able does not exist\.|[Rr]elation [\"\'].*[\"\'] does not exist|[Ss]equence does not exist|[Cc]lass ".+" not found)$/' => MDB_ERROR_NOSUCHTABLE,
                '/[Tt]able [\"\'].*[\"\'] does not exist/' => MDB_ERROR_NOSUCHTABLE,
                '/[Rr]elation [\"\'].*[\"\'] already exists|[Cc]annot insert a duplicate key into (a )?unique index.*/' => MDB_ERROR_ALREADY_EXISTS,
                '/divide by zero$/'                     => MDB_ERROR_DIVZERO,
                '/pg_atoi: error in .*: can\'t parse /' => MDB_ERROR_INVALID_NUMBER,
                '/ttribute [\"\'].*[\"\'] not found$|[Rr]elation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => MDB_ERROR_NOSUCHFIELD,
                '/parser: parse error at or near \"/'   => MDB_ERROR_SYNTAX,
                '/syntax error at/'                     => MDB_ERROR_SYNTAX,
                '/violates not-null constraint/'        => MDB_ERROR_CONSTRAINT_NOT_NULL,
                '/violates [\w ]+ constraint/'          => MDB_ERROR_CONSTRAINT,
                '/referential integrity violation/'     => MDB_ERROR_CONSTRAINT,
                '/deadlock detected/'                   => MDB_ERROR_DEADLOCK
            );
        }
        foreach ($error_regexps as $regexp => $code) {
            if (preg_match($regexp, $errormsg)) {
                return($code);
            }
        }
        // Fall back to MDB_ERROR if there was no mapping.
        return(MDB_ERROR);
    }
    // }}}
    // {{{ pgsqlRaiseError()

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

    function pgsqlRaiseError($errno = NULL)
    {
        $native = $this->errorNative();
        if ($errno === NULL) {
            $err = $this->errorCode($native);
        } else {
            $err = $errno;
        }
        return($this->raiseError($err, NULL, NULL, NULL, $native));
    }

    // }}}
    // {{{ errorNative()

    /**
     * Get the native error code of the last error (if any) that
     * occured on the current connection.
     *
     * @access public
     *
     * @return int native pgsql error code
     */
    function errorNative()
    {
        return @pg_errormessage($this->connection);
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
        if ($this->auto_commit == $auto_commit) {
            return(MDB_OK);
        }
        if ($this->connection) {
            if (MDB::isError($result = $this->_doQuery($auto_commit ? 'END' : 'BEGIN')))
                return($result);
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
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function commit()
    {
         $this->debug('Commit Transaction');
        if ($this->auto_commit) {
            return($this->raiseError(MDB_ERROR, NULL, NULL, 'Commit: transaction changes are being auto commited'));
        }
        return($this->_doQuery('COMMIT') && $this->_doQuery('BEGIN'));
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
            return($this->raiseError(MDB_ERROR, NULL, NULL, 'Rollback: transactions can not be rolled back when changes are auto commited'));
        }
        return($this->_doQuery('ROLLBACK') && $this->_doQuery('BEGIN'));
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
        $function = ($persistent ? 'pg_pconnect' : 'pg_connect');
        if (!function_exists($function)) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'doConnect: PostgreSQL support is not available in this PHP configuration'));
        }
        $port = (isset($this->port) ? $this->port : '');
        if ($database_name == '') {
            $database_name = 'template1';
        }
        $connect_string = 'dbname='.$database_name;
        if ($this->host != '') {
            $connect_string .= ' host='.$this->host;
        }
        if ($port != '') {
            $connect_string .= ' port='.strval($port);
        }
        if ($this->user != '') {
            $connect_string .= ' user='.$this->user;
        }
        if ($this->password != '') {
            $connect_string .= ' password='.$this->password;
        }
        putenv('PGDATESTYLE=ISO');
        if (($connection = @$function($connect_string)) > 0) {
            return($connection);
        }
        if (isset($php_errormsg)) {
            $error_msg = $php_errormsg;
        } else {
            $error_msg = 'Could not connect to PostgreSQL server';
        }
        return($this->raiseError(MDB_ERROR_CONNECT_FAILED, NULL, NULL, 'doConnect: '.$error_msg));
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
        if($this->connection != 0) {
            if (!strcmp($this->connected_host, $this->host)
                && !strcmp($this->connected_port, $port)
                && !strcmp($this->selected_database, $this->database_name)
                && ($this->opened_persistent == $this->options['persistent']))
            {
                return(MDB_OK);
            }
            @pg_close($this->connection);
            $this->affected_rows = -1;
            $this->connection = 0;
        }

        if(!PEAR::loadExtension($this->phptype)) {
            return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND,
                NULL, NULL, 'extension '.$this->phptype.' is not compiled into PHP',
                'MDB_Error', TRUE));
        }

        if(function_exists('pg_cmdTuples')) {
            $connection = $this->_doConnect('template1', 0);
            if (!MDB::isError($connection)) {
                if (($result = @pg_exec($connection, 'BEGIN'))) {
                    $error_reporting = error_reporting(63);
                    @pg_cmdtuples($result);
                    if (!isset($php_errormsg) || strcmp($php_errormsg, 'This compilation does not support pg_cmdtuples()')) {
                        $this->supported['AffectedRows'] = 1;
                    }
                    error_reporting($error_reporting);
                } else {
                    $err = $this->raiseError(MDB_ERROR, NULL, NULL, 'Setup: '.@pg_errormessage($connection));
                }
                @pg_close($connection);
            } else {
                $err = $this->raiseError(MDB_ERROR, NULL, NULL, 'Setup: could not execute BEGIN');
            }
            if (isset($err) && MDB::isError($err)) {
                return($err);
            }
        }
        $connection = $this->_doConnect($this->database_name, $this->options['persistent']);
        if (MDB::isError($connection)) {
            return($connection);
        }
        $this->connection = $connection;

        if (!$this->auto_commit && MDB::isError($trans_result = $this->_doQuery('BEGIN'))) {
            pg_Close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;
            return($trans_result);
        }
        $this->connected_host = $this->host;
        $this->connected_port = $port;
        $this->selected_database = $this->database_name;
        $this->opened_persistent = $this->options['persistent'];
        return(MDB_OK);
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
            @pg_close($this->connection);
            $this->connection = 0;
            $this->affected_rows = -1;

            unset($GLOBALS['_MDB_databases'][$this->database]);
            return(MDB_OK);
        }
        return(MDB_ERROR);
    }

    // }}}
    // {{{ _doQuery()

    /**
     * Execute a query
     * @param string $query the SQL query
     * @return mixed result identifier if query executed, else MDB_error
     * @access private
     **/
    function _doQuery($query)
    {
        if (($result = @pg_Exec($this->connection, $query))) {
            $this->affected_rows = (isset($this->supported['AffectedRows']) ? @pg_cmdtuples($result) : -1);
        } else {
            $error = @pg_errormessage($this->connection);
            return($this->pgsqlRaiseError());
        }
        return($result);
    }

    // }}}
    // {{{ _standaloneQuery()

    /**
     * execute a query
     *
     * @param string $query
     * @return
     * @access private
     */
    function _standaloneQuery($query)
    {
        if (($connection = $this->_doConnect('template1', 0)) == 0) {
            return($this->raiseError(MDB_ERROR_CONNECT_FAILED, NULL, NULL, '_standaloneQuery: Cannot connect to template1'));
        }
        if (!($result = @pg_Exec($connection, $query))) {
            $this->raiseError(MDB_ERROR, NULL, NULL, '_standaloneQuery: ' . @pg_errormessage($connection));
        }
        pg_Close($connection);
        return($result);
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
        $this->debug("Query: $query");
        $ismanip = MDB::isManip($query);
        $this->last_query = $query;
        $first = $this->first_selected_row;
        $limit = $this->selected_row_limit;
        $this->first_selected_row = $this->selected_row_limit = 0;
        $connected = $this->connect();
        if (MDB::isError($connected)) {
            return($connected);
        }

        if (!$ismanip && $limit > 0) {
             if ($this->auto_commit && MDB::isError($this->_doQuery('BEGIN'))) {
                 return($this->raiseError(MDB_ERROR));
             }
             $result = $this->_doQuery('DECLARE select_cursor SCROLL CURSOR FOR '.$query);
             if (!MDB::isError($result)) {
                 if ($first > 0 && MDB::isError($result = $this->_doQuery("MOVE FORWARD $first FROM select_cursor"))) {
                     $this->freeResult($result);
                     return($result);
                 }
                 if (MDB::isError($result = $this->_doQuery("FETCH FORWARD $limit FROM select_cursor"))) {
                     $this->freeResult($result);
                     return($result);
                 }
             } else {
                 return($result);
             }
             if ($this->auto_commit && MDB::isError($result2 = $this->_doQuery('END'))) {
                 $this->freeResult($result);
                 return($result2);
             }
         } else {
            $result = $this->_doQuery($query);
            if (MDB::isError($result)) {
                return($result);
            }
        }
        if ($ismanip) {
            $this->affected_rows = @pg_cmdtuples($result);
            return(MDB_OK);
        } elseif ((preg_match('/^\s*\(?\s*SELECT\s+/si', $query)
                && !preg_match('/^\s*\(?\s*SELECT\s+INTO\s/si', $query)
            ) || preg_match('/^\s*EXPLAIN/si',$query )
        ) {
            /* PostgreSQL commands:
               ABORT, ALTER, BEGIN, CLOSE, CLUSTER, COMMIT, COPY,
               CREATE, DECLARE, DELETE, DROP TABLE, EXPLAIN, FETCH,
               GRANT, INSERT, LISTEN, LOAD, LOCK, MOVE, NOTIFY, RESET,
               REVOKE, ROLLBACK, SELECT, SELECT INTO, SET, SHOW,
               UNLISTEN, UPDATE, VACUUM
            */
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
        } else {
            $this->affected_rows = 0;
            return(MDB_OK);
        }
        return($this->raiseError(MDB_ERROR));
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @param resource $result  result identifier
     * @return mixed associative array variable
     *      that holds the names of columns. The indexes of the array are
     *      the column names mapped to lower case and the values are the
     *      respective numbers of the columns starting from 0. Some DBMS may
     *      not return any columns when the result set does not contain any
     *      rows.
     *     a MDB error on failure
     * @access public
     */
    function getColumnNames($result)
    {
        $result_value = intval($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL, 'Get Column Names: specified an nonexistant result set'));
        }
        if (!isset($this->columns[$result_value])) {
            $this->columns[$result_value] = array();
            $columns = @pg_numfields($result);
            for($column = 0; $column < $columns; $column++) {
                $field_name = @pg_fieldname($result, $column);
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
            return($this->raiseError(MDB_ERROR, NULL, NULL, 'numCols: specified an nonexistant result set'));
        }
        return(pg_numfields($result));
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
            return($this->RaiseError(MDB_ERROR, NULL, NULL, 'End of result attempted to check the end of an unknown result'));
        }
        return($this->highest_fetched_row[$result_value] >= $this->numRows($result) - 1);
    }

    // }}}
    // {{{ fetch()

    /**
     * fetch value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function fetch($result, $row, $field)
    {
        $result_value = intval($result);
        $this->highest_fetched_row[$result_value] = max($this->highest_fetched_row[$result_value], $row);
        $res = @pg_result($result, $row, $field);
        if ($res === FALSE && $res != NULL) {
            return($this->pgsqlRaiseError());
        }
        return($res);
    }

    // }}}
    // {{{ _retrieveLob()

    /**
     * fetch a float value from a result set
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access private
     */
    function _retrieveLob($lob)
    {
        if (!isset($this->lobs[$lob])) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'Retrieve LOB: did not specified a valid lob'));
        }
        if (!isset($this->lobs[$lob]['Value'])) {
            if ($this->auto_commit) {
                if (!@pg_exec($this->connection, 'BEGIN')) {
                    return($this->raiseError(MDB_ERROR,  NULL, NULL,
                        'Retrieve LOB: ' . @pg_errormessage($this->connection)));
                }
                $this->lobs[$lob]['InTransaction'] = 1;
            }
            $this->lobs[$lob]['Value'] = $this->fetch($this->lobs[$lob]['Result'], $this->lobs[$lob]['Row'], $this->lobs[$lob]['Field']);
            if (!($this->lobs[$lob]['Handle'] = @pg_loopen($this->connection, $this->lobs[$lob]['Value'], 'r'))) {
                if (isset($this->lobs[$lob]['InTransaction'])) {
                    @pg_exec($this->connection, 'END');
                    unset($this->lobs[$lob]['InTransaction']);
                }
                unset($this->lobs[$lob]['Value']);
                return($this->raiseError(MDB_ERROR, NULL, NULL,
                    'Retrieve LOB: ' . @pg_errormessage($this->connection)));
            }
        }
        return(MDB_OK);
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
        $lobresult = $this->_retrieveLob($lob);
        if (MDB::isError($lobresult)) {
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
        $lobresult = $this->_retrieveLob($lob);
        if (MDB::isError($lobresult)) {
            return($lobresult);
        }
        $data = @pg_loread($this->lobs[$lob]['Handle'], $length);
        if (gettype($data) != 'string') {
            $this->raiseError(MDB_ERROR, NULL, NULL,
                'Read Result LOB: ' . @pg_errormessage($this->connection));
        }
        if (($length = strlen($data)) == 0) {
            $this->lobs[$lob]['EndOfLOB'] = 1;
        }
        return($length);
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
                @pg_loclose($this->lobs[$lob]['Handle']);
                if (isset($this->lobs[$lob]['InTransaction'])) {
                    @pg_exec($this->connection, 'END');
                }
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
        return($this->fetchLob($result, $row, $field));
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
        return($this->fetchLob($result, $row, $field));
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
                return(sprintf('%.'.$this->decimal_places.'f',doubleval($value)/$this->decimal_factor));
            case MDB_TYPE_TIMESTAMP:
                return substr($value, 0, strlen('YYYY-MM-DD HH:MM:SS'));
            default:
                return($this->_baseConvertResult($value, $type));
        }
    }

    // }}}
    // {{{ resultIsNull()

    /**
     * Determine whether the value of a query result located in given row and
     *   field is a NULL.
     *
     * @param resource    $result result identifier
     * @param int    $row    number of the row where the data can be found
     * @param int    $field    field number where the data can be found
     * @return mixed TRUE or FALSE on success, a MDB error on failure
     * @access public
     */
    function resultIsNull($result, $row, $field)
    {
        $result_value = intval($result);
        $this->highest_fetched_row[$result_value] = max($this->highest_fetched_row[$result_value], $row);
        return(@pg_fieldisnull($result, $row, $field));
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
        return(@pg_numrows($result));
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
        return(@pg_freeresult($result));
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
        return((isset($field['length']) ? "$name VARCHAR (" . $field['length'] . ')' : "$name TEXT") . (isset($field['default']) ? " DEFAULT '" . $field['default'] . "'" : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return("$name OID".(isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return("$name OID".(isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return($name.' DATE'.(isset($field['default']) ? ' DEFAULT \''.$field['default'] . "'" : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return($name.' TIME'.(isset($field['default']) ? ' DEFAULT \''.$field['default'].'\'' : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return("$name FLOAT8 ".(isset($field['default']) ? ' DEFAULT '.$this->getFloatValue($field['default']) : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return("$name INT8 ".(isset($field['default']) ? ' DEFAULT '.$this->getDecimalValue($field['default']) : '').(isset($field['notnull']) ? ' NOT NULL' : ''));
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
        $connect = $this->connect();
        if (MDB::isError($connect)) {
            return($connect);
        }
        if ($this->auto_commit && !@pg_exec($this->connection, 'BEGIN')) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                '_getLobValue: error starting transaction'));
        }
        if (($lo = @pg_locreate($this->connection))) {
            if (($handle = @pg_loopen($this->connection, $lo, 'w'))) {
                while (!$this->endOfLob($lob)) {
                    $result = $this->readLob($lob, $data, $this->options['lob_buffer_length']);
                    if (MDB::isError($result)) {
                        break;
                    }
                    if (!@pg_lowrite($handle, $data)) {
                        $result = $this->raiseError(MDB_ERROR, NULL, NULL,
                            'Get LOB field value: ' . @pg_errormessage($this->connection));
                        break;
                    }
                }
                @pg_loclose($handle);
                if (!MDB::isError($result)) {
                    $value = strval($lo);
                }
            } else {
                $result = $this->raiseError(MDB_ERROR, NULL, NULL,
                    'Get LOB field value: ' . @pg_errormessage($this->connection));
            }
            if (MDB::isError($result)) {
                $result = @pg_lounlink($this->connection, $lo);
            }
        } else {
            $result = $this->raiseError(MDB_ERROR, NULL, NULL, 'Get LOB field value: ' . pg_ErrorMessage($this->connection));
        }
        if ($this->auto_commit) {
            @pg_exec($this->connection, 'END');
        }
        if (MDB::isError($result)) {
            return($result);
        }
        return($value);
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
        return($this->_getLobValue($prepared_query, $parameter, $clob));
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
        return($this->_getLobValue($prepared_query, $parameter, $blob));
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
        return(($value === NULL) ? 'NULL' : $value);
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
        return(($value === NULL) ? 'NULL' : strval(round($value*$this->decimal_factor)));
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
    function nextId($seq_name, $ondemand = TRUE)
    {
        $seqname = $this->getSequenceName($seq_name);
        $repeat = 0;
        do {
            $this->pushErrorHandling(PEAR_ERROR_RETURN);
            $result = $this->query("SELECT NEXTVAL('$seqname')");
            $this->popErrorHandling();
            if ($ondemand && MDB::isError($result) && $result->getCode() == MDB_ERROR_NOSUCHTABLE) {
                $repeat = 1;
                $result = $this->createSequence($seq_name);
                if (MDB::isError($result)) {
                    return($this->pgsqlRaiseError($result));
                }
            } else {
                $repeat = 0;
            }
        } while ($repeat);
        if (MDB::isError($result)) {
            return($this->raiseError($result));
        }
        return($this->fetchOne($result));
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
        $seqname = $this->getSequenceName($seq_name);
        if (MDB::isError($result = $this->queryOne("SELECT last_value FROM $seqname"))) {
            return($this->raiseError(MDB_ERROR, NULL, NULL, 'currId: Unable to select from ' . $seqname) );
        }
        if (!is_numeric($result)) {
            return($this->raiseError(MDB_ERROR, NULL, NULL, 'currId: could not find value in sequence table'));
        }
        return($result);
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
        if ($fetchmode == MDB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }

        if (is_null($rownum)) {
            ++$this->highest_fetched_row[$result_value];
        } else {
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value], $rownum);
        }

        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $row = @pg_fetch_assoc($result, $rownum, PGSQL_ASSOC);
            if (is_array($row) && $this->options['optimize'] == 'portability') {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        } else {
            $row = @pg_fetch_row($result, $rownum);
        }

        if (!$row) {
            if ($this->options['autofree']) {
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
     * Move the internal pgsql result pointer to the next available result
     *
     * @param a valid fbsql result resource
     * @return true if a result is available otherwise return false
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
     * @param  mixed $resource PostgreSQL result identifier or table name
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
            $id = @pg_exec($this->connection, "SELECT * FROM $result LIMIT 0");
            if (empty($id)) {
                return($this->pgsqlRaiseError());
            }
        } else { // else we want information about a resultset
            $id = $result;
            if (empty($id)) {
                return($this->pgsqlRaiseError());
            }
        }

        $count = @pg_numfields($id);

        // made this IF due to performance (one if is faster than $count if's)
        if (empty($mode)) {
            for ($i = 0; $i < $count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name'] = @pg_fieldname ($id, $i);
                $res[$i]['type'] = @pg_fieldtype ($id, $i);
                $res[$i]['len'] = @pg_fieldsize ($id, $i);
                $res[$i]['flags'] = (is_string($result)) ? $this->_pgFieldflags($id, $i, $result) : '';
            }
        } else { // full
            $res['num_fields'] = $count;

            for ($i = 0; $i < $count; $i++) {
                $res[$i]['table'] = (is_string($result)) ? $result : '';
                $res[$i]['name'] = @pg_fieldname ($id, $i);
                $res[$i]['type'] = @pg_fieldtype ($id, $i);
                $res[$i]['len'] = @pg_fieldsize ($id, $i);
                $res[$i]['flags'] = (is_string($result)) ? $this->_pgFieldFlags($id, $i, $result) : '';
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
            @pg_freeresult($id);
        }
        return($res);
    }

    // }}}
    // {{{ _pgFieldFlags()

    /**
     * Flags of a Field
     *
     * @param int $resource PostgreSQL result identifier
     * @param int $num_field the field number
     * @return string The flags of the field ('not_null', 'default_xx', 'primary_key',
     *                 'unique' and 'multiple_key' are supported)
     * @access private
     **/
    function _pgFieldFlags($resource, $num_field, $table_name)
    {
        $field_name = @pg_fieldname($resource, $num_field);

        $result = pg_exec($this->connection, "SELECT f.attnotnull, f.atthasdef
            FROM pg_attribute f, pg_class tab, pg_type typ
            WHERE tab.relname = typ.typname
            AND typ.typrelid = f.attrelid
            AND f.attname = '$field_name'
            AND tab.relname = '$table_name'");
        if (@pg_numrows($result) > 0) {
            $row = @pg_fetch_row($result, 0);
            $flags = ($row[0] == 't') ? 'not_null ' : '';

            if ($row[1] == 't') {
                $result = @pg_exec($this->connection, "SELECT a.adsrc
                    FROM pg_attribute f, pg_class tab, pg_type typ, pg_attrdef a
                    WHERE tab.relname = typ.typname AND typ.typrelid = f.attrelid
                    AND f.attrelid = a.adrelid AND f.attname = '$field_name'
                    AND tab.relname = '$table_name'");
                $row = @pg_fetch_row($result, 0);
                $num = str_replace('\'', '', $row[0]);

                $flags .= "default_$num ";
            }
        }
        $result = @pg_exec($this->connection, "SELECT i.indisunique, i.indisprimary, i.indkey
            FROM pg_attribute f, pg_class tab, pg_type typ, pg_index i
            WHERE tab.relname = typ.typname
            AND typ.typrelid = f.attrelid
            AND f.attrelid = i.indrelid
            AND f.attname = '$field_name'
            AND tab.relname = '$table_name'");
        $count = @pg_numrows($result);

        for ($i = 0; $i < $count ; $i++) {
            $row = @pg_fetch_row($result, $i);
            $keys = explode(' ', $row[2]);

            if (in_array($num_field + 1, $keys)) {
                $flags .= ($row[0] == 't') ? 'unique ' : '';
                $flags .= ($row[1] == 't') ? 'primary ' : '';
                if (count($keys) > 1)
                    $flags .= 'multiple_key ';
            }
        }

        return trim($flags);
    }
}

?>