<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * File::Passwd::Authdigest
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
 * @version    CVS: $Id: Authdigest.php,v 1.13 2005/09/27 06:26:08 mike Exp $
 * @link       http://pear.php.net/package/File_Passwd
 */

/**
* Requires File::Passwd::Common
*/
require_once 'File/Passwd/Common.php';

/**
* Manipulate AuthDigestFiles as used for HTTP Digest Authentication.
*
* <kbd><u>
*   Usage Example:
* </u></kbd>
* <code>
*   $htd = &File_Passwd::factory('Authdigest');
*   $htd->setFile('/www/mike/auth/.htdigest');
*   $htd->load();
*   $htd->addUser('mike', 'myRealm', 'secret');
*   $htd->save();
* </code>
*
* <kbd><u>
*   Output of listUser()
* </u></kbd>
* <pre>
*      array
*       + user  => array
*                   + realm => crypted_passwd
*                   + realm => crypted_passwd
*       + user  => array
*                   + realm => crypted_passwd
* </pre>
* 
* @author   Michael Wallner <mike@php.net>
* @package  File_Passwd
* @version  $Revision: 1.13 $
* @access   public
*/
class File_Passwd_Authdigest extends File_Passwd_Common
{
    /** 
    * Path to AuthDigestFile
    *
    * @var string
    * @access private
    */
    var $_file = '.htdigest';

    /** 
    * Constructor
    * 
    * @access public
    * @param string $file       path to AuthDigestFile
    */
    function File_Passwd_Authdigest($file = '.htdigest')
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
    * 
    * @throws   PEAR_Error
    * @access   public
    * @return   mixed   true if authenticated, false if not or PEAR_Error
    * @param    string  $file   path to passwd file
    * @param    string  $user   user to authenticate
    * @param    string  $pass   plaintext password
    * @param    string  $realm  the realm the user is in
    */
    function staticAuth($file, $user, $pass, $realm)
    {
        $line = File_Passwd_Common::_auth($file, $user.':'.$realm);
        if (!$line || PEAR::isError($line)) {
            return $line;
        }
        @list(,,$real)= explode(':', $line);
        return (md5("$user:$realm:$pass") === $real);
    }
    
    /** 
    * Apply changes and rewrite AuthDigestFile
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
    * @return mixed true on success or a PEAR_Error
    */
    function save()
    {
        $content = '';
        if (count($this->_users)) {
            foreach ($this->_users as $user => $realm) {
                foreach ($realm as $r => $pass){
                  $content .= "$user:$r:$pass\n";
                }
            }
        }
        return $this->_save($content);
    }

