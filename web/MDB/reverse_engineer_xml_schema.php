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

/**
 * MDB reverse engineering of xml schemas script.
 *
 * @package MDB
 * @category Database
 * @author  Lukas Smith <smith@backendmedia.com>
 */

echo ('
<html>
<body>
');

    if(isset($_REQUEST['submit']) && $_REQUEST['file'] != '') {
        // BC hack to define PATH_SEPARATOR for version of PHP prior 4.3
        if(!defined('PATH_SEPARATOR')) {
            if(defined('DIRECTORY_SEPARATOR') && DIRECTORY_SEPARATOR == "\\") {
                define('PATH_SEPARATOR', ';');
            } else {
                define('PATH_SEPARATOR', ':');
            }
        }
        ini_set('include_path', '..'.PATH_SEPARATOR.ini_get('include_path'));
        require_once('MDB.php');
        @include_once('Var_Dump.php');
        MDB::loadFile('Manager');
        $dsn = $_REQUEST['type'].'://'.$_REQUEST['user'].':'.$_REQUEST['pass'].'@'.$_REQUEST['host'].'/'.$_REQUEST['name'];

        $manager =& new MDB_Manager;
        $err = $manager->connect($dsn);
        if(MDB::isError($err)) {
            $error = $err->getMessage();
        } else {
            $manager->captureDebugOutput(TRUE);
            $manager->database->setOption('log_line_break', '<br>');
            if($_REQUEST['action']) {
                set_time_limit(0);
            }
            if($_REQUEST['action'] == 'dump') {
                switch ($_REQUEST['dump']) {
                    case 'structure':
                        $dump_what = MDB_MANAGER_DUMP_STRUCTURE;
                        break;
                    case 'content':
                        $dump_what = MDB_MANAGER_DUMP_CONTENT;
                        break;
                    default:
                        $dump_what = MDB_MANAGER_DUMP_ALL;
                        break;
                }
                if (class_exists('Var_Dump')) {
                    Var_Dump::display($manager->updateDatabase($_REQUEST['file']));
                } else {
                    var_dump($manager->updateDatabase($_REQUEST['file']));
                }
                $dump_config = array(
                    'Output_Mode' => 'file',
                    'Output' => $_REQUEST['file']
                );
                if (class_exists('Var_Dump')) {
                    Var_Dump::display($manager->dumpDatabase($dump_config, $dump_what));
                } else {
                    var_dump($manager->dumpDatabase($dump_config, $dump_what));
                }
            } else if($_REQUEST['action'] == 'create') {
                if (class_exists('Var_Dump')) {
                    Var_Dump::display($manager->updateDatabase($_REQUEST['file']));
                } else {
                    var_dump($manager->updateDatabase($_REQUEST['file']));
                }
            } else {
                $error = 'no action selected';
            }
            $warnings = $manager->getWarnings();
            if(count($warnings) > 0) {
                echo('Warnings<br>');
                if (class_exists('Var_Dump')) {
                    Var_Dump::display($warnings);
                } else {
                    var_dump($warnings);
                }
            }
            if($manager->options['debug']) {
                echo('Debug messages<br>');
                echo($manager->debugOutput().'<br>');
            }
            echo('Database structure<br>');
            if (class_exists('Var_Dump')) {
                Var_Dump::display($manager->database_definition);
            } else {
                var_dump($manager->database_definition);
            }
            $manager->disconnect();
        }
    }
    
    if (!isset($_REQUEST['submit']) || isset($error)) {
        if (isset($error) && $error) {
            echo($error.'<br>');
        }
        echo ('
            <form action="reverse_engineer_xml_schema.php">
            Database Type:
            <select name="type">
                <option value="mysql"');
                if(isset($_REQUEST['type']) && $_REQUEST['type'] == 'mysql') {echo ('selected');}
                echo ('>MySQL</option>
                <option value="pgsql"');
                if(isset($_REQUEST['type']) && $_REQUEST['type'] == 'mysql') {echo ('selected');}
                echo ('>Postgres</option>
            </select>
            <br />
            Username:
            <input type="text" name="user" value="'.(isset($_REQUEST['user']) ? $_REQUEST['user'] : '').'" />
            <br />
            Password:
            <input type="text" name="pass" value="'.(isset($_REQUEST['pass']) ? $_REQUEST['pass'] : '').'" />
            <br />
            Host:
            <input type="text" name="host" value="'.(isset($_REQUEST['host']) ? $_REQUEST['host'] : '').'" />
            <br />
            Databasename:
            <input type="text" name="name" value="'.(isset($_REQUEST['name']) ? $_REQUEST['name'] : '').'" />
            <br />
            Filename:
            <input type="text" name="file" value="'.(isset($_REQUEST['file']) ? $_REQUEST['file'] : '').'" />
            <br />
            Dump:
            <input type="radio" name="action" value="dump" />
            <select name="dump">
                <option value="all"');
                if(!isset($_REQUEST['dump']) || $_REQUEST['dump'] == 'all') {echo ('selected');}
                echo ('>All</option>
                <option value="structure"');
                if(isset($_REQUEST['dump']) && $_REQUEST['dump'] == 'structure') {echo ('selected');}
                echo ('>Structure</option>
                <option value="content"');
                if(isset($_REQUEST['dump']) && $_REQUEST['dump'] == 'content') {echo ('selected');}
                echo ('>Content</option>
            </select>
            <br />
            Create:
            <input type="radio" name="action" value="create" />
            <br />
            <input type="submit" name="submit" value="ok" />
        ');
    }

    echo ('
</form>
</body>
</html>
    ');
?>
