<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::Passwd::Common
 * 
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   FileFormats
 * @package    File_Passwd
 * @author     Michael Wallner <mike@php.net>
 * @copyright  2003-2005 Michael Wallner
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Common.php,v 1.18 2005/03/30 18:33:33 mike Exp $
 * @link       http://pear.php.net/package/File_Passwd
 */

/**
* Requires System
*/
require_once 'System.php';
/**
* Requires File::Passwd
*/
require_once 'File/Passwd.php';

/**
* Baseclass for File_Passwd_* classes.
* 
* <kbd><u>
*   Provides basic operations:
* </u></kbd>
*   o opening & closing
*   o locking & unlocking
*   o loading & saving
*   o check if user exist
*   o delete a certain user
*   o list users
* 
* @author   Michael Wallner <mike@php.net>
* @package  File_Passwd
* @version  $Revision: 1.18 $
* @access   protected
* @internal extend this class for your File_Passwd_* class
*/
class File_Passwd_Common
{
    /**
    * passwd file
    *
    * @var string
    * @access protected
    */
    var $_file = 'passwd';
    
    /**
    * file content
    *
    * @var aray
    * @access protected
    */
    var $_contents = array();
    
    /**
    * users
    *
    * @var array
    * @access protected
    */
    var $_users = array();
    
    /**
    * PCRE for valid chars
    * 
    * @var  string
    * @access   protected
    */
    var $_pcre = '/^[a-z]+[a-z0-9_-]*$/i';
    
    /**
    * Constructor (ZE2)
    *
    * @access protected
    * @param  string    $file   path to passwd file
    */
    function __construct($file = 'passwd')
    {
        $this->setFile($file);
    }
    
    /**
    * Parse the content of the file
    *
    * You must overwrite this method in your File_Passwd_* class.
    * 
    * @abstract
    * @internal
    * @access public
    * @return object    PEAR_Error
    */
    function parse()
    {
        return PEAR::raiseError(
            sprintf(FILE_PASSWD_E_METHOD_NOT_IMPLEMENTED_STR, 'parse'),
            FILE_PASSWD_E_METHOD_NOT_IMPLEMENTED
        );
    }
    
    /**
    * Apply changes and rewrite passwd file
    *
    * You must overwrite this method in your File_Passwd_* class.
    * 
    * @abstract
    * @internal
    * @access public
    * @return object    PEAR_Error
    */
    function save()
    {
        return PEAR::raiseError(
            sprintf(FILE_PASSWD_E_METHOD_NOT_IMPLEMENTED_STR, 'save'),
            FILE_PASSWD_E_METHOD_NOT_IMPLEMENTED
        );
    }
    
