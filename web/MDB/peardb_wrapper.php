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

/*
 * The method mapErrorCode in each MDB_dbtype implementation maps
 * native error codes to one of these.
 *
 * If you add an error code here, make sure you also add a textual
 * version of it in DB::errorMessage().
 */

define('DB_OK',                         MDB_OK);
define('DB_ERROR',                      MDB_ERROR);
define('DB_ERROR_SYNTAX',               MDB_ERROR_SYNTAX);
define('DB_ERROR_CONSTRAINT',           MDB_ERROR_CONSTRAINT);
define('DB_ERROR_NOT_FOUND',            MDB_ERROR_NOT_FOUND);
define('DB_ERROR_ALREADY_EXISTS',       MDB_ERROR_ALREADY_EXISTS);
define('DB_ERROR_UNSUPPORTED',          MDB_ERROR_UNSUPPORTED);
define('DB_ERROR_MISMATCH',             MDB_ERROR_MISMATCH);
define('DB_ERROR_INVALID',              MDB_ERROR_INVALID);
define('DB_ERROR_NOT_CAPABLE',          MDB_ERROR_NOT_CAPABLE);
define('DB_ERROR_TRUNCATED',            MDB_ERROR_TRUNCATED);
define('DB_ERROR_INVALID_NUMBER',       MDB_ERROR_INVALID_NUMBER);
define('DB_ERROR_INVALID_DATE',         MDB_ERROR_INVALID_DATE);
define('DB_ERROR_DIVZERO',              MDB_ERROR_DIVZERO);
define('DB_ERROR_NODBSELECTED',         MDB_ERROR_NODBSELECTED);
define('DB_ERROR_CANNOT_CREATE',        MDB_ERROR_CANNOT_CREATE);
define('DB_ERROR_CANNOT_DELETE',        MDB_ERROR_CANNOT_DELETE);
define('DB_ERROR_CANNOT_DROP',          MDB_ERROR_CANNOT_DROP);
define('DB_ERROR_NOSUCHTABLE',          MDB_ERROR_NOSUCHTABLE);
define('DB_ERROR_NOSUCHFIELD',          MDB_ERROR_NOSUCHFIELD);
define('DB_ERROR_NEED_MORE_DATA',       MDB_ERROR_NEED_MORE_DATA);
define('DB_ERROR_NOT_LOCKED',           MDB_ERROR_NOT_LOCKED);
define('DB_ERROR_VALUE_COUNT_ON_ROW',   MDB_ERROR_VALUE_COUNT_ON_ROW);
define('DB_ERROR_INVALID_DSN',          MDB_ERROR_INVALID_DSN);
define('DB_ERROR_CONNECT_FAILED',       MDB_ERROR_CONNECT_FAILED);
define('DB_ERROR_EXTENSION_NOT_FOUND',  MDB_ERROR_EXTENSION_NOT_FOUND);
define('DB_ERROR_ACCESS_VIOLATION',     MDB_ERROR_ACCESS_VIOLATION);
define('DB_ERROR_NOSUCHDB',             MDB_ERROR_NOSUCHDB);

define('DB_WARNING',           -1000);
define('DB_WARNING_READ_ONLY', -1001);

define('DB_PARAM_SCALAR',   MDB_PARAM_SCALAR);
define('DB_PARAM_OPAQUE',   MDB_PARAM_OPAQUE);
define('DB_PARAM_MISC',     MDB_PARAM_MISC);

define('DB_BINMODE_PASSTHRU',   MDB_BINMODE_PASSTHRU);
define('DB_BINMODE_RETURN',     MDB_BINMODE_RETURN);
define('DB_BINMODE_CONVERT',    MDB_BINMODE_CONVERT);

