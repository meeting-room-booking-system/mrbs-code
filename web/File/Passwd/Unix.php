<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::Passwd::Unix
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
 * @version    CVS: $Id: Unix.php,v 1.17 2005/03/30 18:33:33 mike Exp $
 * @link       http://pear.php.net/package/File_Passwd
 */

/**
* Requires File::Passwd::Common
*/
require_once 'File/Passwd/Common.php';

/**
* Manipulate standard Unix passwd files.
* 
* <kbd><u>Usage Example:</u></kbd>
* <code>
*   $passwd = &File_Passwd::factory('Unix');
*   $passwd->setFile('/my/passwd/file');
*   $passwd->load();
*   $passwd->addUser('mike', 'secret');
*   $passwd->save();
* </code>
* 
* 
* <kbd><u>Output of listUser()</u></kbd>
* # using the 'name map':
* <pre>
*      array
*       + user  => array
*                   + pass  => crypted_passwd or 'x' if shadowed
*                   + uid   => user id
*                   + gid   => group id
*                   + gecos => comments
*                   + home  => home directory
*                   + shell => standard shell
* </pre>
* # without 'name map':
* <pre>
*      array
*       + user  => array
*                   + 0  => crypted_passwd
*                   + 1  => ...
*                   + 2  => ...
* </pre>
* 
* @author   Michael Wallner <mike@php.net>
* @package  File_Passwd
* @version  $Revision: 1.17 $
* @access   public
*/
class File_Passwd_Unix extends File_Passwd_Common
{
    /**
    * A 'name map' wich refer to the extra properties
    *
    * @var array
    * @access private
    */
    var $_map = array('uid', 'gid', 'gecos', 'home', 'shell');
    
    /**
    * Whether to use the 'name map' or not
    *
    * @var boolean
    * @access private
    */
    var $_usemap = true;
    
    /**
    * Whether the passwords of this passwd file are shadowed in another file
    *
    * @var boolean
    * @access private
    */
    var $_shadowed = false;
    
    /**
    * Encryption mode, either md5 or des
    *
    * @var string
    * @access private
    */
    var $_mode = 'des';
    
    /**
    * Supported encryption modes
    * 
    * @var array
    * @access private
    */
    var $_modes = array('md5' => 'md5', 'des' => 'des');
    
    /**
    * Constructor
    *
    * @access public
    * @param  string    $file   path to passwd file
    */
    function File_Passwd_Unix($file = 'passwd')
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
    *   o invalid encryption mode <var>$mode</var> was provided
    *
    * @static   call this method statically for a reasonable fast authentication
    * @access   public
    * @return   mixed   true if authenticated, false if not or PEAR_Error
    * @param    string  $file   path to passwd file
    * @param    string  $user   user to authenticate
    * @param    string  $pass   plaintext password
    * @param    string  $mode   encryption mode to use (des or md5)
    */
    function staticAuth($file, $user, $pass, $mode)
    {
        $line = File_Passwd_Common::_auth($file, $user);
        if (!$line || PEAR::isError($line)) {
            return $line;
        }
        list(,$real)= explode(':', $line);
        $crypted    = File_Passwd_Unix::_genPass($pass, $real, $mode);
        if (PEAR::isError($crypted)) {
            return $crypted;
        }
        return ($crypted === $real);
    }
    
