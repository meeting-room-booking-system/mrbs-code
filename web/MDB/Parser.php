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
// | Author: Christian Dickmann <dickmann@php.net>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once('XML/Parser.php');

/**
 * Parses an XML schema file
 *
 * @package MDB
 * @category Database
 * @access private
 * @author  Christian Dickmann <dickmann@php.net>
 */
class MDB_Parser extends XML_Parser
{
    var $database_definition = array();
    var $elements = array();
    var $element = '';
    var $count = 0;
    var $table = array();
    var $table_name = '';
    var $field = array();
    var $field_name = '';
    var $init = array();
    var $init_name = '';
    var $init_value = '';
    var $index = array();
    var $index_name = '';
    var $var_mode = FALSE;
    var $variables = array();
    var $seq = array();
    var $seq_name = '';
    var $error = NULL;

    var $invalid_names = array(
        'user' => array(),
        'is' => array(),
        'file' => array(
            'oci' => array(),
            'oracle' => array()
        ),
        'notify' => array(
            'pgsql' => array()
        ),
        'restrict' => array(
            'mysql' => array()
        ),
        'password' => array(
            'ibase' => array()
        )
    );
    var $fail_on_invalid_names = 1;

    function MDB_Parser($variables, $fail_on_invalid_names = 1) 
    {
        $this->XML_Parser();
        $this->variables = $variables;
        $this->fail_on_invalid_names = $fail_on_invalid_names;
    }

    function startHandler($xp, $element, $attribs) 
    {   
        if (strtolower($element) == 'variable') {
            $this->var_mode = TRUE;
            return;
        };
        
        $this->elements[$this->count++] = strtolower($element);
        $this->element = implode('-', $this->elements);
        
        switch($this->element) {
        case 'database-table-initialization-insert':
            $this->init = array('type' => 'insert');
            break;
        case 'database-table-initialization-insert-field':
            $this->init_name = '';
            $this->init_value = '';
            break;
        case 'database-table':
            $this->table_name = '';
            $this->table = array();
            break;
        case 'database-table-declaration-field':
            $this->field_name = '';
            $this->field = array();
            break;
        case 'database-table-declaration-field-default':
            $this->field['default'] = '';
            break;
        case 'database-table-declaration-index':
            $this->index_name = '';
            $this->index = array();
            break;
        case 'database-sequence':
            $this->seq_name = '';
            $this->seq = array();
            break;
        case 'database-table-declaration-index-field':
            $this->field_name = '';
            $this->field = array();
            break;
        };
    }

