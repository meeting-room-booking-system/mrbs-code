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

MDB::loadFile('Date');

/**
 * Wrapper that makes MDB behave like Metabase
 *
 * @package MDB
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */

$lob_error = '';

function MetabaseSetupDatabase($arguments, &$database)
{
    _convertArguments($arguments, $dsninfo, $options);
    $db =& MDB::connect($dsninfo, $options);

    if (MDB::isError($db) || !is_object($db)) {
        $database = 0;
        return($db->getMessage());
    }
    $database = $db->database;
    return('');
}

function MetabaseSetupDatabaseObject($arguments, &$db)
{
    _convertArguments($arguments, $dsninfo, $options);
    $db =& MDB::connect($dsninfo, $options);

    if (MDB::isError($db) || !is_object($db)) {
        return($db->getMessage());
    }
    return('');
}

function _convertArguments($arguments, &$dsninfo, &$options)
{
    if (isset($arguments['Type'])) {
        $dsninfo['phptype'] = $arguments['Type'];
    }
    if (isset($arguments['User'])) {
        $dsninfo['username'] = $arguments['User'];
    }
    if(isset($arguments['Password'])) {
        $dsninfo['password'] = $arguments['Password'];
    }
    if(isset($arguments['Host'])) {
        $dsninfo['hostspec'] = $arguments['Host'];
    }
    if(isset($arguments['Options']['Port'])) {
       $dsninfo['port'] = $arguments['Options']['Port'];
       unset($arguments['Options']['Port']);
    }

    if (isset($arguments['Persistent'])) {
        $options['persistent'] = TRUE;
    }
    if(isset($arguments['Debug'])) {
        $options['debug'] = $arguments['Debug'];
    }
    if(isset($arguments['DecimalPlaces'])) {
        $options['decimal_places'] = $arguments['DecimalPlaces'];
    }
    if(isset($arguments['LOBBufferLength'])) {
        $options['LOBbufferlength'] = $arguments['LOBBufferLength'];
    }
    if(isset($arguments['LogLineBreak'])) {
        $options['loglinebreak'] = $arguments['LogLineBreak'];
    }

    $options['seqname_format'] = '_sequence_%s';
    if(isset($arguments['Options']) && is_array($arguments['Options'])) {
       $options = array_merge($options, $arguments['Options']);
    }
}

function MetabaseCloseSetup($database)
{
    global $_MDB_databases;

    $_MDB_databases[$database]->disconnect();
    unset($_MDB_databases[$database]);
}

function MetabaseQuery($database, $query)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->query($query);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('Query', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseQueryField($database, $query, &$field, $type = 'text')
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->queryOne($query, $type);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QueryField', $result->getMessage());
        return(0);
    } else {
        $field = $result;
        return(1);
    }
}

function MetabaseQueryRow($database, $query, &$row, $types = '')
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->queryRow($query, $types);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QueryRow', $result->getMessage());
        return(0);
    } else {
        $row = $result;
        return(1);
    }
}

function MetabaseQueryColumn($database, $query, &$column, $type = 'text')
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->queryCol($query, $type);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QueryColumn', $result->getMessage());
        return(0);
    } else {
        $column = $result;
        return(1);
    }
}

function MetabaseQueryAll($database, $query, &$all, $types = '')
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->queryAll($query, $types);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QueryAll', $result->getMessage());
        return(0);
    } else {
        $all = $result;
        return(1);
    }
}