define('DB_FETCHMODE_DEFAULT',      MDB_FETCHMODE_DEFAULT);
define('DB_FETCHMODE_ORDERED',      MDB_FETCHMODE_ORDERED);
define('DB_FETCHMODE_ASSOC',        MDB_FETCHMODE_ASSOC);
define('DB_FETCHMODE_OBJECT',       3);
define('DB_FETCHMODE_FLIPPED',      MDB_FETCHMODE_FLIPPED);

define('DB_GETMODE_ORDERED', DB_FETCHMODE_ORDERED);
define('DB_GETMODE_ASSOC',   DB_FETCHMODE_ASSOC);
define('DB_GETMODE_FLIPPED', DB_FETCHMODE_FLIPPED);

define('DB_TABLEINFO_ORDER',        MDB_TABLEINFO_ORDER);
define('DB_TABLEINFO_ORDERTABLE',   MDB_TABLEINFO_ORDERTABLE);
define('DB_TABLEINFO_FULL',         MDB_TABLEINFO_FULL);

define('DB_AUTOQUERY_INSERT', 1);
define('DB_AUTOQUERY_UPDATE', 2);

/**
 * Wrapper that makes MDB behave like PEAR DB
 *
 * @package MDB
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class DB
{
    function &factory($type)
    {
        $db =& MDB::factory($type);
        if(PEAR::isError($db)) {
            return($db);
        }
        $obj =& new MDB_PEAR_PROXY($db);
        return($obj);
    }

    function &connect($dsn, $options = FALSE)
    {
        if (!is_array($options) && $options) {
            $options['persistent'] = TRUE;
        }
        $db =& MDB::connect($dsn, $options);
        if(PEAR::isError($db)) {
            return($db);
        }
        $obj =& new MDB_PEAR_PROXY($db);
        return($obj);
    }

    function apiVersion()
    {
        return(2);
    }

    function isError($value)
    {
        return(MDB::isError($value));
    }

    function isManip($query)
    {
        return(MDB::isManip($query));
    }

    function errorMessage($value)
    {
        return(MDB::errorMessage($value));
    }

    function parseDSN($dsn)
    {
        return(MDB::parseDSN($dsn));
    }

    function assertExtension($name)
    {
        if (!extension_loaded($name)) {
            $dlext = OS_WINDOWS ? '.dll' : '.so';
            @dl($name . $dlext);
        }
        return extension_loaded($name);
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
class DB_Error extends PEAR_Error
{
    function DB_Error($code = DB_ERROR, $mode = PEAR_ERROR_RETURN,
              $level = E_USER_NOTICE, $debuginfo = NULL)
    {
        if (is_int($code)) {
            $this->PEAR_Error('DB Error: ' . DB::errorMessage($code), $code, $mode, $level, $debuginfo);
        } else {
            $this->PEAR_Error("DB Error: $code", DB_ERROR, $mode, $level, $debuginfo);
        }
    }
}

/**
 * Wrapper that makes MDB behave like PEAR DB
 *
 * @package MDB
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class DB_result
{
    var $dbh;
    var $result;
    var $row_counter = NULL;

    var $limit_from  = NULL;

    var $limit_count = NULL;

    function DB_result(&$dbh, $result)
    {
        $this->dbh = &$dbh;
        $this->result = $result;
    }

    function fetchRow($fetchmode = DB_FETCHMODE_DEFAULT, $rownum = 0)
    {
        $arr = $this->dbh->fetchInto($this->result, $fetchmode, $rownum);
        if(is_array($arr)) {
            return($arr);
        }
        else {
            return(NULL);
        }
    }

    function fetchInto(&$arr, $fetchmode = DB_FETCHMODE_DEFAULT, $rownum = 0)
    {
        $arr = $this->dbh->fetchInto($this->result, $fetchmode, $rownum);
        if(MDB::isError($arr)) {
            return($arr);
        }
        if($arr === NULL) {
            return($arr);
        }
        return(DB_OK);
    }

    function numCols()
    {
        return($this->dbh->numCols($this->result));
    }

    function numRows()
    {
        return($this->dbh->numRows($this->result));
    }

    function nextResult()
    {
        return($this->dbh->nextResult($this->result));
    }

    function free()
    {
        $err = $this->dbh->freeResult($this->result);
        if(MDB::isError($err)) {
            return($err);
        }
        $this->result = FALSE;
        return(TRUE);
    }

    function tableInfo($mode = NULL)
    {
        return($this->dbh->tableInfo($this->result, $mode));
    }

    function getRowCounter()
    {
        return($this->dbh->highest_fetched_row[$this->result]);
    }
}

class DB_row
{
    function DB_row(&$arr)
    {
        for (reset($arr); $key = key($arr); next($arr)) {
            $this->$key = &$arr[$key];
        }
    }
}

class MDB_PEAR_PROXY
{
    var $MDB_object;

    function MDB_PEAR_PROXY($MDB_object)
    {
        $this->MDB_object = $MDB_object;
        $this->MDB_object->sequence_prefix = '_seq_';
    }

    function connect($dsninfo, $persistent = FALSE)
    {
        return($this->MDB_object->connect($dsninfo, $persistent));
    }

    function disconnect()
    {
        return($this->MDB_object->disconnect());
    }

    function quoteString($string)
    {
        $string = $this->_quote($string);
        if ($string{0} == "'") {
            return substr($string, 1, -1);
        }
        return($string);
    }

    function quote($string)
    {
        if ($string === NULL) {
            return 'NULL';
        }
        return($this->MDB_object->_quote($string));
    }

    function provides($feature)
    {
        return($this->MDB_object->support($feature));
    }

    function errorCode($nativecode)
    {
        return($this->MDB_object->errorCode($nativecode));
    }

    function errorMessage($dbcode)
    {
        return($this->MDB_object->errorMessage($dbcode));
    }

    function &raiseError($code = DB_ERROR, $mode = NULL, $options = NULL,
                         $userinfo = NULL, $nativecode = NULL)
    {
        return($this->MDB_object->raiseError($code = DB_ERROR, $mode, $options, $userinfo, $nativecode));
    }

    function setFetchMode($fetchmode, $object_class = NULL)
    {
        return($this->MDB_object->setFetchMode($fetchmode, $object_class));
    }

    function setOption($option, $value)
    {
        return($this->MDB_object->setOption($option, $value));
    }

    function getOption($option)
    {
        return($this->MDB_object->getOption($option));
    }

    function prepare($query)
    {
        return($this->MDB_object->prepareQuery($query));
    }

    function autoPrepare($table, $table_fields, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $query = $this->buildManipSQL($table, $table_fields, $mode, $where);
        return($this->prepare($query));
    }

    function autoExecute($table, $fields_values, $mode = DB_AUTOQUERY_INSERT, $where = false)
    {
        $sth = $this->autoPrepare($table, array_keys($fields_values), $mode, $where);
        return($this->execute($sth, array_values($fields_values)));
    }

    function buildManipSQL($table, $table_fields, $mode, $where = false)
    {
        if (count($table_fields) == 0) {
            $this->raiseError(DB_ERROR_NEED_MORE_DATA);
        }
        $first = true;
        switch ($mode) {
            case DB_AUTOQUERY_INSERT:
                $values = '';
                $names = '';
                while (list(, $value) = each($table_fields)) {
                    if ($first) {
                        $first = false;
                    } else {
                        $names .= ',';
                        $values .= ',';
                    }
                    $names .= $value;
                    $values .= '?';
                }
                return "INSERT INTO $table ($names) VALUES ($values)";
                break;
            case DB_AUTOQUERY_UPDATE:
                $set = '';
                while (list(, $value) = each($table_fields)) {
                    if ($first) {
                        $first = false;
                    } else {
                        $set .= ',';
                    }
                    $set .= "$value = ?";
                }
                $sql = "UPDATE $table SET $set";
                if ($where) {
                    $sql .= " WHERE $where";
                }
                return($sql);
                break;
            default:
                $this->raiseError(DB_ERROR_SYNTAX);
        }
    }

    function execute($stmt, $data = FALSE)
    {
        $result = $this->MDB_object->execute($stmt, NULL, $data);
        if (MDB::isError($result) || $result === DB_OK) {
            return($result);
        } else {
            return new DB_result($this->MDB_object, $result);
        }
    }

    function executeMultiple( $stmt, &$data )
    {
        return($this->MDB_object->executeMultiple($stmt, NULL, $data));
    }

    function &query($query, $params = array()) {
        if (sizeof($params) > 0) {
            $sth = $this->MDB_object->prepare($query);
            if (MDB::isError($sth)) {
                return($sth);
            }
            return($this->MDB_object->execute($sth, $params));
        } else {
            $result = $this->MDB_object->query($query);
            if (MDB::isError($result) || $result === DB_OK) {
                return($result);
            } else {
                return new DB_result($this->MDB_object, $result);
            }
        }
    }

    function simpleQuery($query) {
        return($this->MDB_object->query($query));
    }

    function limitQuery($query, $from, $count)
    {
        $result = $this->MDB_object->limitQuery($query, NULL, $from, $count);
        if (MDB::isError($result) || $result === DB_OK) {
            return($result);
        } else {
            return new DB_result($this->MDB_object, $result);
        }
    }

    function &getOne($query, $params = array())
    {
        return($this->MDB_object->getOne($query, NULL, $params));
    }

    function &getRow($query,
                     $params = NULL,
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        return($this->MDB_object->getRow($query, NULL, $params, NULL, $fetchmode));
    }

    function &getCol($query, $col = 0, $params = array())
    {
        return($this->MDB_object->getCol($query, NULL, $params, NULL, $col));
    }

    function &getAssoc($query, $force_array = FALSE, $params = array(),
                       $fetchmode = DB_FETCHMODE_ORDERED, $group = FALSE)
    {
        return($this->MDB_object->getAssoc($query, NULL, $params, NULL, $fetchmode, $force_array, $group));
    }

    function &getAll($query,
                     $params = NULL,
                     $fetchmode = DB_FETCHMODE_DEFAULT)
    {
        return($this->MDB_object->getAll($query, NULL, $params, NULL, $fetchmode));
    }

    function autoCommit($onoff = FALSE)
    {
        return($this->MDB_object->autoCommit($onoff));
    }

    function commit()
    {
        return($this->MDB_object->commit());
    }

    function rollback()
    {
        return($this->MDB_object->rollback());
    }

    function numRows($result)
    {
        return($this->MDB_object->numRows($result));
    }

    function affectedRows()
    {
        return($this->MDB_object->affectedRows());
    }

    function errorNative()
    {
        return($this->MDB_object->errorNative());
    }

    function nextId($seq_name, $ondemand = TRUE)
    {
        return($this->MDB_object->nextId($seq_name, $ondemand));
    }

    function createSequence($seq_name)
    {
        return($this->MDB_object->createSequence($seq_name, 1));
    }

    function dropSequence($seq_name)
    {
        return($this->MDB_object->dropSequence($seq_name));
    }

    function tableInfo($result, $mode = NULL)
    {
        return($this->MDB_object->tableInfo($result, $mode));
    }

    function getTables()
    {
        return($this->getListOf('tables'));
    }

    function getListOf($type)
    {
        switch ($type) {
            case 'tables':
                return($this->MDB_object->listTables());
            case 'views':
                return($this->MDB_object->listViews());
            case 'users':
                return($this->MDB_object->listUsers());
            case 'functions':
                return($this->MDB_object->listFunctions());
            case 'databases':
                return($this->MDB_object->listDatabases());
            default:
                return($this->raiseError(DB_ERROR_UNSUPPORTED));
        }
    }
}
?>
