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
// | Original QuerySim Concept & ColdFusion Author: Hal Helms             |
// | <hal.helms@teamallaire.com>                                          |
// | Bert Dawson <bdawson@redbanner.com>                                  |
// +----------------------------------------------------------------------+
// | Original PHP Author: Alan Richmond <arichmond@bigfoot.com>           |
// | David Huyck <b@bombusbee.com>                                        |
// +----------------------------------------------------------------------+
// | Special note concerning code documentation:                          |
// | QuerySim was originally created for use during development of        |
// | applications built using the Fusebox framework. (www.fusebox.org)    |
// | Fusebox uses an XML style of documentation called Fusedoc. (Which    |
// | is admittedly not well suited to documenting classes and functions.  |
// | This short-coming is being addressed by the Fusebox community.) PEAR |
// | uses a Javadoc style of documentation called PHPDoc. (www.phpdoc.de) |
// | Since this class extension spans two groups of users, it is asked    |
// | that the members of each respect the documentation standard of the   |
// | other.  So it is a further requirement that both documentation       |
// | standards be included and maintained. If assistance is required      |
// | please contact Alan Richmond.                                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

/*
<fusedoc fuse="querysim.php" language="PHP">
    <responsibilities>
        I take information and turn it into a recordset that can be accessed
        through the PEAR MDB API.  Based on Hal Helms' QuerySim.cfm ColdFusion
        custom tag available at halhelms.com.
    </responsibilities>
    <properties>
        <property name="API" value="PEAR MDB" />
        <property name="version" value="0.2.1" />
        <property name="status" value="beta" />
        <history author="Hal Helms" email="hal.helms@teamallaire.com" type="Create" />
        <history author="Bert Dawson" email="bdawson@redbanner.com" type="Update">
            Extensive revision that is backwardly compatible but eliminates the
            need for a separate .sim file.
        </history>
        <history author="Alan Richmond" email="arichmond@bigfoot.com" type="Create" date="10-July-2002">
            Rewrote in PHP as an extention to the PEAR DB API.
            Functions supported:
                connect, disconnect, query, fetchRow, fetchInto, freeResult,
                numCols, numRows, getSpecialQuery
            David Huyck (bombusbee.com) added ability to escape special
                characters (i.e., delimiters) using a '\'.
            Extended PEAR DB options[] for adding incoming parameters.  Added
                options:  columnDelim, dataDelim, eolDelim
        </history>
        <history author="David Huyck" email="b@bombusbee.com" type="Update" date="19-July-2002">
            Added the ability to set the QuerySim options at runtime.
            Default options are:
                'columnDelim' => ',',            // Commas split the column names
                'dataDelim'   => '|',            // Pipes split the data fields
                'eolDelim'    => chr(13).chr(10) // Carriage returns split the
                                                 // lines of data
            Affected functions are:
                DB_querysim():          set the default options when the
                                        constructor method is called
                _parseQuerySim($query): altered the parsing of lines, column
                                        names, and data fields
                _empty2null:            altered the way this function is called
                                        to simplify calling it
        </history>
        <history author="Alan Richmond" email="arichmond@bigfoot.com" type="Update" date="24-July-2002">
            Added error catching for malformed QuerySim text.
            Bug fix _empty2null():  altered version was returning unmodified
                                    lineData.
            Cleanup:
                PEAR compliant formatting, finished PHPDocs and added 'out' to
                Fusedoc 'io'.
                Broke up _parseQuerySim() into _buildResult() and _parseOnDelim()
                to containerize duplicate parse code.
        </history>
        <history author="David Huyck" email="b@bombusbee.com" type="Update" date="25-July-2002">
            Edited the _buildResult() and _parseOnDelim() functions to improve
            reliability of special character escaping.
            Re-introduced a custom setOption() method to throw an error when a
            person tries to set one of the delimiters to '\'.
        </history>
        <history author="Alan Richmond" email="arichmond@bigfoot.com" type="Update" date="27-July-2002">
            Added '/' delimiter param to preg_quote() in _empty2null() and
            _parseOnDelim() so '/' can be used as a delimiter.
            Added error check for columnDelim == eolDelim or dataDelim == eolDelim.
            Renamed some variables for consistancy.
        </history>
        <history author="Alan Richmond" email="arichmond@bigfoot.com" type="Update" date="30-July-2002">
            Removed private function _empty2null().  Turns out preg_split()
            deals with empty elemants by making them zero length strings, just
            what they ended up being anyway.  This should speed things up a little.
            Affected functions:
                _parseOnDelim()     perform trim on line here, instead of in
                                    _empty2null().
                _buildResult()      remove call to _empty2null().
                _empty2null()       removed function.
        </history>
        <history author="Alan Richmond" email="arichmond@bigfoot.com" type="Update" date="1-Jan-2003">
            Ported to PEAR MDB.
            Methods supported:
                connect, query, getColumnNames, numCols, endOfResult, fetch,
                numRows, freeResult, fetchInto, nextResult, setSelectedRowRange
                (inherited).
        </history>
        <history
            Removed array_change_key_case() work around for <4.2.0 in
            getColumnNames(), found it already done in MDB/Common.php.
        </history>
        <history author="Alan Richmond" email="arichmond@bigfoot.com" type="Update" date="3-Feb-2003">
            Changed default eolDelim to a *nix file eol, since we're trimming
            the result anyway, it makes no difference for Windows.  Now only
            Mac file eols should need to be set (and other kinds of chars).
        </history>
        <note author="Alan Richmond">
            Got WAY too long.  See querysim_readme.txt for instructions and some
            examples.
            io section only documents elements of DB_result that DB_querysim uses,
            adds or changes; see MDB and MDB_Common for more info.
            io section uses some elements that are not Fusedoc 2.0 compliant:
            object and resource.
        </note>
    </properties>
    <io>
        <in>
            <file path="MDB/Common.php" action="require_once" />
        </in>
        <out>
            <object name="MDB_querysim" extends="MDB_Common" instantiatedby="MDB::connect()">
                <resource type="file" name="connection" oncondition="source is external file" scope="class" />
                <string name="phptype" default="querysim" />
                <string name="dbsyntax" default="querysim" />
                <array name="supported" comments="most of these don't actually do anything, they are enabled to simulate the option being available if checked">
                    <boolean name="Sequences" default="true" />
                    <boolean name="Indexes" default="true" />
                    <boolean name="AffectedRows" default="true" />
                    <boolean name="Summaryfunctions" default="true" />
                    <boolean name="OrderByText" default="true" />
                    <boolean name="CurrId" default="true" />
                    <boolean name="SelectRowRanges" default="true" comments="this one is functional" />
                    <boolean name="LOBs" default="true" />
                    <boolean name="Replace" default="true" />
                    <boolean name="SubSelects" default="true" />
                    <boolean name="Transactions" default="true" />
                </array>
                <string name="last_query" comments="last value passed in with query()" />
                <array name="options" comments="these can be changed at run time">
                    <string name="columnDelim" default="," />
                    <string name="dataDelim" default="|" />
                    <string name="eolDelim" default="chr(13).chr(10)" />
                </array>
            </object>
            <array name="result" comments="the simulated record set returned by ::query()">
                <array comments="columns">
                    <string comments="column name" />
                </array>
                <array comments="data">
                    <array comments="row">
                        <string comments="data element" />
                    </array>
                </array>
            </array>
        </out>
    </io>
</fusedoc>
*/

