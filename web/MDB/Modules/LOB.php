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

if(!defined('MDB_LOB_INCLUDED'))
{
    define('MDB_LOB_INCLUDED', 1);

/**
 * MDB Large Object (BLOB/CLOB) classes
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Lukas Smith <smith@backendmedia.com>
 */
class MDB_LOB
{
    var $database;
    var $lob;
    var $data = '';
    var $position = 0;

    function create(&$arguments)
    {
        if(isset($arguments['Data'])) {
            $this->data = $arguments['Data'];
        }
        return(MDB_OK);
    }

    function destroy()
    {
        $this->data = '';
    }

    function endOfLob()
    {
        return($this->position >= strlen($this->data));
    }

    function readLob(&$data, $length)
    {
        $length = min($length, strlen($this->data) - $this->position);
        $data = substr($this->data, $this->position, $length);
        $this->position += $length;
        return($length);
    }
};

class MDB_LOB_Result extends MDB_LOB
{
    var $result_lob = 0;

    function create(&$arguments)
    {
        if(!isset($arguments['ResultLOB'])) {
            return(PEAR::raiseError(NULL, MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                'it was not specified a result Lob identifier',
                'MDB_Error', TRUE));
        }
        $this->result_lob = $arguments['ResultLOB'];
        return(MDB_OK);
    }

    function destroy()
    {
        $this->database->_destroyResultLob($this->result_lob);
    }

    function endOfLob()
    {
        return($this->database->endOfResultLob($this->result_lob));
    }

    function readLob(&$data, $length)
    {
        $read_length = $this->database->_readResultLob($this->result_lob, $data, $length);
        if (MDB::isError($read_length)) {
            return($read_length);
        }
        if($read_length < 0) {
            return(PEAR::raiseError(NULL, MDB_ERROR_INVALID, NULL, NULL,
                'data was read beyond end of data source',
                'MDB_Error', TRUE));
        }
        return($read_length);
    }
};

class MDB_LOB_Input_File extends MDB_LOB
{
    var $file = 0;
    var $opened_file = 0;

    function create(&$arguments)
    {
        if(isset($arguments['File'])) {
            if(intval($arguments['File']) == 0) {
                return(PEAR::raiseError(NULL, MDB_ERROR_INVALID, NULL, NULL,
                    'it was specified an invalid input file identifier',
                    'MDB_Error', TRUE));
            }
            $this->file = $arguments['File'];
        }
        else
        {
            if(isset($arguments['FileName'])) {
                if((!$this->file = fopen($arguments['FileName'], 'rb'))) {
                return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND, NULL, NULL,
                    'could not open specified input file ("'.$arguments['FileName'].'")',
                    'MDB_Error', TRUE));
                }
                $this->opened_file = 1;
            } else {
                return(PEAR::raiseError(NULL, MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                    'it was not specified the input file',
                    'MDB_Error', TRUE));
            }
        }
        return(MDB_OK);
    }

    function destroy()
    {
        if($this->opened_file) {
            fclose($this->file);
            $this->file = 0;
            $this->opened_file = 0;
        }
    }

    function endOfLob() {
        return(feof($this->file));
    }

    function readLob(&$data, $length)
    {
        if(gettype($data = @fread($this->file, $length))!= 'string') {
            return(PEAR::raiseError(NULL, MDB_ERROR, NULL, NULL,
                'could not read from the input file',
                'MDB_Error', TRUE));
        }
        return(strlen($data));
    }
};

class MDB_LOB_Output_File extends MDB_LOB
{
    var $file = 0;
    var $opened_file = 0;
    var $input_lob = 0;
    var $opened_lob = 0;
    var $buffer_length = 8000;

    function create(&$arguments)
    {
        if(isset($arguments['BufferLength'])) {
            if($arguments['BufferLength'] <= 0) {
                return(PEAR::raiseError(NULL, MDB_ERROR_INVALID, NULL, NULL,
                    'it was specified an invalid buffer length',
                    'MDB_Error', TRUE));
            }
            $this->buffer_length = $arguments['BufferLength'];
        }
        if(isset($arguments['File'])) {
            if(intval($arguments['File']) == 0) {
                return(PEAR::raiseError(NULL, MDB_ERROR_INVALID, NULL, NULL,
                    'it was specified an invalid output file identifier',
                    'MDB_Error', TRUE));
            }
            $this->file = $arguments['File'];
        } else {
            if(isset($arguments['FileName'])) {
                if((!$this->file = fopen($arguments['FileName'],'wb'))) {
                    return(PEAR::raiseError(NULL, MDB_ERROR_NOT_FOUND, NULL, NULL,
                        'could not open specified output file ("'.$arguments['FileName'].'")',
                        'MDB_Error', TRUE));
                }
                $this->opened_file = 1;
            } else {
                return(PEAR::raiseError(NULL, MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                    'it was not specified the output file',
                    'MDB_Error', TRUE));
            }
        }
        if(isset($arguments['LOB'])) {
            if(!is_object($arguments['LOB'])) {
                $this->destroy();
                return(PEAR::raiseError(NULL, MDB_ERROR_INVALID, NULL, NULL,
                    'it was specified an invalid input large object identifier',
                    'MDB_Error', TRUE));
            }
            $this->input_lob = $arguments['LOB'];
        } else {
            if($this->database
                && isset($arguments['Result'])
                && isset($arguments['Row'])
                && isset($arguments['Field'])
                && isset($arguments['Binary']))
            {
                if($arguments['Binary']) {
                    $this->input_lob = $this->database->fetchBlob($arguments['Result'],
                        $arguments['Row'], $arguments['Field']);
                } else {
                    $this->input_lob = $this->database->fetchClob($arguments['Result'],
                        $arguments['Row'], $arguments['Field']);
                }
                if($this->input_lob == 0) {
                    $this->destroy();
                    return(PEAR::raiseError(NULL, MDB_ERROR, NULL, NULL,
                        'could not fetch the input result large object',
                        'MDB_Error', TRUE));
                }
                $this->opened_lob = 1;
            } else {
                $this->destroy();
                return(PEAR::raiseError(NULL, MDB_ERROR_NEED_MORE_DATA, NULL, NULL,
                    'it was not specified the input large object identifier',
                    'MDB_Error', TRUE));
            }
        }
        return(MDB_OK);
    }

    function destroy()
    {
        if($this->opened_file) {
            fclose($this->file);
            $this->opened_file = 0;
            $this->file = 0;
        }
        if($this->opened_lob) {
            $this->database->destroyLob($this->input_lob);
            $this->input_lob = 0;
            $this->opened_lob = 0;
        }
    }

    function endOfLob()
    {
        return($this->database->endOfLob($this->input_lob));
    }

    function readLob(&$data, $length) {
        $buffer_length = ($length == 0 ? $this->buffer_length : $length);
        $written_full = 0;
        do {
            for($written = 0;
                !$this->database->endOfLob($this->input_lob)
                && $written < $buffer_length;
                $written += $read)
            {
                if(MDB::isError($result = $this->database->
                    readLob($this->input_lob, $buffer, $buffer_length)))
                {
                    return($result);
                }
                $read = strlen($buffer);
                if(@fwrite($this->file, $buffer, $read)!= $read) {
                    return(PEAR::raiseError(NULL, MDB_ERROR, NULL, NULL,
                        'could not write to the output file',
                        'MDB_Error', TRUE));
                }
            }
            $written_full += $written;
        } while($length == 0 && !$this->database->endOfLob($this->input_lob));
        return($written_full);
    }
}

};
?>
