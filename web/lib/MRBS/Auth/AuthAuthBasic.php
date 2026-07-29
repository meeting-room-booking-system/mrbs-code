<?php
namespace MRBS\Auth;

use Exception;

/**
 * Authentication scheme that uses an Apache "auth basic" password file for user authentication.
 *
 * To use this authentication scheme, set the following things in config.inc.php:
 *
 *     $auth["type"] = "auth_basic";
 *     $auth["auth_basic"]["passwd_file"] = "/etc/httpd/htpasswd"; // Example
 *     $auth["auth_basic"]["mode"] = "des"; // The mode of encryption used in
 *                                          // the file. Must be one of:
 *                                          // 'des', 'sha' or 'md5'.
 *
 * Then, you may configure admin users:
 *
 *     $auth["admin"][] = "username1";
 *     $auth["admin"][] = "username2";
 */
class AuthAuthBasic extends Auth
{
  public function __construct()
  {
    global $auth;

    if (!isset($auth['auth_basic']['passwd_file']))
    {
      throw new Exception("auth_basic: passwd file not specified");
    }

    if (!isset($auth['auth_basic']['mode']))
    {
      throw new Exception("auth_basic: mode not specified");
    }
  }


  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    global $auth;

    // Check if we do not have a username/password
    if(!isset($user) || !isset($pass))
    {
      return false;
    }

    require_once "File/Passwd/Authbasic.php";

    $f = &File_Passwd::factory('Authbasic');
    $f->setFile($auth["auth_basic"]["passwd_file"]);
    $f->setMode($auth["auth_basic"]["mode"]);
    $f->load();

    if ($f->verifyPasswd($user, $pass) === true)
    {
      return $user;
    }

    return false;
  }

}