require_once 'MDB/Common.php';

/**
 * MDB QuerySim driver
 *
 * @package MDB
 * @category Database
 * @author  Alan Richmond <arichmond@bigfoot.com>
 */
class MDB_querysim extends MDB_Common
{
// Most of the class variables are taken from the corresponding Metabase driver.
// Few are taken from the corresponding PEAR DB driver.
// Some are MDB specific.
    var $connection = 0;
    var $connected_host;
    var $connected_user;
    var $connected_password;
    var $connected_port;
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
    function MDB_querysim()
    {
        $this->MDB_Common();
        $this->phptype  = 'querysim';
        $this->dbsyntax = 'querysim';
        
        // Most of these are dummies to simulate availability if checked
        $this->supported['Sequences'] = 1;
        $this->supported['Indexes'] = 1;
        $this->supported['AffectedRows'] = 1;
        $this->supported['Summaryfunctions'] = 1;
        $this->supported['OrderByText'] = 1;
        $this->supported['CurrId'] = 1;
        $this->supported['SelectRowRanges'] = 1;// this one is real
        $this->supported['LOBs'] = 1;
        $this->supported['Replace'] = 1;
        $this->supported['SubSelects'] = 1;
        $this->supported['Transactions'] = 1;
        
        // init QuerySim options
        $querySimOptions = array(
            'columnDelim' => ',',
            'dataDelim'   => '|',
            'eolDelim'    => "\n"
        );
        // let runtime options overwrite defaults
        $this->options = array_merge($querySimOptions, $this->options);
    }
    // }}}

    // {{{ connect()