function MetabaseReplace($database, $table, &$fields)
{
    global $_MDB_databases;
    for($count = count($fields), reset($fields), $field = 0;
        $field < $count;
        next($fields), $field++)
    {
        $name = key($fields);
        if(!isset($fields[$name]['Type'])) {
            $fields[$name]['Type'] = 'text';
        }
    }
    $result = $_MDB_databases[$database]->replace($table, $fields);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('Replace', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabasePrepareQuery($database, $query)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->prepareQuery($query);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('PrepareQuery', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFreePreparedQuery($database, $prepared_query)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->freePreparedQuery($prepared_query);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FreePreparedQuery', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseExecuteQuery($database, $prepared_query)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->executeQuery($prepared_query);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ExecuteQuery', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseQuerySet($database, $prepared_query, $parameter, $type, $value, $is_null = 0, $field = '')
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParam($prepared_query, $parameter, $type, $value, $is_null, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySet', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetNull($database, $prepared_query, $parameter, $type)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamNull($prepared_query, $parameter, $type);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetNull', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetText($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamText($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetText', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetCLOB($database, $prepared_query, $parameter, $value, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamClob($prepared_query, $parameter, $value, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetCLOB', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetBLOB($database, $prepared_query, $parameter, $value, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamBlob($prepared_query, $parameter, $value, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetBLOB', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetInteger($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamInteger($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetInteger', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetBoolean($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamBoolean($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetBoolean', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetDate($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamDate($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetDate(', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetTimestamp($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamTimestamp($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetTimestamp', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetTime($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamTime($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetTime', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetFloat($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamFloat($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetFloat', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseQuerySetDecimal($database, $prepared_query, $parameter, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setParamDecimal($prepared_query, $parameter, $value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('QuerySetDecimal', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseAffectedRows($database, &$affected_rows)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->affectedRows();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('AffectedRows', $result->getMessage());
        return(0);
    } else {
        $affected_rows = $result;
        return(1);
    }
}

function MetabaseFetchResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetch($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchCLOBResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchClob($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchCLOBResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchBLOBResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchBlob($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchBLOBResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseDestroyResultLOB($database, $lob)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->_destroyResultLob($lob);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DestroyResultLOB', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseEndOfResultLOB($database, $lob)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->endOfResultLob($lob);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('EndOfResultLOB', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseReadResultLOB($database, $lob, &$data, $length)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->_readResultLob($lob, $data, $length);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ReadResultLOB', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseResultIsNull($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->resultIsNull($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ResultIsNull', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchDateResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchDate($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchDateResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchTimestampResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchTimestamp($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchTimestampResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchTimeResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchTime($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchTimeResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchBooleanResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchBoolean($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchBooleanResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchFloatResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchFloat($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchFloatResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchDecimalResult($database, $result, $row, $field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchDecimal($result, $row, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchDecimalResult', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseFetchResultField($database, $result, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchOne($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchResultField', $result->getMessage());
        return(0);
    } else {
        $field = $result;
        return(1);
    }
}

function MetabaseFetchResultArray($database, $result, &$array, $row)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchInto($result, MDB_FETCHMODE_ORDERED, $row);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchResultArray', $result->getMessage());
        return(0);
    } else {
        $array = $result;
        return(1);
    }
}

function MetabaseFetchResultRow($database, $result, &$row)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchRow($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchResultRow', $result->getMessage());
        return(0);
    } else {
        $row = $result;
        return(1);
    }
}

function MetabaseFetchResultColumn($database, $result, &$column)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchCol($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchResultColumn', $result->getMessage());
        return(0);
    } else {
        $column = $result;
        return(1);
    }
}

function MetabaseFetchResultAll($database, $result, &$all)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->fetchAll($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FetchResultAll', $result->getMessage());
        return(0);
    } else {
        $all = $result;
        return(1);
    }
}

function MetabaseNumberOfRows($database, $result)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->numRows($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('NumberOfRows', $result->getMessage());
        return(0);
    } else {
       return($result);
    }
}

function MetabaseNumberOfColumns($database, $result)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->numCols($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('NumberOfColumns', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetColumnNames($database, $result, &$column_names)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getColumnNames($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetColumnNames', $result->getMessage());
        return(0);
    } else {
        $column_names = $result;
        return(1);
    }
}

function MetabaseSetResultTypes($database, $result, &$types)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setResultTypes($result, $types);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('SetResultTypes', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseFreeResult($database, $result)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->freeResult($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('FreeResult', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseError($database)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->error();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('Error', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseSetErrorHandler($database, $function)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setErrorHandler($function);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('SetErrorHandler', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseCreateDatabase($database, $name)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->createDatabase($name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('CreateDatabase', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseDropDatabase($database, $name)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->dropDatabase($name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DropDatabase', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseSetDatabase($database, $name)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setDatabase($name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('SetDatabase', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetIntegerFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getIntegerDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetIntegerFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetTextFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTextDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTextFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetCLOBFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getClobDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetCLOBFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetBLOBFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getBlobDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetBLOBFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetBooleanFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getBooleanDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetBooleanFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetDateFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getDateDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetDateFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetTimestampFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTimestampDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTimestampFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetTimeFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTimeDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTimeFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetFloatFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getFloatDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetFloatFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetDecimalFieldTypeDeclaration($database, $name, &$field)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getDecimalDeclaration($name, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetDecimalFieldTypeDeclaration', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetTextFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTextValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTextFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetBooleanFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getBooleanValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetBooleanFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetDateFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getDateValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetDateFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetTimestampFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTimestampValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTimestampFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetTimeFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTimeValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTimeFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetFloatFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getFloatValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetFloatFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseGetDecimalFieldValue($database, $value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getDecimalValue($value);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetDecimalFieldValue', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseSupport($database, $feature)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->support($feature);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('Support', $result->getMessage());
        return(0);
    } else {
       return($result);
    }
}

function MetabaseCreateTable($database, $name, &$fields)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->createTable($name, $fields);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('CreateTable', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseDropTable($database, $name)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->dropTable($name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DropTable', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseAlterTable($database, $name, &$changes, $check = 0)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->alterTable($name, $changes, $check);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('AlterTable', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseListTables($database, &$tables)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->listTables();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ListTables', $result->getMessage());
        return(0);
    } else {
        $tables = $result;
        return(1);
    }
}

function MetabaseListTableFields($database, $table, &$fields)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->listTableFields($table);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ListTableFields', $result->getMessage());
        return(0);
    } else {
        $fields = $result;
        return(1);
    }
}

function MetabaseGetTableFieldDefinition($database, $table, $field, &$definition)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTableFieldDefinition($table, $field);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTableFieldDefinition', $result->getMessage());
        return(0);
    } else {
        $definition = $result[0];
        return(1);
    }
}

function MetabaseCreateSequence($database, $name, $start)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->createSequence($name, $start);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('CreateSequence', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseDropSequence($database, $name)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->dropSequence($name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DropSequence', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseGetSequenceNextValue($database, $name, &$value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->nextId($name, FALSE);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetSequenceNextValue', $result->getMessage());
        return(0);
    } else {
        $value = $result;
        return(1);
    }
}

function MetabaseGetSequenceCurrentValue($database, $name, &$value)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->currId($name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetSequenceCurrentValue', $result->getMessage());
        return(0);
    } else {
        $value = $result;
        return(1);
    }
}

function MetabaseListSequences($database, &$sequences)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->listSequences();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ListSequences', $result->getMessage());
        return(0);
    } else {
        $sequences = $result;
        return(1);
    }
}

function MetabaseGetSequenceDefinition($database, $sequence, &$definition)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getSequenceDefinition($sequence);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetSequenceDefinition', $result->getMessage());
        return(0);
    } else {
        $definition = $result;
        return(1);
    }
}

function MetabaseAutoCommitTransactions($database, $auto_commit)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->autoCommit($auto_commit);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('AutoCommitTransactions', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseCommitTransaction($database)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->commit();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('CommitTransaction', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseRollbackTransaction($database)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->rollback();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('RollbackTransaction', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseCreateIndex($database, $table, $name, $definition)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->createIndex($table, $name, $definition);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('CreateIndex', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseDropIndex($database, $table, $name)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->dropIndex($table, $name);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DropIndex', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseListTableIndex($database, $table, &$index)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->listTableIndex($table);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('ListTableIndex', $result->getMessage());
        return(0);
    } else {
        $index = $result;
        return(1);
    }
}

function MetabaseGetTableIndexDefinition($database, $table, $index, &$definition)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->getTableIndexDefinition($table, $index);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('GetTableIndexDefinition', $result->getMessage());
        return(0);
    } else {
        $definition = $result;
        return(1);
    }
}

function MetabaseNow()
{
    return(MDB_Date::mdbNow());
}

function MetabaseToday()
{
    return(MDB_Date::mdbToday());
}

function MetabaseTime()
{
    return(MDB_Date::mdbTime());
}

function MetabaseSetSelectedRowRange($database, $first, $limit)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->setSelectedRowRange($first, $limit);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('SetSelectedRowRange', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseEndOfResult($database, $result)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->endOfResult($result);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('EndOfResult', $result->getMessage());
        return(0);
    } else {
       return($result);
    }
}

function MetabaseCaptureDebugOutput($database, $capture)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->captureDebugOutput($capture);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('CaptureDebugOutput', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseDebugOutput($database)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->debugOutput();
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DebugOutput', $result->getMessage());
        return(0);
    } else {
        return($result);
    }
}

function MetabaseDebug($database, $message)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->debug($message);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('Debug', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseShutdownTransactions()
{
    _shutdownTransactions();
}

function MetabaseDefaultDebugOutput($database, $message)
{
    global $_MDB_databases;
    $result = $_MDB_databases[$database]->defaultDebugOutput($_MDB_databases[$database], $message);
    if (MDB::isError($result)) {
        $_MDB_databases[$database]->setError('DefaultDebugOutput', $result->getMessage());
        return(0);
    } else {
        return(1);
    }
}

function MetabaseCreateLOB(&$arguments, &$lob)
{
    global $_MDB_databases;
    $args = $arguments;
    $args['Database'] = $_MDB_databases[$arguments['Database']];
    $result = $_MDB_databases[$arguments['Database']]->createLob($args);
    $args['Database'] = $arguments['Database']
    ;
    if (MDB::isError($result)) {
        global $lob_error;
        $lob_error = $result->getMessage();
        return(0);
    } else {
        $lob = $result;
        return(1);
    }
}

function MetabaseDestroyLOB($lob)
{
    global $_MDB_lobs;
    $result = $_MDB_lobs[$lob]->database->destroyLob($lob);
    if (MDB::isError($result)) {
        global $lob_error;
        $lob_error = $result->getMessage();
        return(0);
    } else {
        return(1);
    }
}

function MetabaseEndOfLOB($lob)
{
    global $_MDB_lobs;
    $result = $_MDB_lobs[$lob]->database->endOfLob($lob);
    if (MDB::isError($result)) {
        global $lob_error;
        $lob_error = $result->getMessage();
        return(0);
    } else {
        return($result);
    }
}

function MetabaseReadLOB($lob, &$data, $length)
{
    global $_MDB_lobs;
    $result = $_MDB_lobs[$lob]->database->readLob($lob, $data, $length);
    if (MDB::isError($result)) {
        global $lob_error;
        $lob_error = $result->getMessage();
        return(0);
    } else {
        return($result);
    }
}

function MetabaseLOBError($lob)
{
    global $lob_error;
    return($lob_error);
}

class metabase_manager_class
{
    var $MDB_manager_object;

    var $fail_on_invalid_names = 1;
    var $error = '';
    var $warnings = array();
    var $database = 0;
    var $database_definition = array(
        'name' => '',
        'create' => 0,
        'TABLES' => array()
    );

    function metabase_manager_class()
    {
        $this->MDB_manager_object =& new MDB_Manager;
        $this->MDB_manager_object->fail_on_invalid_names =& $this->fail_on_invalid_names;
        $this->MDB_manager_object->error =& $this->error;
        $this->MDB_manager_object->warnings =& $this->warnings;
        $this->MDB_manager_object->database_definition =& $this->database_definition;
    }

    function SetupDatabase(&$arguments)
    {
        _convertArguments($arguments, $dsninfo, $options);

        $result = $this->MDB_manager_object->connect($dsninfo, $options);
        if (MDB::isError($result)) {
            return($result->getMessage());
        }
        $this->database = $this->MDB_manager_object->database->database;
        return(1);
    }

    function CloseSetup()
    {
        $result = $this->MDB_manager_object->disconnect();
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function GetField(&$field, $field_name, $declaration, &$query)
    {
        if($declaration) {
            $result = $this->MDB_manager_object->database->getFieldDeclaration($field, $field_name, $declaration);
        } else {
            $result = $field_name;
        }
        if (MDB::isError($result)) {
            return(0);
        } else {
            $query = $result;
            return(1);
        }
    }

    function GetFieldList($fields, $declaration, &$query_fields)
    {
        if($declaration) {
            $result = $this->MDB_manager_object->database->getFieldDeclarationList($fields);
        } else {
            for(reset($fields), $i = 0;
                $field_number < count($fields);
                $i++, next($fields))
            {
                if ($i > 0) {
                    $query_fields .= ', ';
                }
                $result .= key($fields);
            }
        }
        if (MDB::isError($result)) {
            return(0);
        } else {
            $query_fields = $result;
            return(1);
        }
    }

    function GetFields($table, &$fields)
    {
        $result = $this->MDB_manager_object->database->getFieldDeclarationList($this->database_definition['TABLES'][$table]['FIELDS']);
        if (MDB::isError($result)) {
            return(0);
        } else {
            $fields = $result;
            return(1);
        }
    }

    function CreateTable($table_name, $table)
    {
        $result = $this->MDB_manager_object->_createTable($table_name, $table);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function DropTable($table_name)
    {
        $result = $this->MDB_manager_object->_dropTable($table_name);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function CreateSequence($sequence_name, $sequence, $created_on_table)
    {
        $result = $this->MDB_manager_object->createSequence($sequence_name, $sequence, $created_on_table);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function DropSequence($sequence_name)
    {
        $result = $this->MDB_manager_object->_dropSequence($sequence_name);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function CreateDatabase()
    {
        $result = $this->MDB_manager_object->_createDatabase();
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function AddDefinitionChange(&$changes, $definition, $item, $change)
    {
        $result = $this->MDB_manager_object->_addDefinitionChange($changes, $definition, $item, $change);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function CompareDefinitions(&$previous_definition, &$changes)
    {
        $result = $this->MDB_manager_object->_compareDefinitions($previous_definition);
        if (MDB::isError($result)) {
            return(0);
        } else {
            $changes = $result;
            return(1);
        }
    }

    function AlterDatabase(&$previous_definition, &$changes)
    {
        $result = $this->MDB_manager_object->_alterDatabase($previous_definition, $changes);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function EscapeSpecialCharacters($string)
    {
        $result = $this->MDB_manager_object->_escapeSpecialCharacters($string);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return($result);
        }
    }

    function DumpSequence($sequence_name, $output, $eol, $dump_definition)
    {
        $result = $this->MDB_manager_object->_dumpSequence($sequence_name, $output, $eol, $dump_definition);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function DumpDatabase($arguments)
    {
        $result = $this->MDB_manager_object->dumpDatabase($arguments);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function ParseDatabaseDefinitionFile($input_file, &$database_definition, &$variables, $fail_on_invalid_names = 1)
    {
        $result = $this->MDB_manager_object->parseDatabaseDefinitionFile($input_file, $variables, $fail_on_invalid_names);
        if (MDB::isError($result)) {
            return(0);
        } else {
            $database_definition = $result;
            return(1);
        }
    }

    function DumpDatabaseChanges(&$changes)
    {
        $result = $this->MDB_manager_object->_debugDatabaseChanges($changes);
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }

    function UpdateDatabase($current_schema_file, $previous_schema_file, &$arguments, &$variables)
    {
        _convertArguments($arguments, $dsninfo, $options);

        $result = $this->MDB_manager_object->connect($dsninfo, $options);
        if (MDB::isError($result)) {
            return($result);
        }

        $result = $this->MDB_manager_object->updateDatabase($current_schema_file, $previous_schema_file, $variables);
        if (MDB::isError($result)) {
            return($result->getMessage());
        }
        $this->database = $this->MDB_manager_object->database->database;
        return(1);
    }

    function DumpDatabaseContents($schema_file, &$setup_arguments, &$dump_arguments, &$variables)
    {
        $result = $this->MDB_manager_object->_dumpDatabaseContents($schema_file, $setup_arguments, $dump_arguments, $variables);
        if (MDB::isError($result)) {
            return(0);
        } else {
            $database_definition = $result;
            return($result);
        }
    }

    function GetDefinitionFromDatabase()
    {
        $result = $this->MDB_manager_object->getDefinitionFromDatabase();
        if (MDB::isError($result)) {
            return(0);
        } else {
            return(1);
        }
    }
};
?>
