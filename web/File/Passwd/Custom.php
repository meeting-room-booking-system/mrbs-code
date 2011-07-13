<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::Passwd::Custom
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
 * @version    CVS: $Id: Custom.php,v 1.10 2005/03/30 18:33:33 mike Exp $
 * @link       http://pear.php.net/package/File_Passwd
 */

/**
* Requires File::Passwd::Common
*/
require_once 'File/Passwd/Common.php';

/** 
* Manipulate custom formatted passwd files
*
* Usage Example:
* <code>
* $cust = &File_Passwd::factory('Custom');
* $cust->setDelim('|');
* $cust->load();
* $cust->setEncFunc(array('File_Passwd', 'crypt_apr_md5'));
* $cust->addUser('mike', 'pass');
* $cust->save();
* </code>
* 
* @author   Michael Wallner <mike@php.net>
* @version  $Revision: 1.10 $
* @access   public
*/
class File_Passwd_Custom extends File_Passwd_Common
{
    /**
    * Delimiter
    *
    * @access   private
    * @var      string
    */
    var $_delim = ':';
    
    /**
    * Encryption function
    *
    * @access   private
    * @var      string
    */
    var $_enc = array('File_Passwd', 'crypt_md5');
    
    /**
    * 'name map'
    *
    * @access   private
    * @var      array
    */
    var $_map = array();
    
    /**
    * Whether to use the 'name map' or not
    *
    * @var      boolean
    * @access   private
    */
    var $_usemap = false;

    /**
    * Constructor
    *
    * @access   protected
    * @return   object
    */
    function File_Passwd_Custom($file = 'passwd')
    {
        $this->__construct($file);
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
    *   o invalid encryption function <var>$opts[0]</var>,
    *     or no delimiter character <var>$opts[1]</var> was provided
    *
    * @throws   PEAR_Error  FILE_PASSWD_E_UNDEFINED |
    *                       FILE_PASSWD_E_FILE_NOT_OPENED |
    *                       FILE_PASSWD_E_FILE_NOT_LOCKED |
    *                       FILE_PASSWD_E_FILE_NOT_UNLOCKED |
    *                       FILE_PASSWD_E_FILE_NOT_CLOSED |
    *                       FILE_PASSWD_E_INVALID_ENC_MODE
    * @static   call this method statically for a reasonable fast authentication
    * @access   public
    * @return   mixed   Returns &true; if authenticated, &false; if not or 
    *                   <classname>PEAR_Error</classname> on failure.
    * @param    string  $file   path to passwd file
    * @param    string  $user   user to authenticate
    * @param    string  $pass   plaintext password
    * @param    array   $otps   encryption function and delimiter charachter
    *                           (in this order)
    */
    function staticAuth($file, $user, $pass, $opts)
    {
        setType($opts, 'array');
        if (count($opts) != 2 || empty($opts[1])) {
            return PEAR::raiseError('Insufficient options.', 0);
        }
        
        $line = File_Passwd_Common::_auth($file, $user, $opts[1]);
        
        if (!$line || PEAR::isError($line)) {
            return $line;
        }
        
        list(,$real)= explode($opts[1], $line);
        $crypted    = File_Passwd_Custom::_genPass($pass, $real, $opts[0]);
        
        if (PEAR::isError($crypted)) {
            return $crypted;
        }
        
        return ($crypted === $real);
    }

    /**
    * Set delimiter
    * 
    * You can set a custom char to delimit the columns of a data set.
    * Defaults to a colon (':'). Be aware that this char mustn't be
    * in the values of your data sets.
    *
    * @access   public
    * @return   void
    * @param    string  $delim  custom delimiting character
    */
    function setDelim($delim = ':')
    {
        @setType($delim, 'string');
        if (empty($delim)) {
            $this->_delim = ':';
        } else {
            $this->_delim = $delim{0};
        }
    }
    
    /**
    * Get custom delimiter
    *
    * @access   public
    * @return   string
    */
    function getDelim()
    {
        return $this->_delim;
    }
    
    /**
    * Set encryption function
    *
    * You can set a custom encryption function to use.
    * The supplied function will be called by php's call_user_function(), 
    * so you can supply an array with a method of a class/object, too 
    * (i.e. array('File_Passwd', 'crypt_apr_md5').
    * 
    * 
    * @throws   PEAR_Error          FILE_PASSWD_E_INVALID_ENC_MODE
    * @access   public
    * @return   mixed   Returns &true; on success or 
    *                   <classname>PEAR_Error</classname> on failure.
    * @param    mixed   $function    callable encryption function
    */
    function setEncFunc($function = array('File_Passwd', 'crypt_md5'))
    {
        if (!is_callable($function)) {
            if (is_array($function)) {
                $function = implode('::', $function);
            }
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_ENC_MODE_STR, $function),
                FILE_PASSWD_E_INVALID_ENC_MODE
            );
        }
        