    /**
     * Open a file or simulate a successful database connect
     *
     * @param string $dsn the data source name (see MDB::parseDSN for syntax)
     * @param mixed $persistent (optional) boolean whether the connection should
     *        be persistent (default FALSE) or assoc array of config options
     *
     * @access public
     *
     * @return mixed MDB_OK string on success, a MDB error object on failure
     */
    function connect()
    {
        if($this->connection != 0) {
            if (!strcmp($this->selected_database, $this->database_name)
                && ($this->opened_persistent == $this->options['persistent']))
            {
                return MDB_OK;
            }
            if ($this->selected_database) {
                $this->_close($this->connection);
            }
            $this->connection = 0;
        }
        if(is_array($this->options)) {
            foreach($this->options as $option => $value) {
                if((in_array($option, array('columnDelim','dataDelim','eolDelim')))
                    && ($value == '\\')) {
                        return $this->raiseError(MDB_ERROR, null, null,
                            "MDB Error: option $option cannot be set to '\\'");
                }
            }
        }
        $connection = 1;// sim connect
        // if external, check file...
        if ($this->database_name) {
            $file = $this->database_name;
            if (!file_exists($file)) {
                return $this->raiseError(MDB_ERROR_NOT_FOUND, null, null, 'file not found');
            }
            if (!is_file($file)) {
                return $this->raiseError(MDB_ERROR_INVALID, null, null, 'not a file');
            }
            if (!is_readable($file)) {
                return $this->raiseError(MDB_ERROR_ACCESS_VIOLATION, null, null,
                    'could not open file - check permissions');
            }
            // ...and open if persistent
            if ($this->options['persistent']) {
                $connection = @fopen($file, 'r');
            }
        }
        $this->connection = $connection;
        $this->selected_database = $this->database_name;
        $this->opened_persistent = $this->options['persistent'];
        return MDB_OK;
    }
    // }}}

    // {{{ _close()
    
    /**
     * Close a file or simulate a successful database disconnect
     *
     * @access public
     *
     * @return bool TRUE on success, FALSE if file closed.
     *              Always TRUE if simulated.
     */
    function _close()
    {
        $ret = true;
        if ($this->connection != 0) {
            if (($this->opened_persistent) && (is_resource($this->connection))) {
                echo 'closed file';
                $ret = @fclose($this->connection);
            }
            $this->connection = 0;
            unset($GLOBALS['_MDB_databases'][$this->database]);
        }
        return $ret;
    }
    // }}}

    // {{{ setOption()
    
    /**
    * Set the option for the MDB class
    *
    * @param string $option option name
    * @param mixed  $value value for the option
    *
    * @return mixed MDB_OK or MDB_Error
    */
    function setOption($option, $value)
    {
        if ((in_array($option, array('columnDelim','dataDelim','eolDelim')))
            && ($value == '\\')
        ) {
            return $this->raiseError("option $option cannot be set to '\\'");
        }
        if (isset($this->options[$option])) {
            $this->options[$option] = $value;
            return MDB_OK;
        }
        return $this->raiseError("unknown option $option");
    }
    // }}}

    // {{{ query()
    
    /**
     * Get QuerySim text from appropriate source and return
     * the parsed text.
     *
     * @param string The QuerySim text
     * @param mixed   $types  array that contains the types of the columns in
     *                        the result set
     *
     * @access public
     *
     * @return mixed Simulated result set as a multidimentional
     * array if valid QuerySim text was passed in.  A MDB error
     * is returned on failure.
     */
    function query($query, $types = null)
    {
        if ($this->database_name) {
            $query = $this->_readFile();
        }
        
        $this->debug("Query: $query");
        $ismanip = false;
        
        $first = $this->first_selected_row;
        $limit = $this->selected_row_limit;
        $this->last_query = $query;
        
        if ($result = $this->_buildResult($query)) {
            if ($types != null) {
                if (!is_array($types)) {
                    $types = array($types);
                }
                if (MDB::isError($err = $this->setResultTypes($result, $types))) {
                    $this->freeResult($result);
                    return $err;
                }
            }
            if ($limit > 0) {
                $result[1] = array_slice($result[1], $first-1, $limit);
            }
            $this->highest_fetched_row[$this->_querySimSignature($result)] = -1;
            
            return $result;
        }
        return $this->raiseError();
    }

    // }}}
    
    // {{{ _readFile()
    
