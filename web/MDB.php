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
require_once('PEAR.php');

/**
 * The method mapErrorCode in each MDB_dbtype implementation maps
 * native error codes to one of these.
 *
 * If you add an error code here, make sure you also add a textual
 * version of it in MDB::errorMessage().
 */

define('MDB_OK',                         1);
define('MDB_ERROR',                     -1);
define('MDB_ERROR_SYNTAX',              -2);
define('MDB_ERROR_CONSTRAINT',          -3);
define('MDB_ERROR_NOT_FOUND',           -4);
define('MDB_ERROR_ALREADY_EXISTS',      -5);
define('MDB_ERROR_UNSUPPORTED',         -6);
define('MDB_ERROR_MISMATCH',            -7);
define('MDB_ERROR_INVALID',             -8);
define('MDB_ERROR_NOT_CAPABLE',         -9);
define('MDB_ERROR_TRUNCATED',          -10);
define('MDB_ERROR_INVALID_NUMBER',     -11);
define('MDB_ERROR_INVALID_DATE',       -12);
define('MDB_ERROR_DIVZERO',            -13);
define('MDB_ERROR_NODBSELECTED',       -14);
define('MDB_ERROR_CANNOT_CREATE',      -15);
define('MDB_ERROR_CANNOT_DELETE',      -16);
define('MDB_ERROR_CANNOT_DROP',        -17);
define('MDB_ERROR_NOSUCHTABLE',        -18);
define('MDB_ERROR_NOSUCHFIELD',        -19);
define('MDB_ERROR_NEED_MORE_DATA',     -20);
define('MDB_ERROR_NOT_LOCKED',         -21);
define('MDB_ERROR_VALUE_COUNT_ON_ROW', -22);
define('MDB_ERROR_INVALID_DSN',        -23);
define('MDB_ERROR_CONNECT_FAILED',     -24);
define('MDB_ERROR_EXTENSION_NOT_FOUND',-25);
define('MDB_ERROR_NOSUCHDB',           -26);
define('MDB_ERROR_ACCESS_VIOLATION',   -27);
define('MDB_ERROR_CANNOT_REPLACE',     -28);
define('MDB_ERROR_CONSTRAINT_NOT_NULL',-29);
define('MDB_ERROR_DEADLOCK',           -30);
define('MDB_ERROR_CANNOT_ALTER',       -31);
define('MDB_ERROR_MANAGER',            -32);
define('MDB_ERROR_MANAGER_PARSE',      -33);
define('MDB_ERROR_LOADMODULE',         -34);
define('MDB_ERROR_INSUFFICIENT_DATA',  -35);
/**
 * This is a special constant that tells DB the user hasn't specified
 * any particular get mode, so the default should be used.
 */

define('MDB_FETCHMODE_DEFAULT', 0);

/**
 * Column data indexed by numbers, ordered from 0 and up
 */

define('MDB_FETCHMODE_ORDERED',  1);

/**
 * Column data indexed by column names
 */

define('MDB_FETCHMODE_ASSOC',    2);

/**
 * For multi-dimensional results: normally the first level of arrays
 * is the row number, and the second level indexed by column number or name.
 * MDB_FETCHMODE_FLIPPED switches this order, so the first level of arrays
 * is the column name, and the second level the row number.
 */

define('MDB_FETCHMODE_FLIPPED',  4);

/**
 * These are constants for the tableInfo-function
 * they are bitwised or'ed. so if there are more constants to be defined
 * in the future, adjust MDB_TABLEINFO_FULL accordingly
 */

define('MDB_TABLEINFO_ORDER',      1);
define('MDB_TABLEINFO_ORDERTABLE', 2);
define('MDB_TABLEINFO_FULL',       3);

/**
 * These are constants for each of the supported datatypes
 */

define('MDB_TYPE_TEXT'      , 0);
define('MDB_TYPE_BOOLEAN'   , 1);
define('MDB_TYPE_INTEGER'   , 2);
define('MDB_TYPE_DECIMAL'   , 3);
define('MDB_TYPE_FLOAT'     , 4);
define('MDB_TYPE_DATE'      , 5);
define('MDB_TYPE_TIME'      , 6);
define('MDB_TYPE_TIMESTAMP' , 7);
define('MDB_TYPE_CLOB'      , 8);
define('MDB_TYPE_BLOB'      , 9);