    /**
    * Apply changes an rewrite passwd file
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
        foreach ($this->_users as $user => $array){
            $pass   = array_shift($array);
            $extra  = implode(':', $array);
            $content .= $user . ':' . $pass;
            if (!empty($extra)) {
                $content .= ':' . $extra;
            }
            $content .= "\n";
        }
        return $this->_save($content);
    }
    
    /**
    * Parse the Unix password file
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
        foreach ($this->_contents as $line){
            $parts = explode(':', $line);
            if (count($parts) < 2) {
                return PEAR::raiseError(
                    FILE_PASSWD_E_INVALID_FORMAT_STR,
                    FILE_PASSWD_E_INVALID_FORMAT
                );
            }
            $user = array_shift($parts);
            $pass = array_shift($parts);
            if ($pass == 'x') {
                $this->_shadowed = true;
            }
            $values = array();
            if ($this->_usemap) {
                $values['pass'] = $pass;
                foreach ($parts as $i => $value){
                    if (isset($this->_map[$i])) {
                        $values[$this->_map[$i]] = $value;
                    } else {
                        $values[$i+1] = $value;
                    }
                }
            } else {
                $values = array_merge(array($pass), $parts);
            }
            $this->_users[$user] = $values;
            
        }
        $this->_contents = array();
        return true;
    }
    
    /**
    * Set the encryption mode
    * 
    * Supported encryption modes are des and md5.
    * 
    * Returns a PEAR_Error if supplied encryption mode is not supported.
    *
    * @see      setMode()
    * @see      listModes()
    * 
    * @throws   PEAR_Error
    * @access   public
    * @return   mixed   true on succes or PEAR_Error
    * @param    string  $mode   encryption mode to use; either md5 or des
    */
    function setMode($mode)
    {
        $mode = utf8_strtolower($mode);
        if (!isset($this->_modes[$mode])) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_ENC_MODE_STR, $mode),
                FILE_PASSWD_E_INVALID_ENC_MODE
            );
        }
        $this->_mode = $mode;
        return true;
    }
    
    /** 
    * Get supported encryption modes
    *
    * <pre>
    *   array
    *    + md5
    *    + des
    * </pre>
    * 
    * @see      setMode()
    * @see      getMode()
    * 
    * @access   public
    * @return   array
    */
    function listModes()
    {
        return $this->_modes;
    }

    /**
    * Get actual encryption mode
    *
    * @see      listModes()
    * @see      setMode()
    * 
    * @access   public
    * @return   string
    */
    function getMode()
    {
        return $this->_mode;
    }
    
    /**
    * Whether to use the 'name map' of the extra properties or not
    * 
    * Default Unix passwd files look like:
    * <pre>
    * user:password:user_id:group_id:gecos:home_dir:shell
    * </pre>
    * 
    * The default 'name map' for properties except user and password looks like:
    *   o uid
    *   o gid
    *   o gecos
    *   o home
    *   o shell
    * 
    * If you want to change the naming of the standard map use 
    * File_Passwd_Unix::setMap(array()).
    *
    * @see      setMap()
    * @see      getMap()
    * 
    * @access   public
    * @return   boolean always true if you set a value (true/false) OR
    *                   the actual value if called without param
    * 
    * @param    boolean $bool   whether to use the 'name map' or not
    */
    function useMap($bool = null)
    {
        if (is_null($bool)) {
            return $this->_usemap;
        }
        $this->_usemap = (bool) $bool;
        return true;
    }
    
    /**
    * Set the 'name map' to use with the extra properties of the user
    * 
    * This map is used for naming the associative array of the extra properties.
    *
    * Returns a PEAR_Error if <var>$map</var> was not of type array.
    * 
    * @see      getMap()
    * @see      useMap()
    * 
    * @throws   PEAR_Error
    * @access   public
    * @return   mixed       true on success or PEAR_Error
    */
    function setMap($map = array())
    {
        if (!is_array($map)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_PARAM_MUST_BE_ARRAY_STR, '$map'),
                FILE_PASSWD_E_PARAM_MUST_BE_ARRAY
            );
        }
        $this->_map = $map;
        return true;
    }
    
    /**
    * Get the 'name map' which is used for the extra properties of the user
    *
    * @see      setMap()
    * @see      useMap()
    * 
    * @access public
    * @return array
    */
    function getMap()
    {
        return $this->_map;
    }
    
    /**
    * If the passwords of this passwd file are shadowed in another file.
    *
    * @access public
    * @return boolean
    */
    function isShadowed()
    {
        return $this->_shadowed;
    }
    
    /**
    * Add an user
    *
    * The username must start with an alphabetical character and must NOT
    * contain any other characters than alphanumerics, the underline and dash.
    * 
    * If you use the 'name map' you should also use these naming in
    * the supplied extra array, because your values would get mixed up
    * if they are in the wrong order, which is always true if you
    * DON'T use the 'name map'!
    * 
    * So be warned and USE the 'name map'!
    * 
    * If the passwd file is shadowed, the user will be added though, but
    * with an 'x' as password, and a PEAR_Error will be returned, too.
    * 
    * Returns a PEAR_Error if:
    *   o user already exists
    *   o user contains illegal characters
    *   o encryption mode is not supported
    *   o passwords are shadowed in another file
    *   o any element of the <var>$extra</var> array contains a colon (':')
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    * @param  string    $user   the name of the user to add
    * @param  string    $pass   the password of the user to add
    * @param  array     $extra  extra properties of user to add
    */
    function addUser($user, $pass, $extra = array())
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
        if (!is_array($extra)) {
            setType($extra, 'array');
        }
        foreach ($extra as $e){
            if (strstr($e, ':')) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'Property ', $e),
                FILE_PASSWD_E_INVALID_CHARS
            );
            }
        }
        
        /**
        * If passwords of the passwd file are shadowed, 
        * the password of the user will be set to 'x'.
        */
        if ($this->_shadowed) {
            $pass = 'x';
        } else {
            $pass = $this->_genPass($pass);
            if (PEAR::isError($pass)) {
                return $pass;
            }
        }
        
        /**
        * If you don't use the 'name map' the user array will be numeric.
        */
        if (!$this->_usemap) {
            array_unshift($extra, $pass);
            $this->_users[$user] = $extra;
        } else {
            $map = $this->_map;
            array_unshift($map, 'pass');
            $extra['pass'] = $pass;
            foreach ($map as $key){
                $this->_users[$user][$key] = @$extra[$key];
            }
        }
        
        /**
        * Raise a PEAR_Error if passwords are shadowed.
        */
        if ($this->_shadowed) {
            return PEAR::raiseError(
                'Password has been set to \'x\' because they are '.
                'shadowed in another file.', 0
            );
        }
        return true;
    }
    
    /**
    * Modify properties of a certain user
    *
    * # DON'T MODIFY THE PASSWORD WITH THIS METHOD!
    * 
    * You should use this method only if the 'name map' is used, too.
    * 
    * Returns a PEAR_Error if:
    *   o user doesn't exist
    *   o any property contains a colon (':')
    * 
    * @see      changePasswd()
    * 
    * @throws   PEAR_Error
    * @access   public
    * @return   mixed       true on success or PEAR_Error
    * @param    string      $user           the user to modify
    * @param    array       $properties     an associative array of 
    *                                       properties to modify
    */
    function modUser($user, $properties = array())
    {
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        
        if (!is_array($properties)) {
            setType($properties, 'array');
        }
        
        foreach ($properties as $key => $value){
            if (strstr($value, ':')) {
                return PEAR::raiseError(
                    sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'User ', $user),
                    FILE_PASSWD_E_INVALID_CHARS
                );
            }
            $this->_users[$user][$key] = $value;
        }
        
        return true;
    }
    
    /**
    * Change the password of a certain user
    *
    * Returns a PEAR_Error if:
    *   o user doesn't exists
    *   o passwords are shadowed in another file
    *   o encryption mode is not supported
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    * @param string $user   the user whose password should be changed
    * @param string $pass   the new plaintext password
    */
    function changePasswd($user, $pass)
    {
        if ($this->_shadowed) {
            return PEAR::raiseError(
                'Passwords of this passwd file are shadowed.', 
                0
            );
        }
        
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        
        $pass = $this->_genPass($pass);
        if (PEAR::isError($pass)) {
            return $pass;
        }
        
        if ($this->_usemap) {
            $this->_users[$user]['pass'] = $pass;
        } else {
            $this->_users[$user][0] = $pass;
        }
        
        return true;
    }
    
    /**
    * Verify the password of a certain user
    * 
    * Returns a PEAR_Error if:
    *   o user doesn't exist
    *   o encryption mode is not supported
    *
    * @throws PEAR_Error
    * @access public
    * @return mixed true if passwors equal, false if they don't or PEAR_Error
    * @param  string    $user   the user whose password should be verified
    * @param  string    $pass   the password to verify
    */
    function verifyPasswd($user, $pass)
    {
        if (!$this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_NOT_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_NOT
            );
        }
        $real = 
            $this->_usemap ? 
            $this->_users[$user]['pass'] : 
            $this->_users[$user][0]
        ;
        return ($real === $this->_genPass($pass, $real));
    }
    
    /**
    * Generate crypted password from the plaintext password
    *
    * Returns a PEAR_Error if actual encryption mode is not supported.
    * 
    * @throws PEAR_Error
    * @access private
    * @return mixed     the crypted password or PEAR_Error
    * @param  string    $pass   the plaintext password
    * @param  string    $salt   the crypted password from which to gain the salt
    * @param  string    $mode   the encryption mode to use; don't set, because
    *                           it's usually taken from File_Passwd_Unix::_mode
    */
    function _genPass($pass, $salt = null, $mode = null)
    {
        static $crypters;
        if (!isset($crypters)) {
            $crypters = get_class_methods('File_Passwd');
        }
        
        $mode = !isset($mode) ? utf8_strtolower($this->_mode) : utf8_strtolower($mode);
        $func = 'crypt_' . $mode;
        
        if (!in_array($func, $crypters)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_ENC_MODE_STR, $mode),
                FILE_PASSWD_E_INVALID_ENC_MODE
            );
        }
        
        return call_user_func(array('File_Passwd', $func), $pass, $salt);
    }
    
    /**
    * Generate Password
    *
    * Returns PEAR_Error FILE_PASSD_E_INVALID_ENC_MODE if the supplied
    * encryption mode is not supported.
    *
    * @see File_Passwd
    * @static
    * @access   public
    * @return   mixed   The crypted password on success or PEAR_Error on failure.
    * @param    string  $pass The plaintext password.
    * @param    string  $mode The encryption mode to use.
    * @param    string  $salt The salt to use.
    */
    function generatePasswd($pass, $mode = FILE_PASSWD_MD5, $salt = null)
    {
        if (!isset($mode)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_ENC_MODE_STR, '<NULL>'),
                FILE_PASSWD_E_INVALID_ENC_MODE                
            );
        }
        return File_Passwd_Unix::_genPass($pass, $salt, $mode);
    }
    
    /**
     * @ignore
     * @deprecated
     */
    function generatePassword($pass, $mode = FILE_PASSWD_MD5, $salt = null)
    {
        return File_Passwd_Unix::generatePasswd($pass, $mode, $salt);
    }
    
}