    /**
     * Read an external file
     *
     * @param string filepath/filename
     *
     * @access private
     *
     * @return string the contents of a file
     */
    function _readFile()
    {
        $buffer = '';
        if ($this->opened_persistent) {
            while (!feof($this->connection)) {
                $buffer .= fgets($this->connection, 1024);
            }
        } else {
            $this->connection = @fopen($this->selected_database, 'r');
            while (!feof($this->connection)) {
                $buffer .= fgets($this->connection, 1024);
            }
            $this->connection = @fclose($this->connection);
        }
        return $buffer;
    }
    // }}}

    // {{{ _buildResult()
    
    /**
     * Convert QuerySim text into an array
     *
     * @param string Text of simulated query
     *
     * @access private
     *
     * @return multi-dimensional array containing the column names and data
     *                                 from the QuerySim
     */
    function _buildResult($query)
    {
        $eolDelim    = $this->options['eolDelim'];
        $columnDelim = $this->options['columnDelim'];
        $dataDelim   = $this->options['dataDelim'];
        
        $columnNames = array();
        $data        = array();
        
        if ($columnDelim == $eolDelim) {
            return $this->raiseError(MDB_ERROR_INVALID, null, null,
                'columnDelim and eolDelim must be different');
        } elseif ($dataDelim == $eolDelim){
            return $this->raiseError(MDB_ERROR_INVALID, null, null,
                'dataDelim and eolDelim must be different');
        }
        
        $query = trim($query);
        //tokenize escaped slashes
        $query = str_replace('\\\\', '[$double-slash$]', $query);
        
        if (!strlen($query)) {
            return $this->raiseError(MDB_ERROR_SYNTAX, null, null,
                'empty querysim text');
        }
        $lineData = $this->_parseOnDelim($query, $eolDelim);
        //kill the empty last row created by final eol char if it exists
        if (!strlen(trim($lineData[count($lineData) - 1]))) {
            unset($lineData[count($lineData) - 1]);
        }
        //populate columnNames array
        $thisLine = each($lineData);
        $columnNames = $this->_parseOnDelim($thisLine[1], $columnDelim);
        if ((in_array('', $columnNames)) || (in_array('null', $columnNames))) {
            return $this->raiseError(MDB_ERROR_SYNTAX, null, null,
                'all column names must be defined');
        }
        //replace double-slash tokens with single-slash
        $columnNames = str_replace('[$double-slash$]', '\\', $columnNames);
        $columnCount = count($columnNames);
        $rowNum = 0;
        //loop through data lines
        if (count($lineData) > 1) {
            while ($thisLine = each($lineData)) {
                $thisData = $this->_parseOnDelim($thisLine[1], $dataDelim);
                $thisDataCount = count($thisData);
                if ($thisDataCount != $columnCount) {
                    $fileLineNo = $rowNum + 2;
                    return $this->raiseError(MDB_ERROR_SYNTAX, null, null,
                        "number of data elements ($thisDataCount) in line $fileLineNo not equal to number of defined columns ($columnCount)");
                }
                //loop through data elements in data line
                foreach ($thisData as $thisElement) {
                    if (strtolower($thisElement) == 'null'){
                        $thisElement = '';
                    }
                    //replace double-slash tokens with single-slash
                    $data[$rowNum][] = str_replace('[$double-slash$]', '\\', $thisElement);
                }//end foreach
                ++$rowNum;
            }//end while
        }//end if
        return array($columnNames, $data);
    }//end function _buildResult()
    // }}}

    // {{{ _parseOnDelim()
    
    /**
     * Split QuerySim string into an array on a delimiter
     *
     * @param string $thisLine Text of simulated query
     * @param string $delim    The delimiter to split on
     *
     * @access private
     *
     * @return array containing parsed string
     */
    function _parseOnDelim($thisLine, $delim)
    {
        $delimQuoted = preg_quote($delim, '/');
        $thisLine = trim($thisLine);
        
        $parsed = preg_split('/(?<!\\\\)' .$delimQuoted. '/', $thisLine);
        //replaces escaped delimiters
        $parsed = preg_replace('/\\\\' .$delimQuoted. '/', $delim, $parsed);
        if ($delim != $this->options['eolDelim']) {
            //replaces escape chars
            $parsed = preg_replace('/\\\\/', '', $parsed);
        }
        return $parsed;
    }
    // }}}

    // {{{ _querySimSignature()
    
