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

/**
 * @package MDB
 * @author Lukas Smith <smith@backendmedia.com>
 */

// }}}
// {{{ MDB_defaultDebugOutput()

/**
 * default debug output handler
 *
 * @param object $db reference to an MDB database object
 * @param string $message message that should be appended to the debug
 *       variable
 * @return string the corresponding error message, of FALSE
 * if the error code was unknown
 * @access public
 */
function MDB_defaultDebugOutput(&$db, $message)
{
    $db->debug_output .= $db->database . " $message" . $db->getOption('log_line_break');
}

/**
 * MDB_Common: Base class that is extended by each MDB driver
 *
 * @package MDB
 * @category Database
 * @author Lukas Smith <smith@backendmedia.com>
 */
class MDB_Common extends PEAR
{
    // {{{ properties
    /**
    * index of the MDB object withing the global $_MDB_databases array
    * @var integer
    * @access private
    */
    var $database = 0;

    /**
    * @var string
    * @access private
    */
    var $host = '';

    /**
    * @var string
    * @access private
    */
    var $port = '';

    /**
    * @var string
    * @access private
    */
    var $user = '';

    /**
    * @var string
    * @access private
    */
    var $password = '';

    /**
    * @var string
    * @access private
    */
    var $database_name = '';

    /**
    * @var array
    * @access private
    */
    var $supported = array();

    /**
    * $options["persistent"] -> boolean persistent connection true|false?
    * $options["debug"] -> integer numeric debug level
    * $options["autofree"] -> boolean
    * $options["lob_buffer_length"] -> integer LOB buffer length
    * $options["log_line_break"] -> string line-break format
    * $options["seqname_format"] -> string pattern for sequence name
    * $options["includelob"] -> boolean
    * $options["includemanager"] -> boolean
    * $options["UseTransactions"] -> boolean
    * $options["optimize"] -> string 'performance' or 'portability'
    * @var array
    * @access private
    */
    var $options = array(
            'persistent' => FALSE,
            'debug' => FALSE,
            'autofree' => FALSE,
            'lob_buffer_length' => 8192,
            'log_line_break' => "\n",
            'seqname_format' => '%s_seq',
            'includelob' => FALSE,
            'includemanager' => FALSE,
            'UseTransactions' => FALSE,
            'optimize' => 'performance',
        );

    /**
    * @var string
    * @access private
    */
    var $escape_quotes = '';

    /**
    * @var integer
    * @access private
    */
    var $decimal_places = 2;

    /**
    * @var string
    * @access private
    */
    var $manager_included_constant = '';

    /**
    * @var string
    * @access private
    */
    var $manager_include = '';

    /**
    * @var string
    * @access private
    */
    var $manager_class_name = '';

    /**
    * @var object
    * @access private
    */
    var $manager;

    /**
    * @var array
    * @access private
    */
    var $warnings = array();

    /**
    * @var string
    * @access private
    */
    var $debug = '';

    /**
    * @var string
    * @access private
    */
    var $debug_output = '';

    /**
    * @var boolean
    * @access private
    */
    var $pass_debug_handle = FALSE;

    /**
    * @var boolean
    * @access private
    */
    var $auto_commit = TRUE;

    /**
    * @var boolean
    * @access private
    */
    var $in_transaction = FALSE;

    /**
    * @var integer
    * @access private
    */
    var $first_selected_row = 0;

    /**
    * @var integer
    * @access private
    */
    var $selected_row_limit = 0;

    /**
    * DB type (mysql, oci8, odbc etc.)
    * @var string
    * @access private
    */
    var $type;

    /**
    * @var array
    * @access private
    */
    var $prepared_queries = array();

    /**
    * @var array
    * @access private
    */
    var $result_types;

    /**
    * @var string
    * @access private
    */
    var $last_query = '';

    /**
    * @var integer
    * @access private
    */
    var $fetchmode = MDB_FETCHMODE_ORDERED;

    /**
    * @var integer
    * @access private
    */
    var $affected_rows = -1;

    /**
    * @var array
    * @access private
    */
    var $lobs = array();

    /**
    * @var array
    * @access private
    */
    var $clobs = array();

    /**
    * @var array
    * @access private
    */
    var $blobs = array();

    // }}}
    // {{{ constructor

    /**
     * Constructor
     */
    function MDB_Common()
    {
        $database = count($GLOBALS['_MDB_databases']) + 1;
        $GLOBALS['_MDB_databases'][$database] = &$this;
        $this->database = $database;

        $this->PEAR('MDB_Error');
        $this->supported = array();
        $this->errorcode_map = array();
        $this->fetchmode = MDB_FETCHMODE_ORDERED;
    }

    // }}}
    // {{{ _toString()

    /**
     * String conversation
     *
     * @return string
     * @access private
     */
    function _toString()
    {
        $info = get_class($this);
        $info .= ': (phptype = ' . $this->phptype . ', dbsyntax = ' . $this->dbsyntax . ')';
        if ($this->connection) {
            $info .= ' [connected]';
        }
        return($info);
    }

    // }}}
    // {{{ errorCode()