        $this->_enc = $function;
        return true;
    }
    
    /**
    * Get current custom encryption method
    *
    * Possible return values (examples):
    *   o 'md5'
    *   o 'File_Passwd::crypt_md5'
    * 
    * @access   public
    * @return   string
    */
    function getEncFunc()
    {
        if (is_array($this->_enc)) {
            return implode('::', $this->_enc);
        }
        return $this->_enc;
    }
    
    /**
    * Whether to use the 'name map' of the extra properties or not
    * 
    * @see      File_Passwd_Custom::useMap()
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
    * @throws   PEAR_Error  FILE_PASSWD_E_PARAM_MUST_BE_ARRAY
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
    * @access   public
    * @return   array
    */
    function getMap()
    {
        return $this->_map;
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
    * @throws   PEAR_Error  FILE_PASSWD_E_FILE_NOT_OPENED |
    *                       FILE_PASSWD_E_FILE_NOT_LOCKED |
    *                       FILE_PASSWD_E_FILE_NOT_UNLOCKED |
    *                       FILE_PASSWD_E_FILE_NOT_CLOSED
    * @access   public
    * @return   mixed   Returns &true; on success or 
    *                   <classname>PEAR_Error</classname> on failure.
    */
    function save()
    {
        $content = '';
        foreach ($this->_users as $user => $array){
            $pass   = array_shift($array);
            $extra  = implode($this->_delim, $array);
            $content .= $user . $this->_delim . $pass;
            if (!empty($extra)) {
                $content .= $this->_delim . $extra;
            }
            $content .= "\n";
        }
        return $this->_save($content);
    }

    /**
    * Parse the Custom password file
    *
    * Returns a PEAR_Error if passwd file has invalid format.
    * 
    * @throws   PEAR_Error  FILE_PASSWD_E_INVALID_FORMAT
    * @access   public
    * @return   mixed   Returns &true; on success or
    *                   <classname>PEAR_Error</classname> on failure.
    */
    function parse()
    {
        $this->_users = array();
        foreach ($this->_contents as $line){
            $parts = explode($this->_delim, $line);
            if (count($parts) < 2) {
                return PEAR::raiseError(
                    FILE_PASSWD_E_INVALID_FORMAT_STR,
                    FILE_PASSWD_E_INVALID_FORMAT
                );
            }
            $user = array_shift($parts);
            $pass = array_shift($parts);
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
    * Returns a PEAR_Error if:
    *   o user already exists
    *   o user contains illegal characters
    *   o encryption mode is not supported
    *   o any element of the <var>$extra</var> array contains the delimiter char
    * 
    * @throws   PEAR_Error  FILE_PASSWD_E_EXISTS_ALREADY |
    *                       FILE_PASSWD_E_INVALID_ENC_MODE |
    *                       FILE_PASSWD_E_INVALID_CHARS
    * @access   public
    * @return   mixed   Returns &true; on success or 
    *                   <classname>PEAR_Error</classname> on failure.
    * @param    string  $user   the name of the user to add
    * @param    string  $pass   the password of the user to add
    * @param    array   $extra  extra properties of user to add
    */
    function addUser($user, $pass, $extra = array())
    {
        if ($this->userExists($user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_EXISTS_ALREADY_STR, 'User ', $user),
                FILE_PASSWD_E_EXISTS_ALREADY
            );
        }
        if (!preg_match($this->_pcre, $user) || strstr($user, $this->_delim)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'User ', $user),
                FILE_PASSWD_E_INVALID_CHARS
            );
        }
        if (!is_array($extra)) {
            setType($extra, 'array');
        }
        foreach ($extra as $e){
            if (strstr($e, $this->_delim)) {
                return PEAR::raiseError(
                    sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'Property ', $e),
                    FILE_PASSWD_E_INVALID_CHARS
                );
            }
        }
        
        $pass = $this->_genPass($pass);
        if (PEAR::isError($pass)) {
            return $pass;
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
    *   o any property contains the custom delimiter character
    * 
    * @see      changePasswd()
    * 
    * @throws   PEAR_Error  FILE_PASSWD_E_EXISTS_NOT | 
    *                       FILE_PASSWD_E_INVALID_CHARS
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
            if (strstr($value, $this->_delim)) {
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
    *   o encryption mode is not supported
    * 
    * @throws   PEAR_Error  FILE_PASSWD_E_EXISTS_NOT |
    *                       FILE_PASSWD_E_INVALID_ENC_MODE
    * @access   public
    * @return   mixed   Returns &true; on success or 
    *                   <classname>PEAR_Error</classname> on failure.
    * @param    string  $user   the user whose password should be changed
    * @param    string  $pass   the new plaintext password
    */
    function changePasswd($user, $pass)
    {
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
    * @throws   PEAR_Error  FILE_PASSWD_E_EXISTS_NOT |
    *                       FILE_PASSWD_E_INVALID_ENC_MODE
    * @access   public
    * @return   mixed   Returns &true; if passwors equal, &false; if they don't 
    *                   or <classname>PEAR_Error</classname> on fialure.
    * @param    string  $user   the user whose password should be verified
    * @param    string  $pass   the password to verify
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
    * @throws   PEAR_Error  FILE_PASSWD_E_INVALID_ENC_MODE
    * @access   private
    * @return   mixed   Returns the crypted password or 
    *                   <classname>PEAR_Error</classname>
    * @param    string  $pass   the plaintext password
    * @param    string  $salt   the crypted password from which to gain the salt
    * @param    string  $func   the encryption function to use
    */
    function _genPass($pass, $salt = null, $func = null)
    {
        if (is_null($func)) {
            $func = $this->_enc;
        }
        
        if (!is_callable($func)) {
            if (is_array($func)) {
                $func = implode('::', $func);
            }
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_ENC_MODE_STR, $func),
                FILE_PASSWD_E_INVALID_ENC_MODE
            );
        }
        
        $return = @call_user_func($func, $pass, $salt);
        
        if (is_null($return) || $return === false) {
            $return = @call_user_func($func, $pass);
        }
        
        return $return;
    }
}
?>