    /**
     * Creates a signature for the QuerySim.
     * This is a work-around for not having a resultset resource handle to ref.
     *
     * @access private
     *
     * @param array $result the array of QuerySim results
     *
     * @return string the signature
     */
    function _querySimSignature($result)
    {
        // convert array to string and get hash.
        // hash is used to keep length down.
        $sig = md5(serialize($result));
        return $sig;
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
        $result_value = $this->_querySimSignature($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return($this->raiseError(MDB_ERROR, NULL, NULL,
                'Get column names: it was specified an inexisting result set'));
        }
        if (!isset($this->columns[$result_value])) {
            $this->columns[$result_value] = array();
            $columns = array_flip($result[0]);
            if ($this->options['optimize'] == 'portability') {
                $columns = array_change_key_case($columns, CASE_LOWER);
            }
            $this->columns[$result_value] = $columns;
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
        $result_value = $this->_querySimSignature($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return $this->raiseError(MDB_ERROR_INVALID, null, null,
                'numCols: a non-existant result set was specified');
        }
        $cols = count($result[0]);
        return $cols;
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
        $result_value = $this->_querySimSignature($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return $this->raiseError(MDB_ERROR, null, null,
                'endOfResult(): attempted to check the end of an unknown result');
        }
        return ($this->highest_fetched_row[$result_value] >= $this->numRows($result)-1);
    }
    // }}}

    // {{{ fetch()

    /**
    * fetch value from a simulated result set
    *
    * @param array  $result simulated result
    * @param int    $row    number of the row where the data can be found
    * @param int    $field    field number where the data can be found
    * @return mixed string on success, a MDB error on failure
    * @access public
    */
    function fetch($result, $row, $field)
    {
        $result_value = $this->_querySimSignature($result);
        $this->highest_fetched_row[$result_value] = max($this->highest_fetched_row[$result_value], $row);
        if (isset($result[1][$row][$field])) {
            $res = $result[1][$row][$field];
        } else {
            return $this->raiseError(MDB_ERROR, null, null,
                "fetch():  row $row, field $field is undefined in result set");
        }
        return $res;
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
        $result_value = $this->_querySimSignature($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return $this->raiseError(MDB_ERROR_INVALID, null, null,
                'numRows(): a non-existant result set was specified');
        }
        $rows = @count($result[1]);
        return $rows;
    }
    // }}}

    // {{{ freeResult()

    /**
     * Free the internal resources associated with $result.
     *
     * @param $result result identifier
     * @return bool TRUE on success, FALSE if $result is invalid
     * @access public
     */
    function freeResult(&$result)
    {
        $result_value = $this->_querySimSignature($result);
        
        if(isset($this->highest_fetched_row[$result_value])) {
            unset($this->highest_fetched_row[$result_value]);
        }
        if(isset($this->columns[$result_value])) {
            unset($this->columns[$result_value]);
        }
        if(isset($this->result_types[$result_value])) {
            unset($this->result_types[$result_value]);
        }
        if (isset($result)) {
            // can't unset() in caller, so this is the best we can do...
            $result = null;
        } else {
            return false;
        }
        return true;
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
    function fetchInto(&$result, $fetchmode = MDB_FETCHMODE_DEFAULT, $rownum = null)
    {
        $result_value = $this->_querySimSignature($result);
        //if specific rownum request
        if ($rownum == null) {
            ++$this->highest_fetched_row[$result_value];
            $rownum = $this->highest_fetched_row[$result_value];
        } else {
            if (!isset($result[1][$rownum])) {
                return null;
            }
            $this->highest_fetched_row[$result_value] =
                max($this->highest_fetched_row[$result_value], $rownum);
        }
        if ($fetchmode == MDB_FETCHMODE_DEFAULT) {
            $fetchmode = $this->fetchmode;
        }
        // get row
        if(!$row = @$result[1][$rownum]) {
            return null;
        }
        // make row associative
        if (is_array($row) && $fetchmode & MDB_FETCHMODE_ASSOC) {
            foreach ($row as $key => $value) {
                $arraytemp[$result[0][$key]] = $value;
            }
            $row = $arraytemp;
            if ($this->options['optimize'] == 'portability') {
                $row = array_change_key_case($row, CASE_LOWER);
            }
        }
        return $row;
    }
    // }}}

    // {{{ nextResult()

    /**
     * Move the array result pointer to the next available row
     *
     * @param array a valid QuerySim result array
     * @return true if a result is available otherwise return false
     * @access public
     */
    function nextResult(&$result)
    {
        $result_value = $this->_querySimSignature($result);
        if (!isset($this->highest_fetched_row[$result_value])) {
            return $this->raiseError(MDB_ERROR_INVALID, null, null,
                'nextResult(): a non-existant result set was specified');
        }
        $result_value = $this->_querySimSignature($result);
        $setrow = ++$this->highest_fetched_row[$result_value];
        return isset($result[1][$setrow]) ? true : false;
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
    //function tableInfo($result, $mode = null)
    //{
        
    //}
}

?>