    /**
     * Map native error codes to MDB's portable ones.  Requires that
     * the DB implementation's constructor fills in the $errorcode_map
     * property.
     *
     * @param mixed $nativecode the native error code, as returned by the
     *      backend database extension (string or integer)
     * @return int a portable MDB error code, or FALSE if this MDB
     *      implementation has no mapping for the given error code.
     * @access public
     */
    function errorCode($nativecode)
    {
        if (isset($this->errorcode_map[$nativecode])) {
            return($this->errorcode_map[$nativecode]);
        }
        // Fall back to MDB_ERROR if there was no mapping.
        return(MDB_ERROR);
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Map a MDB error code to a textual message.  This is actually
     * just a wrapper for MDB::errorMessage().
     *
     * @param integer $dbcode the MDB error code
     * @return string the corresponding error message, of FALSE
     *      if the error code was unknown
     * @access public
     */
    function errorMessage($dbcode)
    {
        return(MDB::errorMessage($this->errorcode_map[$dbcode]));
    }

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
    function &raiseError($code = MDB_ERROR, $mode = NULL, $options = NULL,
        $userinfo = NULL, $nativecode = NULL)
    {
        // The error is yet a MDB error object
        if (is_object($code)) {
            // because we the static PEAR::raiseError, our global
            // handler should be used if it is set
            if ($mode === null && !empty($this->_default_error_mode)) {
                $mode    = $this->_default_error_mode;
                $options = $this->_default_error_options;
            }
            $err = PEAR::raiseError($code, NULL, $mode, $options, NULL, NULL, TRUE);
            return($err);
        }

        if ($userinfo === NULL) {
            $userinfo = $this->last_query;
        }

        if ($nativecode) {
            $userinfo .= ' [nativecode=' . trim($nativecode) . ']';
        }

        $err = PEAR::raiseError(NULL, $code, $mode, $options, $userinfo, 'MDB_Error', TRUE);
        return($err);
    }

    // }}}
    // {{{ errorNative()

    /**
     * returns an errormessage, provides by the database
     *
     * @return mixed MDB_Error or message
     * @access public
     */
    function errorNative()
    {
        return($this->raiseError(MDB_ERROR_NOT_CAPABLE));
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
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;
            return MDB_OK;
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
        if (isset($this->options[$option])) {
            return($this->options[$option]);
        }
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, "unknown option $option"));
    }

    // }}}
    // {{{ captureDebugOutput()

    /**
     * set a debug handler
     *
     * @param string $capture name of the function that should be used in
     *      debug()
     * @access public
     * @see debug()
     */
    function captureDebugOutput($capture)
    {
        $this->pass_debug_handle = $capture;
        $this->debug = ($capture ? 'MDB_defaultDebugOutput' : '');
    }

    // }}}
    // {{{ debug()

    /**
     * set a debug message
     *
     * @param string $message Message with information for the user.
     * @access public
     */
    function debug($message)
    {
        if (strcmp($function = $this->debug, '')) {
            if ($this->pass_debug_handle) {
                $function($this, $message);
            } else {
                $function($message);
            }
        }
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
        return($this->debug_output);
    }

    // }}}
    // {{{ setError() (deprecated)

    /**
     * set an error (deprecated)
     *
     * @param string $scope Scope of the error message
     *     (usually the method tht caused the error)
     * @param string $message Message with information for the user.
     * @return boolean FALSE
     * @access private
     */
    function setError($scope, $message)
    {
        $this->last_error = $message;
        $this->debug($scope . ': ' . $message);
        if (($function = $this->error_handler) != '') {
            $error = array(
                'Scope' => $scope,
                'Message' => $message
            );
            $function($this, $error);
        }
        return(0);
    }

    // }}}
    // {{{ setErrorHandler() (deprecated)

    /**
     * Specify a function that is called when an error occurs.
     *
     * @param string $function Name of the function that will be called on
     *      error. If an empty string is specified, no handler function is
     *      called on error. The error handler function receives two arguments.
     *      The first argument a reference to the driver class object that
     *      triggered the error.
     *
     *      The second argument is a reference to an associative array that
     *      provides details about the error that occured. These details provide
     *      more information than it is returned by the MetabaseError function.
     *
     *      These are the currently supported error detail entries:
     *
     *      Scope
     *       String that indicates the scope of the driver object class
     *       within which the error occured.
     *
     *      Message
     *       Error message as is returned by the MetabaseError function.
     * @return string name of last function
     * @access public
     */
    function setErrorHandler($function)
    {
        $last_function = $this->error_handler;
        $this->error_handler = $function;
        return($last_function);
    }

    // }}}
    // {{{ error() (deprecated)

    /**
     * Retrieve the error message text associated with the last operation that
     * failed. Some functions may fail but they do not return the reason that
     * makes them to fail. This function is meant to retrieve a textual
     * description of the failure cause.
     *
     * @return string the error message text associated with the last failure.
     * @access public
     */
    function error()
    {
        return($this->last_error);
    }

    // }}}
    // {{{ _quote()

    /**
     * Quotes a string so it can be safely used in a query. It will quote
     * the text so it can safely be used within a query.
     *
     * @param string $text the input string to quote
     * @return string quoted string
     * @access private
     */
    function _quote($text)
    {
        if (strcmp($this->escape_quotes, "'")) {
            $text = str_replace($this->escape_quotes, $this->escape_quotes . $this->escape_quotes, $text);
        }
        return str_replace("'", $this->escape_quotes . "'", $text);
    }

    // }}}
    // {{{ quoteIdentifier()

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + double quote (<kbd>"</kbd>) -- due to Oracle
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mssql
     *   + mysql
     *   + mysqli
     *   + oci8
     *   + odbc(access)
     *   + odbc(db2)
     *   + pgsql
     *   + sqlite
     *   + sybase
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str  identifier name to be quoted
     *
     * @return string  quoted identifier string
     *
     * @access public
     */
    function quoteIdentifier($str)
    {
        return '"' . str_replace('"', '""', $str) . '"';
    }

    // }}}
    // {{{ _loadModule()

    /**
     * loads an module
     *
     * @param string $scope information about what method is being loaded,
     *      that is used for error messages
     * @param string $module name of the module that should be loaded
     *      (only used for error messages)
     * @param string $included_constant name of the constant that should be
     *      defined when the module has been loaded
     * @param string $include name of the script that includes the module
     * @access private
     */
    function _loadModule($scope, $module, $included_constant, $include)
    {
        if (strlen($included_constant) == 0 || !defined($included_constant)) {
            if($include) {
                $include = 'MDB/Modules/'.$include;
                if(MDB::isError($debug = $this->getOption('debug')) || $debug > 2) {
                    include_once($include);
                } else {
                    @include_once($include);
                }
            } else {
                return($this->raiseError(MDB_ERROR_LOADMODULE, NULL, NULL,
                    $scope . ': it was not specified an existing ' . $module . ' file (' . $include . ')'));
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ loadLob()

    /**
     * loads the LOB module
     *
     * @param string $scope information about what method is being loaded,
     *                       that is used for error messages
     * @access public
     */
    function loadLob($scope = '')
    {
        if (defined('MDB_LOB_INCLUDED')) {
            return(MDB_OK);
        }
        $result = $this->_loadModule($scope, 'LOB',
            'MDB_LOB_INCLUDED', 'LOB.php');
        if (MDB::isError($result)) {
            return($result);
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ loadManager()

    /**
     * loads the Manager module
     *
     * @param string $scope information about what method is being loaded,
     *                       that is used for error messages
     * @access public
     */
    function loadManager($scope = '')
    {
        if (isset($this->manager) && is_object($this->manager)) {
            return(MDB_OK);
        }
        $result = $this->_loadModule($scope, 'Manager',
            'MDB_MANAGER_'.strtoupper($this->phptype).'_INCLUDED',
            'Manager/'.$this->phptype.'.php');
        if (MDB::isError($result)) {
            return($result);
        }
        $class_name = 'MDB_Manager_'.$this->dbsyntax;
        if (!class_exists($class_name)) {
            return($this->raiseError(MDB_ERROR_LOADMODULE, NULL, NULL,
                'Unable to load extension'));
        }
        @$this->manager = new $class_name;
        return(MDB_OK);
    }

    // }}}
    // {{{ autoCommit()

    /**
     * Define whether database changes done on the database be automatically
     * committed. This function may also implicitly start or end a transaction.
     *
     * @param boolean $auto_commit flag that indicates whether the database
     *      changes should be committed right after executing every query
     *      statement. If this argument is 0 a transaction implicitly started.
     *      Otherwise, if a transaction is in progress it is ended by committing
     *      any database changes that were pending.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function autoCommit($auto_commit)
    {
        $this->debug('AutoCommit: ' . ($auto_commit ? 'On' : 'Off'));
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Auto-commit transactions: transactions are not supported'));
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
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Commit transaction: commiting transactions are not supported'));
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
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Rollback transaction: rolling back transactions are not supported'));
    }

    // }}}
    // {{{ disconnect()

    /**
     * Log out and disconnect from the database.
     *
     * @return mixed TRUE on success, FALSE if not connected and error
     *                object on error
     * @access public
     */
    function disconnect()
    {
        if ($this->in_transaction && !MDB::isError($this->rollback()) && !MDB::isError($this->autoCommit(TRUE))) {
            $this->in_transaction = FALSE;
        }
        return($this->_close());
    }

    // }}}
    // {{{ _close()

    /**
     * all the RDBMS specific things needed to close a DB connection
     *
     * @access private
     */
    function _close()
    {
        unset($GLOBALS['_MDB_databases'][$this->database]);
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
        $previous_database_name = $this->database_name;
        $this->database_name = $name;
        return($previous_database_name);
    }

    // }}}
    // {{{ setDSN()

    /**
     * set the DSN
     *
     * @param mixed     $dsninfo    DSN string or array
     * @return MDB_OK
     * @access public
     */
    function setDSN($dsn)
    {
        $dsninfo = MDB::parseDSN($dsn);
        if(isset($dsninfo['hostspec'])) {
            $this->host = $dsninfo['hostspec'];
        }
        if(isset($dsninfo['port'])) {
            $this->port = $dsninfo['port'];
        }
        if(isset($dsninfo['username'])) {
            $this->user = $dsninfo['username'];
        }
        if(isset($dsninfo['password'])) {
            $this->password = $dsninfo['password'];
        }
        if(isset($dsninfo['database'])) {
            $this->database_name = $dsninfo['database'];
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ getDSN()

    /**
     * return the DSN as a string
     *
     * @param string     $type    type to return
     * @return mixed DSN in the chosen type
     * @access public
     */
    function getDSN($type = 'string')
    {
        switch($type) {
            case 'array':
                $dsn = array(
                    'phptype' => $this->phptype,
                    'username' => $this->user,
                    'password' => $this->password,
                    'hostspec' => $this->host,
                    'database' => $this->database_name
                );
                break;
            default:
                $dsn = $this->phptype.'://'.$this->user.':'
                    .$this->password.'@'.$this->host
                    .($this->port ? (':'.$this->port) : '')
                    .'/'.$this->database_name;
                break;
        }
        return($dsn);
    }

    // }}}
    // {{{ createDatabase()

    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createDatabase($name)
    {
        $result = $this->loadManager('Create database');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->createDatabase($this, $name));
    }

    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropDatabase($name)
    {
        $result = $this->loadManager('Drop database');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->dropDatabase($this, $name));
    }

    // }}}
    // {{{ createTable()

    /**
     * create a new table
     *
     * @param string $name Name of the database that should be created
     * @param array $fields Associative array that contains the definition of
     *      each field of the new table. The indexes of the array entries are
     *      the names of the fields of the table an the array entry values are
     *      associative arrays like those that are meant to be passed with the
     *      field definitions to get[Type]Declaration() functions.
     *
     *      Example
     *        array(
     *            'id' => array(
     *                'type' => 'integer',
     *                'unsigned' => 1
     *                'notnull' => 1
     *                'default' => 0
     *            ),
     *            'name' => array(
     *                'type' => 'text',
     *                'length' => 12
     *            ),
     *            'password' => array(
     *                'type' => 'text',
     *                'length' => 12
     *            )
     *        );
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createTable($name, $fields)
    {
        $result = $this->loadManager('Create table');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->createTable($this, $name, $fields));
    }

    // }}}
    // {{{ dropTable()

    /**
     * drop an existing table
     *
     * @param string $name name of the table that should be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropTable($name)
    {
        $result = $this->loadManager('Drop table');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->dropTable($this, $name));
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param string $name name of the table that is intended to be changed.
     * @param array  $changes associative array that contains the details of
     *       each type of change that is intended to be performed. The types of
     *       changes that are currently supported are defined as follows:
     *
     *  name
     *      New name for the table.
     *
     *  AddedFields
     *      Associative array with the names of fields to be added as indexes of
     *      the array. The value of each entry of the array should be set to
     *      another associative array with the properties of the fields to be
     *      added. The properties of the fields should be the same as defined by
     *      the Metabase parser.
     *
     *      Additionally, there should be an entry named Declaration that is
     *      expected to contain the portion of the field declaration already in
     *       DBMS specific SQL code as it is used in the CREATE TABLE statement.
     *
     *  RemovedFields
     *      Associative array with the names of fields to be removed as indexes of
     *      the array. Currently the values assigned to each entry are ignored. An
     *      empty array should be used for future compatibility.
     *
     *  RenamedFields
     *      Associative array with the names of fields to be renamed as indexes of
     *      the array. The value of each entry of the array should be set to another
     *      associative array with the entry named name with the new field name and
     *      the entry named Declaration that is expected to contain the portion of
     *      the field declaration already in DBMS specific SQL code as it is used
     *      in the CREATE TABLE statement.
     *
     *  ChangedFields
     *      Associative array with the names of the fields to be changed as indexes
     *      of the array. Keep in mind that if it is intended to change either the
     *      name of a field and any other properties, the ChangedFields array
     *      entries should have the new names of the fields as array indexes.
     *
     *      The value of each entry of the array should be set to another
     *      associative array with the properties of the fields to that are meant
     *      to be changed as array entries. These entries should be assigned to the
     *      new values of the respective properties. The properties of the fields
     *      should be the* same as defined by the Metabase parser.
     *
     *      If the default property is meant to be added, removed or changed, there
     *      should also be an entry with index ChangedDefault assigned to 1.
     *      Similarly, if the notnull constraint is to be added or removed, there
     *      should also be an entry with index ChangedNotNull assigned to 1.
     *
     *      Additionally, there should be an entry named Declaration that is
     *      expected to contain the portion of the field changed declaration
     *      already in DBMS specific SQL code as it is used in the CREATE TABLE
     *      statement.
     *
     *  Example
     *      array(
     *          'name' => 'userlist',
     *          'AddedFields' => array(
     *              'quota' => array(
     *                  'type' => 'integer',
     *                  'unsigned' => 1,
     *                  'Declaration' => 'quota INT'
     *              )
     *          ),
     *          'RemovedFields' => array(
     *              'file_limit' => array(),
     *              'time_limit' => array()
     *          ),
     *          'ChangedFields' => array(
     *              'gender' => array(
     *                  'default' => 'M',
     *                  'ChangeDefault' => 1,
     *                  'Declaration' => "gender CHAR(1) DEFAULT 'M'"
     *              )
     *          ),
     *          'RenamedFields' => array(
     *              'sex' => array(
     *                  'name' => 'gender',
     *                  'Declaration' => "gender CHAR(1) DEFAULT 'M'"
     *              )
     *          )
     *      )
     *
     * @param boolean $check indicates whether the function should just check
     *       if the DBMS driver can perform the requested table alterations if
     *       the value is TRUE or actually perform them otherwise.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function alterTable($name, $changes, $check)
    {
        $result = $this->loadManager('Alter table');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->alterTable($this, $name, $changes, $check));
    }

    // }}}
    // {{{ listDatabases()

    /**
     * list all databases
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listDatabases()
    {
        $result = $this->loadManager('List databases');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listDatabases($this));
    }

    // }}}
    // {{{ listUsers()

    /**
     * list all users
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listUsers()
    {
        $result = $this->loadManager('List users');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listUsers($this));
    }

    // }}}
    // {{{ listViews()

    /**
     * list all viewes in the current database
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listViews()
    {
        $result = $this->loadManager('List views');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listViews($this));
    }

    // }}}
    // {{{ listFunctions()

    /**
     * list all functions in the current database
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listFunctions()
    {
        $result = $this->loadManager('List functions');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listFunctions($this));
    }

    // }}}
    // {{{ listTables()

    /**
     * list all tables in the current database
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTables()
    {
        $result = $this->loadManager('List tables');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listTables($this));
    }

    // }}}
    // {{{ listTableFields()

    /**
     * list all fields in a tables in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableFields($table)
    {
        $result = $this->loadManager('List table fields');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listTableFields($this, $table));
    }

    // }}}
    // {{{ getTableFieldDefinition()

    /**
     * get the stucture of a field into an array
     *
     * @param string $table name of table that should be used in method
     * @param string $fields name of field that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getTableFieldDefinition($table, $field)
    {
        $result = $this->loadManager('Get table field definition');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->getTableFieldDefinition($this, $table, $field));
    }

    // }}}
    // {{{ getFieldDeclaration()

    /**
     * get declaration of a field
     *
     * @param string $field_name name of the field to be created
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Boolean value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function getFieldDeclaration($field_name, $field)
    {
        $result = $this->loadManager('Get table field definition');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->getFieldDeclaration($this, $field_name, $field));
    }

    // }}}
    // {{{ getFieldDeclarationList()

    /**
     * get declaration of a number of field in bulk
     *
     * @param string $fields a multidimensional associative array.
     * The first dimension determines the field name, while the second
     * dimension is keyed with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Boolean value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     *
     *       default
     *           Boolean value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function getFieldDeclarationList($fields)
    {
        $result = $this->loadManager('Get table field list');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->getFieldDeclarationList($this, $fields));
    }

    // }}}
    // {{{ _isSequenceName()

    /**
     * list all tables in the current database
     *
     * @param string $sqn string that containts name of a potential sequence
     * @return mixed name of the sequence if $sqn is a name of a sequence, else FALSE
     * @access private
     */
    function _isSequenceName($sqn)
    {
        $result = $this->loadManager('is sequence name');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->_isSequenceName($this, $sqn));
    }

    // }}}
    // {{{ createIndex()

    /**
     * get the stucture of a field into an array
     *
     * @param string $table name of the table on which the index is to be
     *       created
     * @param string $name name of the index to be created
     * @param array $definition associative array that defines properties of
     *       the index to be created. Currently, only one property named FIELDS
     *       is supported. This property is also an associative with the names
     *       of the index fields as array indexes. Each entry of this array is
     *       set to another type of associative array that specifies properties
     *       of the index that are specific to each field.
     *
     *       Currently, only the sorting property is supported. It should be
     *       used to define the sorting direction of the index. It may be set
     *       to either ascending or descending. Not all DBMS support index
     *       sorting direction configuration. The DBMS drivers of those that do
     *       not support it ignore this property. Use the function support() to
     *       determine whether the DBMS driver can manage indexes.
     *
     *       Example
     *          array(
     *              'FIELDS' => array(
     *                  'user_name' => array(
     *                      'sorting' => 'ascending'
     *                  ),
     *                  'last_login' => array()
     *              )
     *          )
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createIndex($table, $name, $definition)
    {
        $result = $this->loadManager('Create index');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->createIndex($this, $table, $name, $definition));
    }

    // }}}
    // {{{ dropIndex()

    /**
     * drop existing index
     *
     * @param string $table name of table that should be used in method
     * @param string $name name of the index to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropIndex($table, $name)
    {
        $result = $this->loadManager('Drop index');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->dropIndex($this, $table , $name));
    }

    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $result = $this->loadManager('List table index');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listTableIndexes($this, $table));
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * get the stucture of an index into an array
     *
     * @param string $table name of table that should be used in method
     * @param string $index name of index that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getTableIndexDefinition($table, $index)
    {
        $result = $this->loadManager('Get table index definition');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->getTableIndexDefinition($this, $table, $index));
    }

    // }}}
    // {{{ createSequence()

    /**
     * create sequence
     *
     * @param string $name name of the sequence to be created
     * @param string $start start value of the sequence; default is 1
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function createSequence($name, $start = 1)
    {
        $result = $this->loadManager('Create sequence');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->createSequence($this, $name, $start));
    }

    // }}}
    // {{{ dropSequence()

    /**
     * drop existing sequence
     *
     * @param string $name name of the sequence to be dropped
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function dropSequence($name)
    {
        $result = $this->loadManager('Drop sequence');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->dropSequence($this, $name));
    }

    // }}}
    // {{{ listSequences()

    /**
     * list all tables in the current database
     *
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function listSequences()
    {
        $result = $this->loadManager('List sequences');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->listSequences($this));
    }

    // }}}
    // {{{ getSequenceDefinition()

    /**
     * get the stucture of a sequence into an array
     *
     * @param string $sequence name of sequence that should be used in method
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function getSequenceDefinition($sequence)
    {
        $result = $this->loadManager('Get sequence definition');
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->manager->getSequenceDefinition($this, $sequence));
    }

    // }}}
    // {{{ query()

    /**
     * Send a query to the database and return any results
     *
     * @param string $query the SQL query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     * @access public
     */
    function query($query, $types = NULL)
    {
        $this->debug("Query: $query");
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Query: database queries are not implemented'));
    }

    // }}}
    // {{{ setSelectedRowRange()

    /**
     * set the range of the next query
     *
     * @param string $first first row to select
     * @param string $limit number of rows to select
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function setSelectedRowRange($first, $limit)
    {
        if (!isset($this->supported['SelectRowRanges'])) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                'Set selected row range: selecting row ranges is not supported by this driver'));
        }
        $first = (int)$first;
        if ($first < 0) {
            return($this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                'Set selected row range: it was not specified a valid first selected range row'));
        }
        $limit = (int)$limit;
        if ($limit < 1) {
            return($this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                'Set selected row range: it was not specified a valid selected range row limit'));
        }
        $this->first_selected_row = $first;
        $this->selected_row_limit = $limit;
        return(MDB_OK);
    }

    // }}}
    // {{{ limitQuery()

    /**
     * Generates a limited query
     *
     * @param string $query query
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     * @param integer $from the row to start to fetching
     * @param integer $count the numbers of rows to fetch
     * @return mixed a valid ressource pointer or a MDB_Error
     * @access public
     */
    function limitQuery($query, $types = NULL, $from, $count)
    {
        $result = $this->setSelectedRowRange($from, $count);
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->query($query, $types));
    }

    // }}}
    // {{{ subSelect()

    /**
     * simple subselect emulation: leaves the query untouched for all RDBMS
     * that support subselects
     *
     * @access public
     *
     * @param string $query the SQL query for the subselect that may only
     *                      return a column
     * @param string $quote determines if the data needs to be quoted before
     *                      being returned
     *
     * @return string the query
     */
    function subSelect($query, $quote = FALSE)
    {
        if ($this->supported['SubSelects'] == 1) {
            return($query);
        }
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Subselect: subselect not implemented'));
    }

    // }}}
    // {{{ replace()

    /**
     * Execute a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * pratically only MySQL implements it natively, this type of query is
     * emulated through this method for other DBMS using standard types of
     * queries inside a transaction to assure the atomicity of the operation.
     *
     * @param string $table name of the table on which the REPLACE query will
     *       be executed.
     * @param array $fields associative array that describes the fields and the
     *       values that will be inserted or updated in the specified table. The
     *       indexes of the array are the names of all the fields of the table.
     *       The values of the array are also associative arrays that describe
     *       the values and other properties of the table fields.
     *
     *       Here follows a list of field properties that need to be specified:
     *
     *       Value
     *           Value to be assigned to the specified field. This value may be
     *           of specified in database independent type format as this
     *           function can perform the necessary datatype conversions.
     *
     *           Default: this property is required unless the Null property is
     *           set to 1.
     *
     *       Type
     *           Name of the type of the field. Currently, all types Metabase
     *           are supported except for clob and blob.
     *
     *           Default: no type conversion
     *
     *       Null
     *           Boolean property that indicates that the value for this field
     *           should be set to NULL.
     *
     *           The default value for fields missing in INSERT queries may be
     *           specified the definition of a table. Often, the default value
     *           is already NULL, but since the REPLACE may be emulated using
     *           an UPDATE query, make sure that all fields of the table are
     *           listed in this function argument array.
     *
     *           Default: 0
     *
     *       Key
     *           Boolean property that indicates that this field should be
     *           handled as a primary key or at least as part of the compound
     *           unique index of the table that will determine the row that will
     *           updated if it exists or inserted a new row otherwise.
     *
     *           This function will fail if no key field is specified or if the
     *           value of a key field is set to NULL because fields that are
     *           part of unique index they may not be NULL.
     *
     *           Default: 0
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function replace($table, $fields)
    {
        if (!$this->supported['Replace']) {
            return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Replace: replace query is not supported'));
        }
        $count = count($fields);
        for($keys = 0, $condition = $insert = $values = '', reset($fields), $field = 0;
            $field < $count;
            next($fields), $field++)
        {
            $name = key($fields);
            if ($field > 0) {
                $insert .= ', ';
                $values .= ', ';
            }
            $insert .= $name;
            if (isset($fields[$name]['Null']) && $fields[$name]['Null']) {
                $value = 'NULL';
            } else {
                if(isset($fields[$name]['Type'])) {
                    switch ($fields[$name]['Type']) {
                        case 'text':
                            $value = $this->getTextValue($fields[$name]['Value']);
                            break;
                        case 'boolean':
                            $value = $this->getBooleanValue($fields[$name]['Value']);
                            break;
                        case 'integer':
                            $value = $this->getIntegerValue($fields[$name]['Value']);
                            break;
                        case 'decimal':
                            $value = $this->getDecimalValue($fields[$name]['Value']);
                            break;
                        case 'float':
                            $value = $this->getFloatValue($fields[$name]['Value']);
                            break;
                        case 'date':
                            $value = $this->getDateValue($fields[$name]['Value']);
                            break;
                        case 'time':
                            $value = $this->getTimeValue($fields[$name]['Value']);
                            break;
                        case 'timestamp':
                            $value = $this->getTimestampValue($fields[$name]['Value']);
                            break;
                        default:
                            return($this->raiseError(MDB_ERROR_CANNOT_REPLACE, NULL, NULL,
                                'no supported type for field "' . $name . '" specified'));
                    }
                } else {
                    $value = $fields[$name]['Value'];
                }
            }
            $values .= $value;
            if (isset($fields[$name]['Key']) && $fields[$name]['Key']) {
                if ($value === 'NULL') {
                    return($this->raiseError(MDB_ERROR_CANNOT_REPLACE, NULL, NULL,
                        'key values may not be NULL'));
                }
                $condition .= ($keys ? ' AND ' : ' WHERE ') . $name . '=' . $value;
                $keys++;
            }
        }
        if ($keys == 0) {
            return($this->raiseError(MDB_ERROR_CANNOT_REPLACE, NULL, NULL,
                'not specified which fields are keys'));
        }
        $in_transaction = $this->in_transaction;
        if (!$in_transaction && MDB::isError($result = $this->autoCommit(FALSE))) {
            return($result);
        }
        $success = $this->query("DELETE FROM $table$condition");
        if (!MDB::isError($success)) {
            $affected_rows = $this->affected_rows;
            $success = $this->query("INSERT INTO $table ($insert) VALUES ($values)");
            $affected_rows += $this->affected_rows;
        }

        if (!$in_transaction) {
            if (!MDB::isError($success)) {
                if (!MDB::isError($success = $this->commit())
                    && !MDB::isError($success = $this->autoCommit(TRUE))
                    && isset($this->supported['AffectedRows'])
                ) {
                    $this->affected_rows = $affected_rows;
                }
            } else {
                $this->rollback();
                $this->autoCommit(TRUE);
            }
        }
        return($success);
    }

    // }}}
    // {{{ prepareQuery()

    /**
     * Prepares a query for multiple execution with execute().
     * With some database backends, this is emulated.
     * prepareQuery() requires a generic query as string like
     * 'INSERT INTO numbers VALUES(?,?,?)'. The ? are wildcards.
     * Types of wildcards:
     *    ? - a quoted scalar value, i.e. strings, integers
     *
     * @param string $ the query to prepare
     * @return mixed resource handle for the prepared query on success, a DB
     *        error on failure
     * @access public
     * @see execute
     */
    function prepareQuery($query)
    {
        $this->debug("PrepareQuery: $query");
        $positions = array();
        for($position = 0;
            $position < strlen($query) && is_integer($question = strpos($query, '?', $position));
        ) {
            if (is_integer($quote = strpos($query, "'", $position))
                && $quote < $question
            ) {
                if (!is_integer($end_quote = strpos($query, "'", $quote + 1))) {
                    return($this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                        'Prepare query: query with an unterminated text string specified'));
                }
                switch ($this->escape_quotes) {
                    case '':
                    case "'":
                        $position = $end_quote + 1;
                        break;
                    default:
                        if ($end_quote == $quote + 1) {
                            $position = $end_quote + 1;
                        } else {
                            if ($query[$end_quote-1] == $this->escape_quotes) {
                                $position = $end_quote;
                            } else {
                                $position = $end_quote + 1;
                            }
                        }
                        break;
                }
            } else {
                $positions[] = $question;
                $position = $question + 1;
            }
        }
        $this->prepared_queries[] = array(
            'Query' => $query,
            'Positions' => $positions,
            'Values' => array(),
            'Types' => array()
            );
        $prepared_query = count($this->prepared_queries);
        if ($this->selected_row_limit > 0) {
            $this->prepared_queries[$prepared_query-1]['First'] = $this->first_selected_row;
            $this->prepared_queries[$prepared_query-1]['Limit'] = $this->selected_row_limit;
        }
        return($prepared_query);
    }

    // }}}
    // {{{ _validatePreparedQuery()

    /**
     * validate that a handle is infact a prepared query
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @access private
     */
    function _validatePreparedQuery($prepared_query)
    {
        if ($prepared_query < 1 || $prepared_query > count($this->prepared_queries)) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'Validate prepared query: invalid prepared query'));
        }
        if (gettype($this->prepared_queries[$prepared_query-1]) != 'array') {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'Validate prepared query: prepared query was already freed'));
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ freePreparedQuery()

    /**
     * Release resources allocated for the specified prepared query.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function freePreparedQuery($prepared_query)
    {
        $result = $this->_validatePreparedQuery($prepared_query);
        if (MDB::isError($result)) {
            return($result);
        }
        $this->prepared_queries[$prepared_query-1] = '';
        return(MDB_OK);
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
    function _executePreparedQuery($prepared_query, $query, $types = NULL)
    {
        return($this->query($query, $types));
    }

    // }}}
    // {{{ executeQuery()

    /**
     * Execute a prepared query statement.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param array $types array that contains the types of the columns in the
     *       result set
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     * @access public
     */
    function executeQuery($prepared_query, $types = NULL)
    {
        $result = $this->_validatePreparedQuery($prepared_query);
        if (MDB::isError($result)) {
            return($result);
        }
        $index = $prepared_query-1;
        $success = MDB_OK;
        $this->clobs[$prepared_query] = $this->blobs[$prepared_query] = array();
        $query = '';
        for($last_position = $position = 0;
            $position < count($this->prepared_queries[$index]['Positions']);
            $position++) {
            if (!isset($this->prepared_queries[$index]['Values'][$position])) {
                return($this->raiseError(MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                    'Execute query: it was not defined query argument '.($position + 1)));
            }
            $current_position = $this->prepared_queries[$index]['Positions'][$position];
            $query .= substr($this->prepared_queries[$index]['Query'], $last_position, $current_position - $last_position);
            $value = $this->prepared_queries[$index]['Values'][$position];
            if ($this->prepared_queries[$index]['IsNULL'][$position]) {
                $query .= $value;
            } else {
                switch ($this->prepared_queries[$index]['Types'][$position]) {
                    case 'clob':
                        if (!MDB::isError($success = $this->getClobValue($prepared_query, $position + 1, $value))) {
                            $this->clobs[$prepared_query][$position + 1] = $success;
                            $query .= $this->clobs[$prepared_query][$position + 1];
                        }
                        break;
                    case 'blob':
                        if (!MDB::isError($success = $this->getBlobValue($prepared_query, $position + 1, $value))) {
                            $this->blobs[$prepared_query][$position + 1] = $success;
                            $query .= $this->blobs[$prepared_query][$position + 1];
                        }
                        break;
                    default:
                        $query .= $value;
                        break;
                }
            }
            $last_position = $current_position + 1;
        }
        if (!MDB::isError($success)) {
            $query .= substr($this->prepared_queries[$index]['Query'], $last_position);
            if ($this->selected_row_limit > 0) {
                $this->prepared_queries[$index]['First'] = $this->first_selected_row;
                $this->prepared_queries[$index]['Limit'] = $this->selected_row_limit;
            }
            if (isset($this->prepared_queries[$index]['Limit'])
                && $this->prepared_queries[$index]['Limit'] > 0
            ) {
                $this->first_selected_row = $this->prepared_queries[$index]['First'];
                $this->selected_row_limit = $this->prepared_queries[$index]['Limit'];
            } else {
                $this->first_selected_row = $this->selected_row_limit = 0;
            }
            $success = $this->_executePreparedQuery($prepared_query, $query, $types);
        }
        for(reset($this->clobs[$prepared_query]), $clob = 0;
            $clob < count($this->clobs[$prepared_query]);
            $clob++, next($this->clobs[$prepared_query])) {
            $this->freeClobValue($prepared_query, key($this->clobs[$prepared_query]), $this->clobs[$prepared_query][key($this->clobs[$prepared_query])], $success);
        }
        unset($this->clobs[$prepared_query]);
        for(reset($this->blobs[$prepared_query]), $blob = 0;
            $blob < count($this->blobs[$prepared_query]);
            $blob++, next($this->blobs[$prepared_query])) {
            $this->freeBlobValue($prepared_query, key($this->blobs[$prepared_query]), $this->blobs[$prepared_query][key($this->blobs[$prepared_query])], $success);
        }
        unset($this->blobs[$prepared_query]);
        return($success);
    }

    // }}}
    // {{{ execute()

    /**
     * Executes a prepared SQL query
     * With execute() the generic query of prepare is assigned with the given
     * data array. The values of the array inserted into the query in the same
     * order like the array order
     *
     * @param resource $prepared_query query handle from prepare()
     * @param array $types array that contains the types of the columns in
     *        the result set
     * @param array $params numeric array containing the data to insert into
     *        the query
     * @param array $param_types array that contains the types of the values
     *        defined in $params
     * @return mixed a new result handle or a MDB_Error when fail
     * @access public
     * @see prepare()
     */
    function execute($prepared_query, $types = NULL, $params = FALSE, $param_types = NULL)
    {
        $this->setParamArray($prepared_query, $params, $param_types);

        return($this->executeQuery($prepared_query, $types));
    }

    // }}}
    // {{{ executeMultiple()

    /**
     * This function does several execute() calls on the same statement handle.
     * $params must be an array indexed numerically from 0, one execute call is
     * done for every 'row' in the array.
     *
     * If an error occurs during execute(), executeMultiple() does not execute
     * the unfinished rows, but rather returns that error.
     *
     * @param resource $stmt query handle from prepare()
     * @param array $types array that contains the types of the columns in
     *        the result set
     * @param array $params numeric array containing the
     *        data to insert into the query
     * @param array $parAM_types array that contains the types of the values
     *        defined in $params
     * @return mixed a result handle or MDB_OK on success, a MDB error on failure
     * @access public
     * @see prepare(), execute()
     */
    function executeMultiple($prepared_query, $types = NULL, $params, $param_types = NULL)
    {
        for($i = 0, $j = count($params); $i < $j; $i++) {
            $result = $this->execute($prepared_query, $types, $params[$i], $param_types);
            if (MDB::isError($result)) {
                return($result);
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ setParam()

    /**
     * Set the value of a parameter of a prepared query.
     *
     * @param int $prepared_query argument is a handle that was returned
     *       by the function prepareQuery()
     * @param int $parameter the order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $type designation of the type of the parameter to be set.
     *       The designation of the currently supported types is as follows:
     *           text, boolean, integer, decimal, float, date, time, timestamp,
     *           clob, blob
     * @param mixed $value value that is meant to be assigned to specified
     *       parameter. The type of the value depends on the $type argument.
     * @param boolean $is_null flag that indicates whether whether the
     *       parameter is a NULL
     * @param string $field name of the field that is meant to be assigned
     *       with this parameter value when it is of type clob or blob
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function setParam($prepared_query, $parameter, $type, $value, $is_null = 0, $field = '')
    {
        $result = $this->_validatePreparedQuery($prepared_query);
        if (MDB::isError($result)) {
            return($result);
        }
        $index = $prepared_query - 1;
        if ($parameter < 1 || $parameter > count($this->prepared_queries[$index]['Positions'])) {
            return($this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                'Query set: it was not specified a valid argument number'));
        }
        $this->prepared_queries[$index]['Values'][$parameter-1] = $value;
        $this->prepared_queries[$index]['Types'][$parameter-1] = $type;
        $this->prepared_queries[$index]['Fields'][$parameter-1] = $field;
        $this->prepared_queries[$index]['IsNULL'][$parameter-1] = $is_null;
        return(MDB_OK);
    }

    // }}}
    // {{{ setParamArray()

    /**
     * Set the values of multiple a parameter of a prepared query in bulk.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param array $params array thats specifies all necessary infromation
     *       for setParam() the array elements must use keys corresponding to
     *       the number of the position of the parameter.
     * @param array $types array thats specifies the types of the fields
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamArray($prepared_query, $params, $types = NULL)
    {
        if (is_array($types)) {
            if (count($params) != count($types)) {
                return $this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                    'setParamArray: the number of given types ('.count($types).')'
                    .'is not corresponding to the number of given parameters ('.count($params).')');
            }
            for($i = 0, $j = count($params); $i < $j; ++$i) {
                switch ($types[$i]) {
                    case 'NULL':
                        $success = $this->setParam($prepared_query, $i + 1, $params[$i][0], 'NULL', 1, '');
                        break;
                    case 'text':
                        $success = $this->setParam($prepared_query, $i + 1, 'text', $this->getTextValue($params[$i]));
                        break;
                    case 'clob':
                        $success = $this->setParam($prepared_query, $i + 1, 'clob', $params[$i][0], 0, $params[$i][1]);
                        break;
                    case 'blob':
                        $success = $this->setParam($prepared_query, $i + 1, 'blob', $params[$i][0], 0, $params[$i][1]);
                        break;
                    case 'integer':
                        $success = $this->setParam($prepared_query, $i + 1, 'integer', $this->getIntegerValue($params[$i]));
                        break;
                    case 'boolean':
                        $success = $this->setParam($prepared_query, $i + 1, 'boolean', $this->getBooleanValue($params[$i]));
                        break;
                    case 'date':
                        $success = $this->setParam($prepared_query, $i + 1, 'date', $this->getDateValue($params[$i]));
                        break;
                    case 'timestamp':
                        $success = $this->setParam($prepared_query, $i + 1, 'timestamp', $this->getTimestampValue($params[$i]));
                        break;
                    case 'time':
                        $success = $this->setParam($prepared_query, $i + 1, 'time', $this->getTimeValue($params[$i]));
                        break;
                    case 'float':
                        $success = $this->setParam($prepared_query, $i + 1, 'float', $this->getFloatValue($params[$i]));
                        break;
                    case 'decimal':
                        $success = $this->setParam($prepared_query, $i + 1, 'decimal', $this->getDecimalValue($params[$i]));
                        break;
                    default:
                        $success = $this->setParam($prepared_query, $i + 1, 'text', $this->getTextValue($params[$i]));
                        break;
                }
                if (MDB::isError($success)) {
                    return($success);
                }
            }
        } else {
            for($i = 0, $j = count($params); $i < $j; ++$i) {
                $success = $this->setParam($prepared_query, $i + 1, 'text', $this->getTextValue($params[$i]));
                if (MDB::isError($success)) {
                    return($success);
                }
            }
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ setParamNull()

    /**
     * Set the value of a parameter of a prepared query to NULL.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $type designation of the type of the parameter to be set.
     *       The designation of the currently supported types is list in the
     *       usage of the function  setParam()
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamNull($prepared_query, $parameter, $type)
    {
        return($this->setParam($prepared_query, $parameter, $type, 'NULL', 1, ''));
    }

    // }}}
    // {{{ setParamText()

    /**
     * Set a parameter of a prepared query with a text value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $value text value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamText($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'text', $this->getTextValue($value)));
    }

    // }}}
    // {{{ setParamClob()

    /**
     * Set a parameter of a prepared query with a character large object value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param int $value handle of large object created with createLOB()
     *       function from which it will be read the data value that is meant
     *       to be assigned to specified parameter.
     * @param string $field name of the field of a INSERT or UPDATE query to
     *       which it will be assigned the value to specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamClob($prepared_query, $parameter, $value, $field)
    {
        return($this->setParam($prepared_query, $parameter, 'clob', $value, 0, $field));
    }

    // }}}
    // {{{ setParamBlob()

    /**
     * Set a parameter of a prepared query with a binary large object value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param int $value handle of large object created with createLOB()
     *       function from which it will be read the data value that is meant
     *       to be assigned to specified parameter.
     * @param string $field name of the field of a INSERT or UPDATE query to
     *       which it will be assigned the value to specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamBlob($prepared_query, $parameter, $value, $field)
    {
        return($this->setParam($prepared_query, $parameter, 'blob', $value, 0, $field));
    }

    // }}}
    // {{{ setParamInteger()

    /**
     * Set a parameter of a prepared query with a text value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param int $value an integer value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamInteger($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'integer', $this->getIntegerValue($value)));
    }

    // }}}
    // {{{ setParamBoolean()

    /**
     * Set a parameter of a prepared query with a boolean value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param boolean $value boolean value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamBoolean($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'boolean', $this->getBooleanValue($value)));
    }

    // }}}
    // {{{ setParamDate()

    /**
     * Set a parameter of a prepared query with a date value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $value date value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamDate($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'date', $this->getDateValue($value)));
    }

    // }}}
    // {{{ setParamTimestamp()

    /**
     * Set a parameter of a prepared query with a time stamp value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $value time stamp value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamTimestamp($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'timestamp', $this->getTimestampValue($value)));
    }

    // }}}
    // {{{ setParamTime()

    /**
     * Set a parameter of a prepared query with a time value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $value time value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamTime($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'time', $this->getTimeValue($value)));
    }

    // }}}
    // {{{ setParamFloat()

    /**
     * Set a parameter of a prepared query with a float value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $value float value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamFloat($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'float', $this->getFloatValue($value)));
    }

    // }}}
    // {{{ setParamDecimal()

    /**
     * Set a parameter of a prepared query with a decimal value.
     *
     * @param int $prepared_query argument is a handle that was returned by
     *       the function prepareQuery()
     * @param int $parameter order number of the parameter in the query
     *       statement. The order number of the first parameter is 1.
     * @param string $value decimal value that is meant to be assigned to
     *       specified parameter.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     * @see setParam()
     */
    function setParamDecimal($prepared_query, $parameter, $value)
    {
        return($this->setParam($prepared_query, $parameter, 'decimal', $this->getDecimalValue($value)));
    }

    // }}}
    // {{{ setResultTypes()

    /**
     * Define the list of types to be associated with the columns of a given
     * result set.
     *
     * This function may be called before invoking fetchInto(), fetchOne(),
     * fetchRow(), fetchCol() and fetchAll() so that the necessary data type
     * conversions are performed on the data to be retrieved by them. If this
     * function is not called, the type of all result set columns is assumed
     * to be text, thus leading to not perform any conversions.
     *
     * @param resource $result result identifier
     * @param string $types array variable that lists the
     *       data types to be expected in the result set columns. If this array
     *       contains less types than the number of columns that are returned
     *       in the result set, the remaining columns are assumed to be of the
     *       type text. Currently, the types clob and blob are not fully
     *       supported.
     * @return mixed MDB_OK on success, a MDB error on failure
     * @access public
     */
    function setResultTypes($result, $types)
    {
        $result_value = intval($result);
        if (isset($this->result_types[$result_value])) {
            return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                'Set result types: attempted to redefine the types of the columns of a result set'));
        }
        $columns = $this->numCols($result);
        if (MDB::isError($columns)) {
            return($columns);
        }
        if ($columns < count($types)) {
            return($this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                'Set result types: it were specified more result types (' . count($types) . ') than result columns (' . $columns . ')'));
        }
        $valid_types = array(
            'text'      => MDB_TYPE_TEXT,
            'boolean'   => MDB_TYPE_BOOLEAN,
            'integer'   => MDB_TYPE_INTEGER,
            'decimal'   => MDB_TYPE_DECIMAL,
            'float'     => MDB_TYPE_FLOAT,
            'date'      => MDB_TYPE_DATE,
            'time'      => MDB_TYPE_TIME,
            'timestamp' => MDB_TYPE_TIMESTAMP,
            'clob'      => MDB_TYPE_CLOB,
            'blob'      => MDB_TYPE_BLOB
        );
        for($column = 0; $column < count($types); $column++) {
            if (!isset($valid_types[$types[$column]])) {
                return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
                    'Set result types: ' . $types[$column] . ' is not a supported column type'));
            }
            $this->result_types[$result_value][$column] = $valid_types[$types[$column]];
        }
        while ($column < $columns) {
            $this->result_types[$result_value][$column] = MDB_TYPE_TEXT;
            $column++;
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ affectedRows()

    /**
     * returns the affected rows of a query
     *
     * @return mixed MDB_Error or number of rows
     * @access public
     */
    function affectedRows()
    {
        if ($this->affected_rows == -1) {
            return($this->raiseError(MDB_ERROR_NEED_MORE_DATA));
        }
        return($this->affected_rows);
    }

    // }}}
    // {{{ getColumnNames()

    /**
     * Retrieve the names of columns returned by the DBMS in a query result.
     *
     * @param resource $result result identifier
     * @return mixed associative array variable
     *       that holds the names of columns. The indexes of the array are
     *       the column names mapped to lower case and the values are the
     *       respective numbers of the columns starting from 0. Some DBMS may
     *       not return any columns when the result set does not contain any
     *       rows.
     *      a MDB error on failure
     * @access public
     */
    function getColumnNames($result)
    {
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Get column names: obtaining result column names is not implemented'));
    }

    // }}}
    // {{{ numCols()

    /**
     * Count the number of columns returned by the DBMS in a query result.
     *
     * @param resource $result result identifier
     * @return mixed integer value with the number of columns, a MDB error
     *       on failure
     * @access public
     */
    function numCols($result)
    {
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Number of columns: obtaining the number of result columns is not implemented'));
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
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'End of result: end of result method not implemented'));
    }

    // }}}
    // {{{ setFetchMode()

    /**
     * Sets which fetch mode should be used by default on queries
     * on this connection.
     *
     * @param integer $fetchmode MDB_FETCHMODE_ORDERED or MDB_FETCHMODE_ASSOC,
     *       possibly bit-wise OR'ed with MDB_FETCHMODE_FLIPPED.
     * @access public
     * @see MDB_FETCHMODE_ORDERED
     * @see MDB_FETCHMODE_ASSOC
     * @see MDB_FETCHMODE_FLIPPED
     */
    function setFetchMode($fetchmode)
    {
        switch ($fetchmode) {
            case MDB_FETCHMODE_ORDERED:
            case MDB_FETCHMODE_ASSOC:
                $this->fetchmode = $fetchmode;
                break;
            default:
                return($this->raiseError('invalid fetchmode mode'));
        }
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
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Fetch: fetch result method not implemented'));
    }

    // }}}
    // {{{ fetchLob()

    /**
     * fetch a lob value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed string on success, a MDB error on failure
     * @access public
     */
    function fetchLob($result, $row, $field)
    {
        $lob = count($this->lobs) + 1;
        $this->lobs[$lob] = array(
            'Result' => $result,
            'Row' => $row,
            'Field' => $field,
            'Position' => 0
        );
        $dst_lob = array(
            'Database' => $this,
            'Error' => '',
            'Type' => 'resultlob',
            'ResultLOB' => $lob
        );
        if (MDB::isError($lob = $this->createLob($dst_lob))) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Fetch LOB result: ' . $dst_lob['Error']));
        }
        return($lob);
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
            return($this->raiseError(MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                'Fetch LOB result: it was not specified a valid lob'));
        }
        if (!isset($this->lobs[$lob]['Value'])) {
            $this->lobs[$lob]['Value'] = $this->fetch($this->lobs[$lob]['Result'], $this->lobs[$lob]['Row'], $this->lobs[$lob]['Field']);
        }
        return(MDB_OK);
    }

    // }}}
    // {{{ endOfResultLob()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @return mixed TRUE or FALSE on success, a MDB error on failure
     * @access public
     */
    function endOfResultLob($lob)
    {
        $result = $this->_retrieveLob($lob);
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->lobs[$lob]['Position'] >= strlen($this->lobs[$lob]['Value']));
    }

    // }}}
    // {{{ _readResultLob()

    /**
     * Read data from large object input stream.
     *
     * @param int $lob handle to a lob created by the createLob() function
     * @param blob $data reference to a variable that will hold data to be
     *       read from the large object input stream
     * @param int $length integer value that indicates the largest ammount of
     *       data to be read from the large object input stream.
     * @return mixed length on success, a MDB error on failure
     * @access private
     */
    function _readResultLob($lob, &$data, $length)
    {
        $result = $this->_retrieveLob($lob);
        if (MDB::isError($result)) {
            return($result);
        }
        $length = min($length, strlen($this->lobs[$lob]['Value']) - $this->lobs[$lob]['Position']);
        $data = substr($this->lobs[$lob]['Value'], $this->lobs[$lob]['Position'], $length);
        $this->lobs[$lob]['Position'] += $length;
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
     *        a MDB error on failure
     * @access public
     */
    function fetchClob($result, $row, $field)
    {
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'fetch clob result method is not implemented'));
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
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'fetch blob result method is not implemented'));
    }

    // }}}
    // {{{ resultIsNull()

    /**
     * Determine whether the value of a query result located in given row and
     *    field is a NULL.
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed TRUE or FALSE on success, a MDB error on failure
     * @access public
     */
    function resultIsNull($result, $row, $field)
    {
        $result = $this->fetch($result, $row, $field);
        if (MDB::isError($result)) {
            return($result);
        }
        return(!isset($result));
    }

    // }}}
    // {{{ _baseConvertResult()

    /**
     * general type conversion method
     *
     * @param mixed $value refernce to a value to be converted
     * @param int $type constant that specifies which type to convert to
     * @return object a MDB error on failure
     * @access private
     */
    function _baseConvertResult($value, $type)
    {
        switch ($type) {
            case MDB_TYPE_TEXT:
                return($value);
            case MDB_TYPE_BLOB:
                return($value);
            case MDB_TYPE_CLOB:
                return($value);
            case MDB_TYPE_INTEGER:
                return(intval($value));
            case MDB_TYPE_BOOLEAN:
                return ($value == 'Y') ? TRUE : FALSE;
            case MDB_TYPE_DECIMAL:
                return($value);
            case MDB_TYPE_FLOAT:
                return(doubleval($value));
            case MDB_TYPE_DATE:
                return($value);
            case MDB_TYPE_TIME:
                return($value);
            case MDB_TYPE_TIMESTAMP:
                return($value);
            case MDB_TYPE_CLOB:
                return($value);
            case MDB_TYPE_BLOB:
                return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                    'BaseConvertResult: attempt to convert result value to an unsupported type ' . $type));
            default:
                return($this->raiseError(MDB_ERROR_INVALID, NULL, NULL,
                    'BaseConvertResult: attempt to convert result value to an unknown type ' . $type));
        }
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
        return($this->_baseConvertResult($value, $type));
    }

    // }}}
    // {{{ convertResultRow()

    /**
     * convert a result row
     *
     * @param resource $result result identifier
     * @param array $row array with data
     * @return mixed MDB_OK on success,  a MDB error on failure
     * @access public
     */
    function convertResultRow($result, $row)
    {
        $result_value = intval($result);
        if (isset($this->result_types[$result_value])) {
            $current_column = -1;
            foreach($row as $key => $column) {
                ++$current_column;
                if (!isset($this->result_types[$result_value][$current_column])
                   ||!isset($column)
                ) {
                    continue;
                }
                switch ($type = $this->result_types[$result_value][$current_column]) {
                    case MDB_TYPE_TEXT:
                    case MDB_TYPE_BLOB:
                    case MDB_TYPE_CLOB:
                        break;
                    case MDB_TYPE_INTEGER:
                        $row[$key] = intval($row[$key]);
                        break;
                    default:
                        $value = $this->convertResult($row[$key], $type);
                        if (MDB::isError($value)) {
                            return $value;
                        }
                        $row[$key] = $value;
                        break;
                }
            }
        }
        return ($row);
    }

    // }}}
    // {{{ fetchDate()

    /**
     * fetch a date value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchDate($result, $row, $field)
    {
        $value = $this->fetch($result, $row, $field);
        return($this->convertResult($value, MDB_TYPE_DATE));
    }

    // }}}
    // {{{ fetchTimestamp()

    /**
     * fetch a timestamp value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchTimestamp($result, $row, $field)
    {
        $value = $this->fetch($result, $row, $field);
        return($this->convertResult($value, MDB_TYPE_TIMESTAMP));
    }

    // }}}
    // {{{ fetchTime()

    /**
     * fetch a time value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchTime($result, $row, $field)
    {
        $value = $this->fetch($result, $row, $field);
        return($this->convertResult($value, MDB_TYPE_TIME));
    }

    // }}}
    // {{{ fetchBoolean()

    /**
     * fetch a boolean value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchBoolean($result, $row, $field)
    {
        $value = $this->fetch($result, $row, $field);
        return($this->convertResult($value, MDB_TYPE_BOOLEAN));
    }

    // }}}
    // {{{ fetchFloat()

    /**
     * fetch a float value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchFloat($result, $row, $field)
    {
        $value = $this->fetch($result, $row, $field);
        return($this->convertResult($value, MDB_TYPE_FLOAT));
    }

    // }}}
    // {{{ fetchDecimal()

    /**
     * fetch a decimal value from a result set
     *
     * @param resource $result result identifier
     * @param int $row number of the row where the data can be found
     * @param int $field field number where the data can be found
     * @return mixed content of the specified data cell, a MDB error on failure
     * @access public
     */
    function fetchDecimal($result, $row, $field)
    {
        $value = $this->fetch($result, $row, $field);
        return($this->convertResult($value, MDB_TYPE_DECIMAL));
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
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Num Rows: number of rows method not implemented'));
    }

    // }}}
    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param  $result result identifier
     * @return boolean TRUE on success, FALSE if $result is invalid
     * @access public
     */
    function freeResult($result)
    {
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL, 'Free Result: free result method not implemented'));
    }

    // }}}
    // {{{ getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       unsigned
     *           Boolean flag that indicates whether the field should be
     *           declared as unsigned integer if possible.
     *
     *       default
     *           Integer value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getIntegerDeclaration($name, $field)
    {
        if (isset($field['unsigned'])) {
            $this->warnings[] = "unsigned integer field \"$name\" is being
                declared as signed integer";
        }
        return("$name INT" . (isset($field['default']) ? ' DEFAULT ' . $field['default'] : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return((isset($field['length']) ? "$name CHAR (" . $field['length'] . ')' : "$name TEXT") . (isset($field['default']) ? ' DEFAULT ' . $this->getTextValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getClobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the large
     *           object field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getClobDeclaration($name, $field)
    {
        return((isset($field['length']) ? "$name CHAR (" . $field['length'] . ')' : "$name TEXT") . (isset($field['default']) ? ' DEFAULT ' . $this->getTextValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getBlobDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       length
     *           Integer value that determines the maximum length of the large
     *           object field. If this argument is missing the field should be
     *           declared to have the longest length allowed by the DBMS.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getBlobDeclaration($name, $field)
    {
        return((isset($field['length']) ? "$name CHAR (" . $field['length'] . ')' : "$name TEXT") . (isset($field['default']) ? ' DEFAULT ' . $this->getTextValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
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
        return("$name CHAR (1)" . (isset($field['default']) ? ' DEFAULT ' . $this->getBooleanValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDateDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a date type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Date value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getDateDeclaration($name, $field)
    {
        return("$name CHAR (" . strlen("YYYY-MM-DD") . ")" . (isset($field['default']) ? ' DEFAULT ' . $this->getDateValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getTimestampDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a timestamp
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Timestamp value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getTimestampDeclaration($name, $field)
    {
        return("$name CHAR (" . strlen("YYYY-MM-DD HH:MM:SS") . ")" . (isset($field['default']) ? ' DEFAULT ' . $this->getTimestampValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getTimeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a time
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Time value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getTimeDeclaration($name, $field)
    {
        return("$name CHAR (" . strlen("HH:MM:SS") . ")" . (isset($field['default']) ? ' DEFAULT ' . $this->getTimeValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getFloatDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a float type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Float value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getFloatDeclaration($name, $field)
    {
        return("$name TEXT " . (isset($field['default']) ? ' DEFAULT ' . $this->getFloatValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getDecimalDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a decimal type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param string $field associative array with the name of the properties
     *       of the field being declared as array indexes. Currently, the types
     *       of supported field properties are as follows:
     *
     *       default
     *           Decimal value to be used as default for this field.
     *
     *       notnull
     *           Boolean flag that indicates whether this field is constrained
     *           to not be set to NULL.
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     * @access public
     */
    function getDecimalDeclaration($name, $field)
    {
        return("$name TEXT " . (isset($field['default']) ? ' DEFAULT ' . $this->getDecimalValue($field['default']) : '') . (isset($field['notnull']) ? ' NOT NULL' : ''));
    }

    // }}}
    // {{{ getIntegerValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getIntegerValue($value)
    {
        return(($value === NULL) ? 'NULL' : (int)$value);
    }

    // }}}
    // {{{ getTextValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that already contains any DBMS specific
     *       escaped character sequences.
     * @access public
     */
    function getTextValue($value)
    {
        return(($value === NULL) ? 'NULL' : "'".$this->_quote($value)."'");
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
     *       a DBMS specific format.
     * @access public
     */
    function getClobValue($prepared_query, $parameter, $clob)
    {
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Get CLOB field value: prepared queries with values of type "clob" are not yet supported'));
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
     *       a DBMS specific format.
     * @access public
     */
    function getBlobValue($prepared_query, $parameter, $blob)
    {
        return($this->raiseError(MDB_ERROR_UNSUPPORTED, NULL, NULL,
            'Get BLOB field value: prepared queries with values of type "blob" are not yet supported'));
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
        return(($value === NULL) ? 'NULL' : ($value ? "'Y'" : "'N'"));
    }

    // }}}
    // {{{ getDateValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getDateValue($value)
    {
        return(($value === NULL) ? 'NULL' : "'$value'");
    }

    // }}}
    // {{{ getTimestampValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getTimestampValue($value)
    {
        return(($value === NULL) ? 'NULL' : "'$value'");
    }

    // }}}
    // {{{ getTimeValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     *       compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getTimeValue($value)
    {
        return(($value === NULL) ? 'NULL' : "'$value'");
    }

    // }}}
    // {{{ getFloatValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getFloatValue($value)
    {
        return(($value === NULL) ? 'NULL' : "'$value'");
    }

    // }}}
    // {{{ getDecimalValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getDecimalValue($value)
    {
        return(($value === NULL) ? 'NULL' : "'$value'");
    }

    // }}}
    // {{{ getValue()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $type type to which the value should be converted to
     * @param string $value text string value that is intended to be converted.
     * @return string text string that represents the given argument value in
     *       a DBMS specific format.
     * @access public
     */
    function getValue($type, $value)
    {
        if (empty($type)) {
            return($this->raiseError(MDB_ERROR_SYNTAX, NULL, NULL,
                'getValue: called without type to convert to'));
        }
        if (method_exists($this,"get{$type}Value")) {
            return $this->{"get{$type}Value"}($value);
        }
        return $value;
    }

    // }}}
    // {{{ support()

    /**
     * Tell whether a DB implementation or its backend extension
     * supports a given feature.
     *
     * @param string $feature name of the feature (see the MDB class doc)
     * @return boolean whether this DB implementation supports $feature
     * @access public
     */
    function support($feature)
    {
        return(isset($this->supported[$feature]) && $this->supported[$feature]);
    }

    // }}}
    // {{{ getSequenceName()

    /**
     * adds sequence name formating to a sequence name
     *
     * @param string $sqn name of the sequence
     * @return string formatted sequence name
     * @access public
     */
    function getSequenceName($sqn)
    {
        return sprintf($this->options['seqname_format'],
            preg_replace('/[^a-z0-9_]/i', '_', $sqn));
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
    function nextId($seq_name, $ondemand = FALSE)
    {
        return($this->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
            'Next Sequence: getting next sequence value not supported'));
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
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        $this->expectError(MDB_ERROR_NOT_CAPABLE);
        $id = $this->nextId($seq_name);
        $this->popExpect(MDB_ERROR_NOT_CAPABLE);
        if (MDB::isError($id)) {
            if ($id->getCode() == MDB_ERROR_NOT_CAPABLE) {
                return($this->raiseError(MDB_ERROR_NOT_CAPABLE, NULL, NULL,
                    'Current Sequence: getting current sequence value not supported'));
            }
            return($id);
        }
        return($id);
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
        if (MDB::isError($this->endOfResult($result))) {
            $this->freeResult($result);
            $result = $this->raiseError(MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                'Fetch field: result set is empty');
        }
        if ($rownum == NULL) {
            ++$this->highest_fetched_row[$result_value];
            $rownum = $this->highest_fetched_row[$result_value];
        } else {
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value], $row);
        }
        if ($fetchmode == MDB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        $columns = $this->numCols($result);
        if (MDB::isError($columns)) {
            return($columns);
        }
        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $column_names = $this->getColumnNames($result);
        }
        for($column = 0; $column < $columns; $column++) {
            if (!$this->resultIsNull($result, $rownum, $column)) {
                $value = $this->fetch($result, $rownum, $column);
                if ($value === FALSE) {
                    if ($this->options['autofree']) {
                        $this->freeResult($result);
                    }
                    return(NULL);
                } elseif (MDB::isError($value)) {
                    if ($value->getMessage() == '') {
                        if ($this->options['autofree']) {
                            $this->freeResult($result);
                        }
                        return(NULL);
                    } else {
                        return($value);
                    }
                }
            }
            $row[$column] = $value;
        }
        if ($fetchmode & MDB_FETCHMODE_ASSOC) {
            $row = array_combine($column_names, $row);
            if (is_array($row) && $this->options['optimize'] == 'portability') {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        }
        if (isset($this->result_types[$result_value])) {
            $row = $this->convertResultRow($result, $row);
        }
        return($row);
    }

    // }}}
    // {{{ fetchOne()

    /**
     * Fetch and return a field of data (it uses fetchInto for that)
     *
     * @param resource $result result identifier
     * @return mixed data on success, a MDB error on failure
     * @access public
     */
    function fetchOne($result)
    {
        $row = $this->fetchInto($result, MDB_FETCHMODE_ORDERED);
        if (!$this->options['autofree'] || $row != NULL) {
            $this->freeResult($result);
        }
        if (is_array($row)) {
            return($row[0]);
        }
        return($row);
    }

    // }}}
    // {{{ fetchRow()

    /**
     * Fetch and return a row of data (it uses fetchInto for that)
     *
     * @param resource $result result identifier
     * @param int $fetchmode how the array data should be indexed
     * @param int $rownum the row number to fetch
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function fetchRow($result, $fetchmode = MDB_FETCHMODE_DEFAULT, $rownum = NULL)
    {
        $row = $this->fetchInto($result, $fetchmode, $rownum);
        if (!$this->options['autofree'] || $row != NULL) {
            $this->freeResult($result);
        }
        return($row);
    }

    // }}}
    // {{{ fetchCol()

    /**
     * Fetch and return a column of data (it uses fetchInto for that)
     *
     * @param resource $result result identifier
     * @param int $colnum the row number to fetch
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function fetchCol($result, $colnum = 0)
    {
        $fetchmode = is_numeric($colnum) ? MDB_FETCHMODE_ORDERED : MDB_FETCHMODE_ASSOC;
        $column = array();
        $row = $this->fetchInto($result, $fetchmode);
        if (is_array($row)) {
            if (!array_key_exists($colnum, $row)) {
                return($this->raiseError(MDB_ERROR_TRUNCATED));
            }
            do {
                $column[] = $row[$colnum];
            } while (is_array($row = $this->fetchInto($result, $fetchmode)));
        }
        if (!$this->options['autofree']) {
            $this->freeResult($result);
        }
        if (MDB::isError($row)) {
            return($row);
        }
        return($column);
    }

    // }}}
    // {{{ fetchAll()

    /**
     * Fetch and return a column of data (it uses fetchInto for that)
     *
     * @param resource $result result identifier
     * @param int $fetchmode how the array data should be indexed
     * @param boolean $rekey if set to TRUE, the $all will have the first
     *       column as its first dimension
     * @param boolean $force_array used only when the query returns exactly
     *       two columns. If TRUE, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param boolean $group if TRUE, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     * @return mixed data array on success, a MDB error on failure
     * @access public
     * @see getAssoc()
     */
    function fetchAll($result, $fetchmode = MDB_FETCHMODE_DEFAULT, $rekey = FALSE, $force_array = FALSE, $group = FALSE)
    {
        if ($rekey) {
            $cols = $this->numCols($result);
            if (MDB::isError($cols)) {
                return($cols);
            }
            if ($cols < 2) {
                return($this->raiseError(MDB_ERROR_TRUNCATED));
            }
        }
        $all = array();
        while (is_array($row = $this->fetchInto($result, $fetchmode))) {
            if ($rekey) {
                if ($fetchmode & MDB_FETCHMODE_ASSOC) {
                    $key = reset($row);
                    unset($row[key($row)]);
                } else {
                    $key = array_shift($row);
                }
                if (!$force_array && count($row) == 1) {
                    $row = array_shift($row);
                }
                if ($group) {
                    $all[$key][] = $row;
                } else {
                    $all[$key] = $row;
                }
            } else {
                if ($fetchmode & MDB_FETCHMODE_FLIPPED) {
                    foreach ($row as $key => $val) {
                        $all[$key][] = $val;
                    }
                } else {
                   $all[] = $row;
                }
            }
        }
        if (!$this->options['autofree']) {
            $this->freeResult($result);
        }
        if (MDB::isError($row)) {
            return($row);
        }
        return($all);
    }

    // }}}
    // {{{ queryOne()

    /**
     * Execute the specified query, fetch the value from the first column of
     * the first row of the result set and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param string $type optional argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @return mixed field value on success, a MDB error on failure
     * @access public
     */
    function queryOne($query, $type = NULL)
    {
        if ($type != NULL) {
            $type = array($type);
        }
        $result = $this->query($query, $type);
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->fetchOne($result));
    }

    // }}}
    // {{{ queryRow()

    /**
     * Execute the specified query, fetch the values from the first
     * row of the result set into an array and then frees
     * the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $types optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param int $fetchmode how the array data should be indexed
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function queryRow($query, $types = NULL, $fetchmode = MDB_FETCHMODE_DEFAULT)
    {
        $result = $this->query($query, $types);
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->fetchRow($result, $fetchmode));
    }

    // }}}
    // {{{ queryCol()

    /**
     * Execute the specified query, fetch the value from the first column of
     * each row of the result set into an array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param string $type optional argument that specifies the expected
     *       datatype of the result set field, so that an eventual conversion
     *       may be performed. The default datatype is text, meaning that no
     *       conversion is performed
     * @param int $colnum the row number to fetch
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function queryCol($query, $type = NULL, $colnum = 0)
    {
        if ($type != NULL) {
            $type = array($type);
        }
        $result = $this->query($query, $type);
        if (MDB::isError($result)) {
            return($result);
        }
        return($this->fetchCol($result, $colnum));
    }

    // }}}
    // {{{ queryAll()

    /**
     * Execute the specified query, fetch all the rows of the result set into
     * a two dimensional array and then frees the result set.
     *
     * @param string $query the SELECT query statement to be executed.
     * @param array $types optional array argument that specifies a list of
     *       expected datatypes of the result set columns, so that the eventual
     *       conversions may be performed. The default list of datatypes is
     *       empty, meaning that no conversion is performed.
     * @param int $fetchmode how the array data should be indexed
     * @param boolean $rekey if set to TRUE, the $all will have the first
     *       column as its first dimension
     * @param boolean $force_array used only when the query returns exactly
     *       two columns. If TRUE, the values of the returned array will be
     *       one-element arrays instead of scalars.
     * @param boolean $group if TRUE, the values of the returned array is
     *       wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     * @return mixed data array on success, a MDB error on failure
     * @access public
     */
    function queryAll($query, $types = NULL, $fetchmode = MDB_FETCHMODE_DEFAULT,
        $rekey = FALSE, $force_array = FALSE, $group = FALSE)
    {
        if (MDB::isError($result = $this->query($query, $types))) {
            return($result);
        }
        return($this->fetchAll($result, $fetchmode, $rekey, $force_array, $group));
    }

    // }}}
    // {{{ getOne()

    /**
     * Fetch the first column of the first row of data returned from
     * a query.  Takes care of doing the query and freeing the results
     * when finished.
     *
     * @param string $query the SQL query
     * @param string $type string that contains the type of the column in the
     *       result set
     * @param array $params if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array $param_types array that contains the types of the values
     *       defined in $params
     * @return mixed MDB_Error or the returned value of the query
     * @access public
     */
    function getOne($query, $type = NULL, $params = array(), $param_types = NULL)
    {
        if ($type != NULL) {
            $type = array($type);
        }
        settype($params, 'array');
        if (count($params) > 0) {
            $prepared_query = $this->prepareQuery($query);
            if (MDB::isError($prepared_query)) {
                return($prepared_query);
            }
            $this->setParamArray($prepared_query, $params, $param_types);
            $result = $this->executeQuery($prepared_query, $type);
        } else {
            $result = $this->query($query, $type);
        }

        if (MDB::isError($result)) {
            return($result);
        }

        $value = $this->fetchOne($result, MDB_FETCHMODE_ORDERED);
        if (MDB::isError($value)) {
            return($value);
        }
        if (isset($prepared_query)) {
            $result = $this->freePreparedQuery($prepared_query);
            if (MDB::isError($result)) {
                return($result);
            }
        }

        return($value);
    }

    // }}}
    // {{{ getRow()

    /**
     * Fetch the first row of data returned from a query.  Takes care
     * of doing the query and freeing the results when finished.
     *
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in
     *       the result set
     * @param array $params array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array $param_types array that contains the types of the values
     *       defined in $params
     * @param integer $fetchmode the fetch mode to use
     * @return array the first row of results as an array indexed from
     * 0, or a MDB error code.
     * @access public
     */
    function getRow($query, $types = NULL, $params = array(), $param_types = NULL, $fetchmode = MDB_FETCHMODE_DEFAULT)
    {
        settype($params, 'array');
        if (count($params) > 0) {
            $prepared_query = $this->prepareQuery($query);
            if (MDB::isError($prepared_query)) {
                return($prepared_query);
            }
            $this->setParamArray($prepared_query, $params, $param_types);
            $result = $this->executeQuery($prepared_query, $types);
        } else {
            $result = $this->query($query, $types);
        }

        if (MDB::isError($result)) {
            return($result);
        }

        $row = $this->fetchRow($result, $fetchmode);
        if (MDB::isError($row)) {
            return($row);
        }
        if (isset($prepared_query)) {
            $result = $this->freePreparedQuery($prepared_query);
            if (MDB::isError($result)) {
                return($result);
            }
        }

        return($row);
    }

    // }}}
    // {{{ getCol()

    /**
     * Fetch a single column from a result set and return it as an
     * indexed array.
     *
     * @param string $query the SQL query
     * @param string $type string that contains the type of the column in the
     *       result set
     * @param array $params array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array $param_types array that contains the types of the values
     *       defined in $params
     * @param mixed $colnum which column to return(integer [column number,
     *       starting at 0] or string [column name])
     * @return array an indexed array with the data from the first
     * row at index 0, or a MDB error code.
     * @access public
     */
    function getCol($query, $type = NULL, $params = array(), $param_types = NULL, $colnum = 0)
    {
        if ($type != NULL) {
            $type = array($type);
        }
        settype($params, 'array');
        if (count($params) > 0) {
            $prepared_query = $this->prepareQuery($query);

            if (MDB::isError($prepared_query)) {
                return($prepared_query);
            }
            $this->setParamArray($prepared_query, $params, $param_types);
            $result = $this->executeQuery($prepared_query, $type);
        } else {
            $result = $this->query($query, $type);
        }

        if (MDB::isError($result)) {
            return($result);
        }

        $col = $this->fetchCol($result, $colnum);
        if (MDB::isError($col)) {
            return($col);
        }
        if (isset($prepared_query)) {
            $result = $this->freePreparedQuery($prepared_query);
            if (MDB::isError($result)) {
                return($result);
            }
        }
        return($col);
    }

    // }}}
    // {{{ getAssoc()

    /**
     * Fetch the entire result set of a query and return it as an
     * associative array using the first column as the key.
     *
     * If the result set contains more than two columns, the value
     * will be an array of the values from column 2-n.  If the result
     * set contains only two columns, the returned value will be a
     * scalar with the value of the second column (unless forced to an
     * array with the $force_array parameter).  A MDB error code is
     * returned on errors.  If the result set contains fewer than two
     * columns, a MDB_ERROR_TRUNCATED error is returned.
     *
     * For example, if the table 'mytable' contains:
     *
     *   ID      TEXT       DATE
     * --------------------------------
     *   1       'one'      944679408
     *   2       'two'      944679408
     *   3       'three'    944679408
     *
     * Then the call getAssoc('SELECT id,text FROM mytable') returns:
     *    array(
     *      '1' => 'one',
     *      '2' => 'two',
     *      '3' => 'three',
     *    )
     *
     * ...while the call getAssoc('SELECT id,text,date FROM mytable') returns:
     *    array(
     *      '1' => array('one', '944679408'),
     *      '2' => array('two', '944679408'),
     *      '3' => array('three', '944679408')
     *    )
     *
     * If the more than one row occurs with the same value in the
     * first column, the last row overwrites all previous ones by
     * default.  Use the $group parameter if you don't want to
     * overwrite like this.  Example:
     *
     * getAssoc('SELECT category,id,name FROM mytable', NULL, NULL
     *           MDB_FETCHMODE_ASSOC, FALSE, TRUE) returns:
     *    array(
     *      '1' => array(array('id' => '4', 'name' => 'number four'),
     *                   array('id' => '6', 'name' => 'number six')
     *             ),
     *      '9' => array(array('id' => '4', 'name' => 'number four'),
     *                   array('id' => '6', 'name' => 'number six')
     *             )
     *    )
     *
     * Keep in mind that database functions in PHP usually return string
     * values for results regardless of the database's internal type.
     *
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in
     *       the result set
     * @param array $params array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array $param_types array that contains the types of the values
     *       defined in $params
     * @param boolean $force_array used only when the query returns
     * exactly two columns.  If TRUE, the values of the returned array
     * will be one-element arrays instead of scalars.
     * @param boolean $group if TRUE, the values of the returned array
     *       is wrapped in another array.  If the same key value (in the first
     *       column) repeats itself, the values will be appended to this array
     *       instead of overwriting the existing values.
     * @return array associative array with results from the query.
     * @access public
     */
    function getAssoc($query, $types = NULL, $params = array(), $param_types = NULL,
        $fetchmode = MDB_FETCHMODE_ORDERED, $force_array = FALSE, $group = FALSE)
    {
        settype($params, 'array');
        if (count($params) > 0) {
            $prepared_query = $this->prepareQuery($query);

            if (MDB::isError($prepared_query)) {
                return($prepared_query);
            }
            $this->setParamArray($prepared_query, $params, $param_types);
            $result = $this->executeQuery($prepared_query, $types);
        } else {
            $result = $this->query($query, $types);
        }

        if (MDB::isError($result)) {
            return($result);
        }

        $all = $this->fetchAll($result, $fetchmode, TRUE, $force_array, $group);
        if (MDB::isError($all)) {
            return($all);
        }
        if (isset($prepared_query)) {
            $result = $this->freePreparedQuery($prepared_query);
            if (MDB::isError($result)) {
                return($result);
            }
        }
        return($all);
    }

    // }}}
    // {{{ getAll()

    /**
     * Fetch all the rows returned from a query.
     *
     * @param string $query the SQL query
     * @param array $types array that contains the types of the columns in
     *       the result set
     * @param array $params array if supplied, prepare/execute will be used
     *       with this array as execute parameters
     * @param array $param_types array that contains the types of the values
     *       defined in $params
     * @param integer $fetchmode the fetch mode to use
     * @return array an nested array, or a MDB error
     * @access public
     */
    function getAll($query, $types = NULL, $params = array(), $param_types = NULL, $fetchmode = MDB_FETCHMODE_DEFAULT)
    {
        settype($params, 'array');
        if (count($params) > 0) {
            $prepared_query = $this->prepareQuery($query);

            if (MDB::isError($prepared_query)) {
                return($prepared_query);
            }
            $this->setParamArray($prepared_query, $params, $param_types);
            $result = $this->executeQuery($prepared_query, $types);
        } else {
            $result = $this->query($query, $types);
        }

        if (MDB::isError($result)) {
            return($result);
        }

        $all = $this->fetchAll($result, $fetchmode);
        if (MDB::isError($all)) {
            return($all);
        }
        if (isset($prepared_query)) {
            $result = $this->freePreparedQuery($prepared_query);
            if (MDB::isError($result)) {
                return($result);
            }
        }
        return($all);
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
        return($this->raiseError(MDB_ERROR_NOT_CAPABLE));
    }

    // }}}
    // {{{ createLob()

    /**
     * Create a handler object of a specified class with functions to
     * retrieve data from a large object data stream.
     *
     * @param array $arguments An associative array with parameters to create
     *                  the handler object. The array indexes are the names of
     *                  the parameters and the array values are the respective
     *                  parameter values.
     *
     *                  Some parameters are specific of the class of each type
     *                  of handler object that is created. The following
     *                  parameters are common to all handler object classes:
     *
     *                  Type
     *
     *                      Name of the type of the built-in supported class
     *                      that will be used to create the handler object.
     *                      There are currently four built-in types of handler
     *                      object classes: data, resultlob, inputfile and
     *                      outputfile.
     *
     *                      The data handler class is the default class. It
     *                      simply reads data from a given data string.
     *
     *                      The resultlob handler class is meant to read data
     *                      from a large object retrieved from a query result.
     *                      This class is not used directly by applications.
     *
     *                      The inputfile handler class is meant to read data
     *                      from a file to use in prepared queries with large
     *                      object field parameters.
     *
     *                      The outputfile handler class is meant to write to
     *                      a file data from result columns with large object
     *                      fields. The functions to read from this type of
     *                      large object do not return any data. Instead, the
     *                      data is just written to the output file with the
     *                      data retrieved from a specified large object handle.
     *
     *                  Class
     *
     *                      Name of the class of the handler object that will be
     *                      created if the Type argument is not specified. This
     *                      argument should be used when you need to specify a
     *                      custom handler class.
     *
     *                  Database
     *
     *                      Database object as returned by MDB::connect.
     *                      This is an option argument needed by some handler
     *                      classes like resultlob.
     *
     *                  The following arguments are specific of the inputfile
     *                  handler class:
     *
     *                      File
     *
     *                          Integer handle value of a file already opened
     *                          for writing.
     *
     *                      FileName
     *
     *                          Name of a file to be opened for writing if the
     *                          File argument is not specified.
     *
     *                  The following arguments are specific of the outputfile
     *                  handler class:
     *
     *                      File
     *
     *                          Integer handle value of a file already opened
     *                          for writing.
     *
     *                      FileName
     *
     *                          Name of a file to be opened for writing if the
     *                          File argument is not specified.
     *
     *                      BufferLength
     *
     *                          Integer value that specifies the length of a
     *                          buffer that will be used to read from the
     *                          specified large object.
     *
     *                      LOB
     *
     *                          Integer handle value that specifies a large
     *                          object from which the data to be stored in the
     *                          output file will be written.
     *
     *                      Result
     *
     *                          Integer handle value as returned by the function
     *                          MDB::query() or MDB::executeQuery() that specifies
     *                          the result set that contains the large object value
     *                          to be retrieved. If the LOB argument is specified,
     *                          this argument is ignored.
     *
     *                      Row
     *
     *                          Integer value that specifies the number of the
     *                          row of the result set that contains the large
     *                          object value to be retrieved. If the LOB
     *                          argument is specified, this argument is ignored.
     *
     *                      Field
     *
     *                          Integer or string value that specifies the
     *                          number or the name of the column of the result
     *                          set that contains the large object value to be
     *                          retrieved. If the LOB argument is specified,
     *                          this argument is ignored.
     *
     *                      Binary
     *
     *                          Boolean value that specifies whether the large
     *                          object column to be retrieved is of binary type
     *                          (blob) or otherwise is of character type (clob).
     *                          If the LOB argument is specified, this argument
     *                          is ignored.
     *
     *                  The following argument is specific of the data
     *                  handler class:
     *
     *                  Data
     *
     *                      String of data that will be returned by the class
     *                      when it requested with the readLOB() method.
     *
     *                  The following argument is specific of the resultlob
     *                  handler class:
     *
     *                      ResultLOB
     *
     *                          Integer handle value of a large object result
     *                          row field.
     * @return integer handle value that should be passed as argument insubsequent
     * calls to functions that retrieve data from the large object input stream.
     * @access public
     */
    function createLob($arguments)
    {
        $result = $this->loadLob('Create LOB');
        if (MDB::isError($result)) {
            return($result);
        }
        $class_name = 'MDB_LOB';
        if (isset($arguments['Type'])) {
            switch ($arguments['Type']) {
                case 'data':
                    break;
                case 'resultlob':
                    $class_name = 'MDB_LOB_Result';
                    break;
                case 'inputfile':
                    $class_name = 'MDB_LOB_Input_File';
                    break;
                case 'outputfile':
                    $class_name = 'MDB_LOB_Output_File';
                    break;
                default:
                    if (isset($arguments['Error'])) {
                        $arguments['Error'] = $arguments['Type'] . ' is not a valid type of large object';
                    }
                    return($this->raiseError());
            }
        } else {
            if (isset($arguments['Class'])) {
                $class = $arguments['Class'];
            }
        }
        $lob = count($GLOBALS['_MDB_lobs']) + 1;
        $GLOBALS['_MDB_lobs'][$lob] = &new $class_name;
        if (isset($arguments['Database'])) {
            $GLOBALS['_MDB_lobs'][$lob]->database = $arguments['Database'];
        } else {
            $GLOBALS['_MDB_lobs'][$lob]->database = &$this;
        }
        if (MDB::isError($result = $GLOBALS['_MDB_lobs'][$lob]->create($arguments))) {
            $GLOBALS['_MDB_lobs'][$lob]->database->destroyLob($lob);
            return($result);
        }
        return($lob);
    }

    // }}}
    // {{{ readLob()

    /**
     * Read data from large object input stream.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB::createLob() method.
     * @param string $data reference to a variable that will hold data
     *                          to be read from the large object input stream
     * @param integer $length    value that indicates the largest ammount ofdata
     *                          to be read from the large object input stream.
     * @return mixed the effective number of bytes read from the large object
     *                      input stream on sucess or an MDB error object.
     * @access public
     * @see endOfLob()
     */
    function readLob($lob, &$data, $length)
    {
        return($GLOBALS['_MDB_lobs'][$lob]->readLob($data, $length));
    }

    // }}}
    // {{{ endOfLob()

    /**
     * Determine whether it was reached the end of the large object and
     * therefore there is no more data to be read for the its input stream.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB::createLob() method.
     * @access public
     * @return boolean flag that indicates whether it was reached the end of the large object input stream
     */
    function endOfLob($lob)
    {
        return($GLOBALS['_MDB_lobs'][$lob]->endOfLob());
    }

    // }}}
    // {{{ destroyLob()

    /**
     * Free any resources allocated during the lifetime of the large object
     * handler object.
     *
     * @param integer $lob argument handle that is returned by the
     *                          MDB::createLob() method.
     * @access public
     */
    function destroyLob($lob)
    {
        $GLOBALS['_MDB_lobs'][$lob]->destroy();
        unset($GLOBALS['_MDB_lobs'][$lob]);
    }

    // }}}
    // {{{ Destructor

    /**
    * this function closes open transactions to be executed at shutdown
    *
    * @access private
    */
    function _MDB_Common()
    {
        if ($this->in_transaction && !MDB::isError($this->rollback())) {
            $this->autoCommit(TRUE);
        }
    }
};
?>