    /**
    * Opens a file, locks it exclusively and returns the filehandle
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in the desired mode
    *   o file couldn't be locked exclusively
    * 
    * @throws PEAR_Error
    * @access protected
    * @return mixed resource of type file handle or PEAR_Error
    * @param  string    $mode   the mode to open the file with
    */
    function &_open($mode, $file = null)
    {
        isset($file) or $file = $this->_file;
        $dir  = dirname($file);
        $lock = strstr($mode, 'r') ? LOCK_SH : LOCK_EX;
        if (!is_dir($dir) && !System::mkDir('-p -m 0755 ' . $dir)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_DIR_NOT_CREATED_STR, $dir),
                FILE_PASSWD_E_DIR_NOT_CREATED
            );
        }
        if (!is_resource($fh = @fopen($file, $mode))) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_FILE_NOT_OPENED_STR, $file),
                FILE_PASSWD_E_FILE_NOT_OPENED
            );
        }
        if (!@flock($fh, $lock)) {
            fclose($fh);
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_FILE_NOT_LOCKED_STR, $file),
                FILE_PASSWD_E_FILE_NOT_LOCKED
            );
        }
        return $fh;
    }
    
    /**
    * Closes a prior opened and locked file handle
    *
    * Returns a PEAR_Error if:
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access protected
    * @return mixed true on success or PEAR_Error
    * @param  resource  $file_handle    the file handle to operate on
    */
    function _close(&$file_handle)
    {
        if (!@flock($file_handle, LOCK_UN)) {
            return PEAR::raiseError(
                FILE_PASSWD_E_FILE_NOT_UNLOCKED_STR,
                FILE_PASSWD_E_FILE_NOT_UNLOCKED
            );
        }
        if (!@fclose($file_handle)) {
            return PEAR::raiseError(
                FILE_PASSWD_E_FILE_NOT_CLOSED_STR,
                FILE_PASSWD_E_FILE_NOT_CLOSED
            );
        }
        return true;
    }
    
    /**
    * Loads the file
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in read mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    */
    function load()
    {
        $fh = &$this->_open('r');
        if (PEAR::isError($fh)) {
            return $fh;
        }
        $this->_contents = array();
        while ($line = fgets($fh)) {
            if (!preg_match('/^\s*#/', $line) && $line = trim($line)) {
                $this->_contents[] = $line;
            }
        }
        $e = $this->_close($fh);
        if (PEAR::isError($e)) {
            return $e;
        }
        return $this->parse();
    }
    
    /**
    * Save the modified content to the passwd file
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in write mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access protected
    * @return mixed true on success or PEAR_Error
    */
    function _save($content)
    {
        $fh = &$this->_open('w');
        if (PEAR::isError($fh)) {
            return $fh;
        }
        fputs($fh, $content);
        return $this->_close($fh);
    }
    
    /**
    * Set path to passwd file
    *
    * @access public
    * @return void
    */
    function setFile($file)
    {
        $this->_file = $file;
    }
    
    /**
    * Get path of passwd file
    *
    * @access public
    * @return string
    */
    function getFile()
    {
        return $this->_file;
    }

    /**
    * Check if a certain user already exists
    *
    * @access public
    * @return bool
    * @param  string    $user   the name of the user to check if already exists
    */
    function userExists($user)
    {
        return isset($this->_users[$user]);
    }
    
    /**
    * Delete a certain user
    *
    * Returns a PEAR_Error if user doesn't exist.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    * @param  string    
    */
    function delUser($user)
    {
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        unset($this->_users[$user]);
        return true;
    }
    
    /**
    * List user
    *
    * Returns a PEAR_Error if <var>$user</var> doesn't exist.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed array of a/all user(s) or PEAR_Error
    * @param  string    $user   the user to list or all users if empty
    */
    function listUser($user = '')
    {
        if (empty($user)) {
            return $this->_users;
        }
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        return $this->_users[$user];
    }

    /**
    * Base method for File_Passwd::staticAuth()
    * 
    * Returns a PEAR_Error if:
    *   o file doesn't exist
    *   o file couldn't be opened in read mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked (only if auth fails)
    *   o file couldn't be closed (only if auth fails)
    * 
    * @throws   PEAR_Error
    * @access   protected
    * @return   mixed       line of passwd file containing <var>$id</var>,
    *                       false if <var>$id</var> wasn't found or PEAR_Error
    * @param    string      $file   path to passwd file
    * @param    string      $id     user_id to search for
    * @param    string      $sep    field separator
    */
    function _auth($file, $id, $sep = ':')
    {
        $file = realpath($file);
        if (!is_file($file)) {
            return PEAR::raiseError("File '$file' couldn't be found.", 0);
        }
        $fh = &File_Passwd_Common::_open('r', $file);
        if (PEAR::isError($fh)) {
            return $fh;
        }
        $cmp = $id . $sep;
        $len = strlen($cmp);
        while ($line = fgets($fh)) {
            if (!strncmp($line, $cmp, $len)) {
                File_Passwd_Common::_close($fh);
                return trim($line);
            }
        }
        $e = File_Passwd_Common::_close($fh);
        if (PEAR::isError($e)) {
            return $e;
        }
        return false;
    }
}