    function endHandler($xp, $element)
    {
        if (strtolower($element) == 'variable') {
            $this->var_mode = FALSE;
            return;
        };
        
        switch($this->element) {
        /* Initialization */
        case 'database-table-initialization-insert-field':
            if (!$this->init_name) {
                $this->raiseError('field-name has to be specified', $xp);
            };
            if (isset($this->init['FIELDS'][$this->init_name])) {
                $this->raiseError('field "'.$this->init_name.'" already filled', $xp);
            };
            if (!isset($this->table['FIELDS'][$this->init_name])) {
                $this->raiseError('unkown field "'.$this->init_name.'"', $xp);
            };
            if ($this->init_value !== '' 
                && !$this->validateFieldValue($this->init_name, $this->init_value, $xp)) 
            {
                $this->raiseError('field "'.$this->init_name.'" has wrong value', $xp);
            };
            $this->init['FIELDS'][$this->init_name] = $this->init_value;
            break;
        case 'database-table-initialization-insert':
            $this->table['initialization'][] = $this->init;
            break;
            
        /* Table definition */
        case 'database-table':
            if (!isset($this->table['was'])) {
                $this->table['was'] = $this->table_name;
            };
            if (!$this->table_name) {
                $this->raiseError('tables need names', $xp);
            };
            if (isset($this->database_definition['TABLES'][$this->table_name])) {
                $this->raiseError('table "'.$this->table_name.'" already exists', $xp);
            };
            if (!isset($this->table['FIELDS'])) {
                $this->raiseError('tables need one or more fields', $xp);
            };
            if (isset($this->table['INDEXES'])) {
                foreach($this->table['INDEXES'] as $index_name => $index) {
                    foreach($index['FIELDS'] as $field_name => $field) {
                        if (!isset($this->table['FIELDS'][$field_name])) {
                            $this->raiseError('index field "'.$field_name.'" does not exist', $xp);
                        }
                        if (!(isset($this->table['FIELDS'][$field_name]['notnull'])
                            && $this->table['FIELDS'][$field_name]['notnull'] == 1)) 
                        {
                            $this->raiseError('index field "'.$field_name.'" has to be "notnull"', $xp);
                        }
                    }
                }
            };
            $this->database_definition['TABLES'][$this->table_name] = $this->table;
            break;
            
        /* Field declaration */
        case 'database-table-declaration-field':
            if (!$this->field_name || !isset($this->field['type'])) {
                $this->raiseError('field "'.$this->field_name.'" was not properly specified', $xp);
            };
            if (isset($this->table['FIELDS'][$this->field_name])) {
                $this->raiseError('field "'.$this->field_name.'" already exists', $xp);
            };
            /* Invalidname check */
            if ($this->fail_on_invalid_names && isset($this->invalid_names[$this->field_name])) {
                $this->raiseError('fieldname "'.$this->field_name.'" not allowed', $xp);
            };
            /* Type check */
            switch($this->field['type']) {
            case 'integer':
                if (isset($this->field['unsigned']) 
                    && $this->field['unsigned'] !== '1' && $this->field['unsigned'] !== '0') 
                {
                    $this->raiseError('unsigned has to be 1 or 0', $xp);
                };
                break;
            case 'text':
            case 'clob':
            case 'blob':
                if (isset($this->field['length']) && ((int)$this->field['length']) <= 0) {
                    $this->raiseError('length has to be an integer greater 0', $xp);
                };
                break;
            case 'boolean':
            case 'date':
            case 'timestamp':
            case 'time':
            case 'float':
            case 'decimal':
                break;
            default: 
                $this->raiseError('no valid field type ("'.$this->field['type'].'") specified', $xp);
            };
            if (!isset($this->field['was'])) {
                $this->field['was'] = $this->field_name;
            };
            if (isset($this->field['notnull']) && !$this->is_boolean($this->field['notnull'])) {
                $this->raiseError('field  "notnull" has to be 1 or 0', $xp);
            };
            if (isset($this->field['notnull']) && !isset($this->field['default'])) {
                $this->raiseError('if field is "notnull", it needs a default value', $xp);
            };
            if (isset($this->field['unsigned']) && !$this->is_boolean($this->field['unsigned'])) {
                $this->raiseError('field  "notnull" has to be 1 or 0', $xp);
            };
            $this->table['FIELDS'][$this->field_name] = $this->field;
            if (isset($this->field['default'])) {
                if ($this->field['type'] == 'clob' || $this->field['type'] == 'blob') {
                    $this->raiseError('"'.$this->field['type'].'"-fields are not allowed to have a default value', $xp);
                };
                if ($this->field['default'] !== '' 
                    && !$this->validateFieldValue($this->field_name, $this->field['default'], $xp))
                {
                    $this->raiseError('default value of "'.$this->field_name.'" is of wrong type', $xp);
                };
            };
            break;
            
        /* Index declaration */
        case 'database-table-declaration-index':
            if (!$this->index_name) {
                $this->raiseError('an index needs a name', $xp);
            };
            if (isset($this->table['INDEXES'][$this->index_name])) {
                $this->raiseError('index "'.$this->index_name.'" already exists', $xp);
            };
            if (isset($this->index['unique']) && !$this->is_boolean($this->index['unique'])) {
                $this->raiseError('field  "unique" has to be 1 or 0', $xp);
            };
            if (!isset($this->index['was'])) {
                $this->index['was'] = $this->index_name;
            };
            $this->table['INDEXES'][$this->index_name] = $this->index;
            break;
        case 'database-table-declaration-index-field':
            if (!$this->field_name) {
                $this->raiseError('the index-field-name is required', $xp);
            };
            if (isset($this->field['sorting']) 
                && $this->field['sorting'] !== 'ascending' && $this->field['sorting'] !== 'descending') {
                $this->raiseError('sorting type unknown', $xp);
            };
            $this->index['FIELDS'][$this->field_name] = $this->field;
            break;
            
        /* Sequence declaration */
        case 'database-sequence':
            if (!$this->seq_name) {
                $this->raiseError('a sequence has to have a name', $xp);
            };
            if (isset($this->database_definition['SEQUENCES'][$this->seq_name])) {
                $this->raiseError('sequence "'.$this->seq_name.'" already exists', $xp);
            };
            if (!isset($this->seq['was'])) {
                $this->seq['was'] = $this->seq_name;
            };
            if (isset($this->seq['on'])) {
                if ((!isset($this->seq['on']['table']) || !$this->seq['on']['table'])
                    || (!isset($this->seq['on']['field']) || !$this->seq['on']['field'])) 
                {
                    $this->raiseError('sequence "'.$this->seq_name.'" was not properly defined', $xp);
                };
            };
            $this->database_definition['SEQUENCES'][$this->seq_name] = $this->seq;
            break;
            
        /* End of File */
        case 'database':
            if (isset($this->database_definition['create']) 
                && !$this->is_boolean($this->database_definition['create']))
            {
                $this->raiseError('field "create" has to be 1 or 0', $xp);
            };
            if (isset($this->database_definition['overwrite']) 
                && !$this->is_boolean($this->database_definition['overwrite']))
            {
                $this->raiseError('field "overwrite" has to be 1 or 0', $xp);
            };
            if (!isset($this->database_definition['name']) || !$this->database_definition['name']) {
                $this->raiseError('database needs a name', $xp);
            };
            if (isset($this->database_definition['SEQUENCES'])) {
                foreach($this->database_definition['SEQUENCES'] as $seq_name => $seq) {
                    if (isset($seq['on']) 
                        && !isset($this->database_definition['TABLES'][$seq['on']['table']]['FIELDS'][$seq['on']['field']]))
                    {
                        $this->raiseError('sequence "'.$seq_name.'" was assigned on unexisting field/table', $xp);
                    };
                };
            };
            if (MDB::isError($this->error)) {
                $this->database_definition = $this->error;
            };
            break;
        }
        
        unset($this->elements[--$this->count]);
        $this->element = implode('-', $this->elements);
    }