    /** 
    * Add an user
    *
    * Returns a PEAR_Error if:
    *   o the user already exists in the supplied realm
    *   o the user or realm contain illegal characters
    * 
    * $user and $realm must start with an alphabetical charachter and must NOT
    * contain any other characters than alphanumerics, the underline and dash.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or a PEAR_Error
    * @param string $user   the user to add
    * @param string $realm  the realm the user should be in
    * @param string $pass   the plaintext password
    */
    function addUser($user, $realm, $pass)
    {
        if ($this->userInRealm($user, $realm)) {
            return PEAR::raiseError(
                "User '$user' already exists in realm '$realm'.", 0
            );
        }
        if (!preg_match($this->_pcre, $user)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'User ', $user),
                FILE_PASSWD_E_INVALID_CHARS
            );
        }
        if (!preg_match($this->_pcre, $realm)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_INVALID_CHARS_STR, 'Realm ', $realm),
                FILE_PASSWD_E_INVALID_CHARS
            );
        }
        $this->_users[$user][$realm] = md5("$user:$realm:$pass");
        return true;
    }

    /**
    * List all user of (a | all) realm(s)
    * 
    * Returns:
    *   o associative array of users of ONE realm if $inRealm was supplied
    *     <pre>
    *       realm1
    *        + user1 => pass
    *        + user2 => pass
    *        + user3 => pass
    *     </pre>
    *   o associative array of all realms with all users
    *     <pre>
    *       array
    *        + realm1 => array
    *                     + user1 => pass
    *                     + user2 => pass
    *                     + user3 => pass
    *        + realm2 => array
    *                     + user3 => pass
    *        + realm3 => array
    *                     + user1 => pass
    *                     + user2 => pass
    *     </pre>
    * 
    * @access public
    * @return array
    * @param string $inRealm    the realm to list users of;
    *                           if omitted, you'll get all realms
    */
    function listUserInRealm($inRealm = '')
    {
        $result = array();
        foreach ($this->_users as $user => $realms){
            foreach ($realms as $realm => $pass){
                if (!empty($inRealm) && ($inRealm !== $realm)) {
                    continue;
                }
                if (!isset($result[$realm])) {
                    $result[$realm] = array();
                }
                $result[$realm][$user] = $pass;
            }
        }
        return $result;
    }
    
    /** 
    * Change the password of a certain user
    *
    * Returns a PEAR_Error if:
    *   o user doesn't exist in the supplied realm
    *   o user or realm contains illegal characters
    * 
    * This method in fact adds the user whith the new password
    * after deleting the user.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or a PEAR_Error
    * @param string $user   the user whose password should be changed
    * @param string $realm  the realm the user is in
    * @param string $pass   the new plaintext password
    */
    function changePasswd($user, $realm, $pass)
    {
        if (PEAR::isError($error = $this->delUserInRealm($user, $realm))) {
            return $error;
        } else {
            return $this->addUser($user, $realm, $pass);
        }
    }

    /** 
    * Verifiy password
    *
    * Returns a PEAR_Error if the user doesn't exist in the supplied realm.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true if passwords equal, false if they don't, or PEAR_Error
    * @param string $user   the user whose password should be verified
    * @param string $realm  the realm the user is in
    * @param string $pass   the plaintext password to verify
    */
    function verifyPasswd($user, $realm, $pass)
    {
        if (!$this->userInRealm($user, $realm)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_USER_NOT_IN_REALM_STR, $user, $realm),
                FILE_PASSWD_E_USER_NOT_IN_REALM
            );
        }
        return ($this->_users[$user][$realm] === md5("$user:$realm:$pass"));
    }

    /**
    * Ckeck if a certain user is in a specific realm
    * 
    * @throws PEAR_Error
    * @access public
    * @return boolean
    * @param string $user   the user to check
    * @param string $realm  the realm the user shuold be in
    */
    function userInRealm($user, $realm)
    {
      return (isset($this->_users[$user][$realm]));
    }
    
    /**
    * Delete a certain user in a specific realm
    *
    * Returns a PEAR_Error if <var>$user</var> doesn't exist <var>$inRealm</var>.
    * 
    * @throws PEAR_Error
    * @access public
    * @return mixed true on success or PEAR_Error
    * @param  string    $user       the user to remove
    * @param  string    $inRealm    the realm the user should be in
    */
    function delUserInRealm($user, $inRealm)
    {
        if (!$this->userInRealm($user, $inRealm)) {
            return PEAR::raiseError(
                sprintf(FILE_PASSWD_E_USER_NOT_IN_REALM_STR, $user, $inRealm),
                FILE_PASSWD_E_USER_NOT_IN_REALM
            );
        }
        unset($this->_users[$user][$inRealm]);
        return true;
    }
    
    /** 
    * Parse the AuthDigestFile
    *
    * Returns a PEAR_Error if AuthDigestFile has invalid format.
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
            if (count($user) != 3) {
                return PEAR::raiseError(
                    FILE_PASSWD_E_INVALID_FORMAT_STR,
                    FILE_PASSWD_E_INVALID_FORMAT
                );
            }
            list($user, $realm, $pass) = $user;
            $this->_users[$user][$realm] = trim($pass);
        }
        $this->_contents = array();
        return true;
    }
    
    /**
    * Generate Password
    *
    * @static
    * @access   public
    * @return   string  The crypted password.
    * @param    string  $user The username.
    * @param    string  $realm The realm the user is in.
    * @param    string  $pass The plaintext password.
    */
    function generatePasswd($user, $realm, $pass)
    {
        return md5("$user:$realm:$pass");
    }
    
    /**
     * @ignore
     * @deprecated
     */
    function generatePassword($user, $realm, $pass)
    {
        return File_Passwd_Authdigest::generatePasswd($user, $realm, $pass);
    }
}
?>