/**
 * These are global variables that are used to track the various class instances
 */

$GLOBALS['_MDB_lobs'] = array();
$GLOBALS['_MDB_databases'] = array();

/**
 * The main 'MDB' class is simply a container class with some static
 * methods for creating DB objects as well as some utility functions
 * common to all parts of DB.
 *
 * The object model of DB is as follows (indentation means inheritance):
 *
 * MDB          The main DB class.  This is simply a utility class
 *              with some 'static' methods for creating MDB objects as
 *              well as common utility functions for other MDB classes.
 *
 * MDB_common   The base for each DB implementation.  Provides default
 * |            implementations (in OO lingo virtual methods) for
 * |            the actual DB implementations as well as a bunch of
 * |            query utility functions.
 * |
 * +-MDB_mysql  The DB implementation for MySQL. Inherits MDB_Common.
 *              When calling MDB::factory or MDB::connect for MySQL
 *              connections, the object returned is an instance of this
 *              class.
 * +-MDB_pgsql  The DB implementation for PostGreSQL. Inherits MDB_Common.
 *              When calling MDB::factory or MDB::connect for PostGreSQL
 *              connections, the object returned is an instance of this
 *              class.
 *
 * MDB_Date     This class provides several method to convert from and to
 *              MDB timestamps.
 *
 * MDB_Manager  This class handles the xml schema management.
 *
 * @package  MDB
 * @version  $Id$
 * @category Database
 * @author   Lukas Smith <smith@backendmedia.com>
 */
class MDB
{
    // }}}
    // {{{ setOptions()

    /**
     * set option array in an exiting database object
     *
     * @param   object  $db       MDB object
     * @param   mixed   $options  An associative array of option names and
     *                            their values.
     * @access  public
     */
    function setOptions(&$db, $options)
    {
        if(is_array($options)) {
            foreach($options as $option => $value) {
                $test = $db->setOption($option, $value);
                if(MDB::isError($test)) {
                    return $test;
                }
            }
        } else {
            $db->setOption('persistent', $options);
        }
        $include_lob = $db->getOption('includelob');
        if(!MDB::isError($include_lob) && $include_lob) {
            $db->loadLob('load at start');
        }
        $includemanager = $db->getOption('includemanager');
        if(!MDB::isError($includemanager) && $includemanager) {
            $db->loadManager('load at start');
        }
        $debug = $db->getOption('debug');
        if(!MDB::isError($debug) && $debug) {
            $db->captureDebugOutput(TRUE);
        }
    }

    // }}}
    // {{{ factory()

    /**
     * Create a new DB connection object for the specified database
     * type
     * IMPORTANT: In order for MDB to work properly it is necessary that
     * you make sure that you work with a reference of the original
     * object instead of a copy (this is a PHP4 quirk).
     *
     * For example:
     *     $mdb =& MDB::factory($dsn);
     *          ^^
     * And not:
     *     $mdb = MDB::factory($dsn);
     *          ^^
     *
     * @param   string  $type   database type, for example 'mysql'
     * @return  mixed   a newly created MDB connection object, or a MDB
     *                  error object on error
     * @access  public
     */
    function &factory($type)
    {
        $class_name = "MDB_$type";

        @include_once("MDB/${type}.php");

        @$db =& new $class_name;

        return($db);
    }

    // }}}
    // {{{ connect()