    function validateFieldValue($field_name, &$field_value, &$xp)
    {
        if (!isset($this->table['FIELDS'][$field_name])) {
            return;
        };
        $field_def = $this->table['FIELDS'][$field_name];
        switch($field_def['type']) {
        case 'text':
        case 'clob':
            if (isset($field_def['length']) && strlen($field_value) > $field_def['length']) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            };
            break;
        case 'blob':
            /*
            if (!preg_match('/^([0-9a-f]{2})*$/i', $field_value)) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            }
            */
            $field_value = pack('H*', $field_value);
            if (isset($field_def['length']) && strlen($field_value) > $field_def['length']) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            };
            break;
        case 'integer':
            if ($field_value != ((int)$field_value)) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            };
            $field_value = (int) $field_value;
            if (isset($field_def['unsigned']) && $field_def['unsigned'] && $field_value < 0) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            };
            break;
        case 'boolean':
            if (!$this->is_boolean($field_value)) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            }
            break;
        case 'date':
            if (!preg_match('/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/', $field_value)) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            }
            break;
        case 'timestamp':
            if (!preg_match('/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/', $field_value)) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            }
            break;
        case 'time':
            if (!preg_match("/([0-9]{2}):([0-9]{2}):([0-9]{2})/", $field_value)) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            }
            break;
        case 'float':
        case 'double':
            if ($field_value != (double) $field_value) {
                return($this->raiseError('"'.$field_value.'" is not of type "'.$field_def['type'].'"', $xp));
            };
            $field_value = (double) $field_value;
            break;
        }
        return(TRUE);
    }

    function raiseError($msg, $xp = NULL)
    {
        if ($this->error === NULL) {
            if(is_resource($msg)) {
                $error = "Parser error: ";
                $xp = $msg;
            } else {
                $error = "Parser error: \"".$msg."\"\n";
            }
            if($xp != NULL) {
                $byte = @xml_get_current_byte_index($xp);
                $line = @xml_get_current_line_number($xp);
                $column = @xml_get_current_column_number($xp);
                $error .= "Byte: $byte; Line: $line; Col: $column\n";
            }
            $this->error = PEAR::raiseError(NULL, MDB_ERROR_MANAGER_PARSE, NULL, NULL,
                $error, 'MDB_Error', TRUE);
        };
        return(FALSE);
    }

    function is_boolean(&$value)
    {
        if (is_int($value) && ($value == 0 || $value == 1)) {
            return(TRUE);
        };
        if ($value === '1' || $value === '0') {
            $value = (int) $value;
            return(TRUE);
        };
        switch($value)
        {
        case 'N':
        case 'n':
        case 'no':
        case 'FALSE':
            $value = 0;
            break;
        case 'Y':
        case 'y':
        case 'yes':
        case 'TRUE':
            $value = 1;
            break;
        default:
            return(FALSE);
        };
        return(TRUE);
    }

    function cdataHandler($xp, $data)
    {
        if ($this->var_mode == TRUE) {
            if (!isset($this->variables[$data])) {
                $this->raiseError('variable "'.$data.'" not found', $xp);
                return;
            };
            $data = $this->variables[$data];
        };
        
        switch($this->element) {
        /* Initialization */
        case 'database-table-initialization-insert-field-name':
            @$this->init_name .= $data;
            break;
        case 'database-table-initialization-insert-field-value':
            @$this->init_value .= $data;
            break;
            
        /* Database */
        case 'database-name': 
            @$this->database_definition['name'] .= $data;
            break;
        case 'database-create':
            @$this->database_definition['create'] .= $data;
            break;
        case 'database-overwrite':
            @$this->database_definition['overwrite'] .= $data;
            break;
        case 'database-table-name':
            @$this->table_name .= $data;
            break;
        case 'database-table-was':
            @$this->table['was'] .= $data;
            break;
            
        /* Field declaration */
        case 'database-table-declaration-field-name':
            @$this->field_name .= $data;
            break;
        case 'database-table-declaration-field-type':
            @$this->field['type'] .= $data;
            break;
        case 'database-table-declaration-field-was':
            @$this->field['was'] .= $data;
            break;
        case 'database-table-declaration-field-notnull':
            @$this->field['notnull'] .= $data;
            break;
        case 'database-table-declaration-field-unsigned':
            @$this->field['unsigned'] .= $data;
            break;
        case 'database-table-declaration-field-default':
            @$this->field['default'] .= $data;
            break;
        case 'database-table-declaration-field-length':
            @$this->field['length'] .= $data;
            break;
            
        /* Index declaration */
        case 'database-table-declaration-index-name':
            @$this->index_name .= $data;
            break;
        case 'database-table-declaration-index-unique':
            @$this->index['unique'] .= $data;
            break;
        case 'database-table-declaration-index-was':
            @$this->index['was'] .= $data;
            break;
        case 'database-table-declaration-index-field-name':
            @$this->field_name .= $data;
            break;
        case 'database-table-declaration-index-field-sorting':
            @$this->field['sorting'] .= $data;
            break;
            
        /* Sequence declaration */
        case 'database-sequence-name':
            @$this->seq_name .= $data;
            break;
        case 'database-sequence-was':
            @$this->seq['was'] .= $data;
            break;
        case 'database-sequence-start':
            @$this->seq['start'] .= $data;
            break;
        case 'database-sequence-on-table':
            @$this->seq['on']['table'] .= $data;
            break;
        case 'database-sequence-on-field':
            @$this->seq['on']['field'] .= $data;
            break;
        };
    }
};

?>
