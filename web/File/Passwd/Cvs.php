<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::Passwd::Cvs
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
 * @version    CVS: $Id: Cvs.php,v 1.14 2005/03/30 18:33:33 mike Exp $
 * @link       http://pear.php.net/package/File_Passwd
 */

/**
* Requires File::Passwd::Common
*/
require_once 'File/Passwd/Common.php';

/**
* Manipulate CVS pserver passwd files.
* 
* <kbd><u>
*   A line of a CVS pserver passwd file consists of 2 to 3 colums:
* </u></kbd>
* <pre>
*   user1:1HCoDDWxK9tbM:sys_user1
*   user2:0O0DYYdzjCVxs
*   user3:MIW9UUoifhqRo:sys_user2
* </pre>
* 
* If the third column is specified, the CVS user named in the first column is 
* mapped to the corresponding system user named in the third column.
* That doesn't really affect us - just for your interest :)
* 
* <kbd><u>Output of listUser()</u></kbd>
* <pre>
*      array
*       + user =>  array
*                   + passwd => crypted_passwd
*                   + system => system_user
*       + user =>  array
*                   + passwd => crypted_passwd
*                   + system => system_user
* </pre>
* 
* @author   Michael Wallner <mike@php.net>
* @package  File_Passwd
* @version  $Revision: 1.14 $
* @access   public
*/
class File_Passwd_Cvs extends File_Passwd_Common
{
    /**
    * Constructor
    *
    * @access public
    */
    function File_Passwd_Cvs($file = 'passwd')
    {
        parent::__construct($file);
    }
    
    /**
    * Fast authentication of a certain user
    * 
    * Returns a PEAR_Error if:
    *   o file doesn't exist
    *   o file couldn't be opened in read mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked (only if auth fails)
    *   o file couldn't be closed (only if auth fails)
    *
    * @static   call this method statically for a reasonable fast authentication
    * @access   public
    * @return   mixed   true if authenticated, false if not or PEAR_Error
    * @param    string  $file   path to passwd file
    * @param    string  $user   user to authenticate
    * @param    string  $pass   plaintext password
    */
    function staticAuth($file, $user, $pass)
    {
        $line = File_Passwd_Common::_auth($file, $user);
        if (!$line || PEAR::isError($line)) {
            return $line;
        }
        @list(,$real)   = explode(':', $line);
        return (File_Passwd_Cvs::generatePassword($pass, $real) === $real);
    }
    
    /**
    * Apply changes and rewrite CVS passwd file
    *
    * Returns a PEAR_Error if:
    *   o directory in which the file should reside couldn't be created
    *   o file couldn't be opened in write mode
    *   o file couldn't be locked exclusively
    *   o file couldn't be unlocked
    *   o file couldn't be closed
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    */
    function save()
    {
        $content = '';
        foreach ($this->_users as $user => $v){
            $content .= $user . ':' . $v['passwd'];
            if (isset($v['system']) && !empty($v['system'])) {
                $content .= ':' . $v['system'];
            }
            $content .= "\n";
        }
        return $this->_save($content);
    }
    
    /** 
    * Parse the CVS passwd file
    *
    * Returns a PEAR_Error if passwd file has invalid format.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    */
    function parse()
    {
        $this->_users = array();
        foreach ($this->_contents as $line) {
            $user = explode(':', $line);
            if (count($user) < 2) {
                return PEAR::raiseError(
                    FILE_PASSWD_E_INVALID_FORMAT_STR,
                    FILE_PASSWD_E_INVALID_FORMAT
                );
            }
            @list($user, $pass, $system) = $user;
            $this->_users[$user]['passwd'] = $pass;
            if (!empty($system)) {
                $this->_users[$user]['system'] = $system;
            }
        }
        $this->_contents = array();
        return true;
    }

    /**
    * Add an user
    *
    * The username must start with an alphabetical character and must NOT
    * contain any other characters than alphanumerics, the underline and dash.
    * 
    * Returns a PEAR_Error if:
    *   o user already exists
    *   o user or system_user contains illegal characters
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    * @param  string    $user           the name of the user to add
    * @param  string    $pass           the password of the user tot add
    * @param  string    $system_user    the systemuser this user maps to
    */
    function addUser($user, $pass, $system_user = '')
    {
        if ($this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_ALREADY_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_ALREADY
            );
        }
        if (!preg_match($this->_pcre, $user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'User ', $user),
                FILE_PASSWD_E_INVALID_CHARS
            );
        }
        @setType($system_user, 'string');
        if (!empty($system_user) && !preg_match($this->_pcre, $system_user)) {
            return PEAR::raiseError(
                sprintf(
                    FILE_PASSWD_E_INVALID_CHARS_STR, 
                    'System user ', 
                    $system_user
                ),
                FILE_PASSWD_E_INVALID_CHARS
            );
        }
        $this->_users[$user]['passwd'] = $this->generatePassword($pass);
        $this->_users[$user]['system'] = $system_user;
        return true;
    }
    
    /**
    * Verify the password of a certain user
    *
    * Returns a PEAR_Error if the user doesn't exist.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true if passwords equal, false ifthe don't or PEAR_Error
    * @param  string    $user   user whose password should be verified
    * @param  string    $pass   the plaintext password that should be verified
    */
    function verifyPasswd($user, $pass)
    {
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        $real = $this->_users[$user]['passwd'];
        return ($real === $this->generatePassword($pass, $real));
    }
    
    /**
    * Change the password of a certain user
    *
    * Returns a PEAR_Error if user doesn't exist.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    */
    function changePasswd($user, $pass)
    {
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        $this->_users[$user]['passwd'] = $this->generatePassword($pass);
        return true;
    }
    
    /**
    * Change the corresponding system user of a certain cvs user
    *
    * Returns a PEAR_Error if:
    *   o user doesn't exist
    *   o system user contains illegal characters
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    */
    function changeSysUser($user, $system)
    {
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        if (!preg_match($this->_pcre, $system)) {
            return PEAR::raiseError(
                sprintf(
                    FILE_PASSWD_E_INVALID_CHARS_STR, 
                    'System user ', 
                    $system_user
                ),
                FILE_PASSWD_E_INVALID_CHARS
            );
        }
        $this->_users[$user]['system'] = $system;
        return true;
    }
    
    /**
    * Generate crypted password
    *
    * @static
    * @access public
    * @return string    the crypted password
    * @param  string    $pass   new plaintext password
    * @param  string    $salt   new crypted password from which to gain the salt
    */
    function generatePasswd($pass, $salt = null)
    {
        return File_Passwd::crypt_des($pass, $salt);
    }
    
    /**
     * @ignore
     * @deprecated
     */
    function generatePassword($pass, $salt = null)
    {
        return File_Passwd_Cvs::generatePasswd($pass, $salt);
    }
}
?>