    /**
     * Create a new MDB connection object and connect to the specified
     * database
     *
     * IMPORTANT: In order for MDB to work properly it is necessary that
     * you make sure that you work with a reference of the original
     * object instead of a copy (this is a PHP4 quirk).
     *
     * For example:
     *     $mdb =& MDB::connect($dsn);
     *          ^^
     * And not:
     *     $mdb = MDB::connect($dsn);
     *          ^^
     *
     * @param   mixed   $dsn      'data source name', see the MDB::parseDSN
     *                            method for a description of the dsn format.
     *                            Can also be specified as an array of the
     *                            format returned by MDB::parseDSN.
     * @param   mixed   $options  An associative array of option names and
     *                            their values.
     * @return  mixed   a newly created MDB connection object, or a MDB
     *                  error object on error
     * @access  public
     * @see     MDB::parseDSN
     */
    function &connect($dsn, $options = FALSE)
    {
        $dsninfo = MDB::parseDSN($dsn);
        if(isset($dsninfo['phptype'])) {
            $type          = $dsninfo['phptype'];
            $class_name    = 'MDB_'.$type;
            $include       = 'MDB/'.$type.'.php';
        } else {
            return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND,
                NULL, NULL, 'no RDBMS driver specified',
                'MDB_Error', TRUE));
        }

        if(is_array($options)
            && isset($options['debug'])
            && $options['debug'] >= 2
        ) {
            // expose php errors with sufficient debug level
            @include_once($include);
        } else {
            include_once($include);
        }

        if(!class_exists($class_name)) {
            $error = PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND, NULL, NULL,
                'Unable to include the '.$include.' file', 'MDB_Error', TRUE);
            return($error);
        }

        @$db =& new $class_name();

        $db->setDSN($dsninfo);

        MDB::setOptions($db, $options);

        if(isset($dsninfo['database'])) {
            $err = $db->connect();
            if (MDB::isError($err)) {
                $dsn = $db->getDSN();
                $err->addUserInfo($dsn);
                return($err);
            }
        }
        return($db);
    }

    // }}}
    // {{{ connect()

    /**
     * Returns a MDB connection with the requested DSN.
     * A newnew MDB connection object is only created if no object with the
     * reuested DSN exists yet.
     *
     * IMPORTANT: In order for MDB to work properly it is necessary that
     * you make sure that you work with a reference of the original
     * object instead of a copy (this is a PHP4 quirk).
     *
     * For example:
     *     $mdb =& MDB::sngleton($dsn);
     *          ^^
     * And not:
     *     $mdb = MDB::singleton($dsn);
     *          ^^
     *
     * @param   mixed   $dsn      'data source name', see the MDB::parseDSN
     *                            method for a description of the dsn format.
     *                            Can also be specified as an array of the
     *                            format returned by MDB::parseDSN.
     * @param   mixed   $options  An associative array of option names and
     *                            their values.
     * @return  mixed   a newly created MDB connection object, or a MDB
     *                  error object on error
     * @access  public
     * @see     MDB::parseDSN
     */
    function &singleton($dsn = NULL, $options = FALSE)
    {
        if ($dsn) {
            $dsninfo = MDB::parseDSN($dsn);
            $dsninfo_default = array(
                'phptype' => NULL,
                'username' => NULL,
                'password' => NULL,
                'hostspec' => NULL,
                'database' => NULL,
            );
            $dsninfo = array_merge($dsninfo_default, $dsninfo);
            $keys = array_keys($GLOBALS['_MDB_databases']);
            for ($i=0, $j=count($keys); $i<$j; ++$i) {
                $tmp_dsn = $GLOBALS['_MDB_databases'][$keys[$i]]->getDSN('array');
                if ($dsninfo['phptype'] == $tmp_dsn['phptype']
                    && $dsninfo['username'] == $tmp_dsn['username']
                    && $dsninfo['password'] == $tmp_dsn['password']
                    && $dsninfo['hostspec'] == $tmp_dsn['hostspec']
                    && $dsninfo['database'] == $tmp_dsn['database'])
                {
                    MDB::setOptions($GLOBALS['_MDB_databases'][$keys[$i]], $options);
                    return $GLOBALS['_MDB_databases'][$keys[$i]];
                }
            }
        } else {
            if (is_array($GLOBALS['_MDB_databases'])
                && reset($GLOBALS['_MDB_databases'])
            ) {
                $db =& $GLOBALS['_MDB_databases'][key($GLOBALS['_MDB_databases'])];
                return $db;
            }
        }
        $db =& MDB::connect($dsn, $options);
        return $db;
    }

    // }}}
    // {{{ loadFile()

    /**
     * load a file (like 'Date.php' or 'Manager.php')
     *
     * @return $file    name of the file to be included from the MDB dir without
     *                  the '.php' extension (like 'Date' or 'Manager')
     * @access public
     */
    function loadFile($file)
    {
        @include_once('MDB/'.$file.'.php');
    }

    // }}}
    // {{{ apiVersion()

    /**
     * Return the MDB API version
     *
     * @return int     the MDB API version number
     * @access public
     */
    function apiVersion()
    {
        return(1);
    }

    // }}}
    // {{{ isError()

    /**
     * Tell whether a result code from a MDB method is an error
     *
     * @param   int       $value  result code
     * @return  boolean   whether $value is an MDB_Error
     * @access public
     */
    function isError($value)
    {
        return is_a($value, 'MDB_Error');
    }

    // }}}
    // {{{ isConnection()
    /**
     * Tell whether a value is a MDB connection
     *
     * @param mixed $value value to test
     *
     * @return bool whether $value is a MDB connection
     *
     * @access public
     */
    function isConnection($value)
    {
        return is_a($value, 'MDB_Common');
    }

    // }}}
    // {{{ isManip()

    /**
     * Tell whether a query is a data manipulation query (insert,
     * update or delete) or a data definition query (create, drop,
     * alter, grant, revoke).
     *
     * @param   string   $query the query
     * @return  boolean  whether $query is a data manipulation query
     * @access public
     */
    function isManip($query)
    {
        $manips = 'INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|
                  ALTER|GRANT|REVOKE|LOCK|UNLOCK|ROLLBACK|COMMIT';
        if (preg_match('/^\s*"?('.$manips.')\s+/i', $query)) {
            return(TRUE);
        }
        return(FALSE);
    }

    // }}}
    // {{{ errorMessage()

    /**
     * Return a textual error message for a MDB error code
     *
     * @param   int     $value error code
     * @return  string  error message, or false if the error code was
     *                  not recognized
     * @access public
     */
    function errorMessage($value)
    {
        static $errorMessages;
        if (!isset($errorMessages)) {
            $errorMessages = array(
                MDB_ERROR                    => 'unknown error',
                MDB_ERROR_ALREADY_EXISTS     => 'already exists',
                MDB_ERROR_CANNOT_CREATE      => 'can not create',
                MDB_ERROR_CANNOT_ALTER       => 'can not alter',
                MDB_ERROR_CANNOT_REPLACE     => 'can not replace',
                MDB_ERROR_CANNOT_DELETE      => 'can not delete',
                MDB_ERROR_CANNOT_DROP        => 'can not drop',
                MDB_ERROR_CONSTRAINT         => 'constraint violation',
                MDB_ERROR_CONSTRAINT_NOT_NULL=> 'null value violates not-null constraint',
                MDB_ERROR_DIVZERO            => 'division by zero',
                MDB_ERROR_INVALID            => 'invalid',
                MDB_ERROR_INVALID_DATE       => 'invalid date or time',
                MDB_ERROR_INVALID_NUMBER     => 'invalid number',
                MDB_ERROR_MISMATCH           => 'mismatch',
                MDB_ERROR_NODBSELECTED       => 'no database selected',
                MDB_ERROR_NOSUCHFIELD        => 'no such field',
                MDB_ERROR_NOSUCHTABLE        => 'no such table',
                MDB_ERROR_NOT_CAPABLE        => 'MDB backend not capable',
                MDB_ERROR_NOT_FOUND          => 'not found',
                MDB_ERROR_NOT_LOCKED         => 'not locked',
                MDB_ERROR_SYNTAX             => 'syntax error',
                MDB_ERROR_UNSUPPORTED        => 'not supported',
                MDB_ERROR_VALUE_COUNT_ON_ROW => 'value count on row',
                MDB_ERROR_INVALID_DSN        => 'invalid DSN',
                MDB_ERROR_CONNECT_FAILED     => 'connect failed',
                MDB_OK                       => 'no error',
                MDB_ERROR_NEED_MORE_DATA     => 'insufficient data supplied',
                MDB_ERROR_EXTENSION_NOT_FOUND=> 'extension not found',
                MDB_ERROR_NOSUCHDB           => 'no such database',
                MDB_ERROR_ACCESS_VIOLATION   => 'insufficient permissions',
                MDB_ERROR_MANAGER            => 'MDB_manager error',
                MDB_ERROR_MANAGER_PARSE      => 'MDB_manager schema parse error',
                MDB_ERROR_LOADMODULE         => 'Error while including on demand module',
                MDB_ERROR_TRUNCATED          => 'truncated',
                MDB_ERROR_DEADLOCK           => 'deadlock detected',
            );
        }

        if (MDB::isError($value)) {
            $value = $value->getCode();
        }

        return(isset($errorMessages[$value]) ?
           $errorMessages[$value] : $errorMessages[MDB_ERROR]);
    }

    // }}}
    // {{{ parseDSN()

    /**
     * Parse a data source name
     *
     * A array with the following keys will be returned:
     *  phptype: Database backend used in PHP (mysql, odbc etc.)
     *  dbsyntax: Database used with regards to SQL syntax etc.
     *  protocol: Communication protocol to use (tcp, unix etc.)
     *  hostspec: Host specification (hostname[:port])
     *  database: Database to use on the DBMS server
     *  username: User name for login
     *  password: Password for login
     *
     * The format of the supplied DSN is in its fullest form:
     *
     *  phptype(dbsyntax)://username:password@protocol+hostspec/database
     *
     * Most variations are allowed:
     *
     *  phptype://username:password@protocol+hostspec:110//usr/db_file.db
     *  phptype://username:password@hostspec/database_name
     *  phptype://username:password@hostspec
     *  phptype://username@hostspec
     *  phptype://hostspec/database
     *  phptype://hostspec
     *  phptype(dbsyntax)
     *  phptype
     *
     * @param   string  $dsn Data Source Name to be parsed
     * @return  array   an associative array
     * @access public
     * @author Tomas V.V.Cox <cox@idecnet.com>
     */
    function parseDSN($dsn)
    {
        if (is_array($dsn)) {
            return($dsn);
        }

        $parsed = array(
            'phptype'  => FALSE,
            'dbsyntax' => FALSE,
            'username' => FALSE,
            'password' => FALSE,
            'protocol' => FALSE,
            'hostspec' => FALSE,
            'port'     => FALSE,
            'socket'   => FALSE,
            'database' => FALSE
        );

        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== FALSE) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = NULL;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = (empty($arr[2])) ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (empty($dsn)) {
            return($parsed);
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== FALSE) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== FALSE) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec

        // $dsn => proto(proto_opts)/database
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            $proto       = $match[1];
            $proto_opts  = (!empty($match[2])) ? $match[2] : FALSE;
            $dsn         = $match[3];

        // $dsn => protocol+hostspec/database (old format)
        } else {
            if (strpos($dsn, '+') !== FALSE) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== FALSE) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = NULL;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if ($parsed['protocol'] == 'tcp') {
            if (strpos($proto_opts, ':') !== FALSE) {
                list($parsed['hostspec'], $parsed['port']) =
                                                     explode(':', $proto_opts);
            } else {
                $parsed['hostspec'] = $proto_opts;
            }
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if (!empty($dsn)) {
            // /database
            if (($pos = strpos($dsn, '?')) === FALSE) {
                $parsed['database'] = $dsn;
            // /database?param1=value1&param2=value2
            } else {
                $parsed['database'] = substr($dsn, 0, $pos);
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== FALSE) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) { // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return($parsed);
    }
}

/**
 * MDB_Error implements a class for reporting portable database error
 * messages.
 *
 * @package MDB
 * @category Database
 * @author  Stig Bakken <ssb@fast.no>
 */
class MDB_Error extends PEAR_Error
{

    // }}}
    // {{{ constructor

    /**
     * MDB_Error constructor.
     *
     * @param mixed   $code      MDB error code, or string with error message.
     * @param integer $mode      what 'error mode' to operate in
     * @param integer $level     what error level to use for
     *                           $mode & PEAR_ERROR_TRIGGER
     * @param smixed  $debuginfo additional debug info, such as the last query
     */
    function MDB_Error($code = MDB_ERROR, $mode = PEAR_ERROR_RETURN,
              $level = E_USER_NOTICE, $debuginfo = NULL)
    {
        if (is_int($code)) {
            $this->PEAR_Error('MDB Error: '.MDB::errorMessage($code), $code,
                $mode, $level, $debuginfo);
        } else {
            $this->PEAR_Error("MDB Error: $code", MDB_ERROR, $mode, $level,
                $debuginfo);
        }
    }
}
?>