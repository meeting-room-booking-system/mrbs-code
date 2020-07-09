<?php
namespace MRBS\Auth;

/*
 * Authentication scheme that uses an Apache "auth basic" password file
 * for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["type"]   = "auth_basic";
 * $auth["auth_basic"]["passwd_file] = "/etc/httpd/htpasswd"; // Example
 * $auth["auth_basic"]["mode"] = "des"; // The mode of encryption used in
 *                                      // the file. Must be one of:
 *                                      // 'des', 'sha' or 'md5'.
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "username1";
 * $auth["admin"][] = "username2";
 *
 */

class AuthAuthBasic extends Auth
{
  /* authValidateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The user name
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public function validateUser($user, $pass)
  {
    global $auth;

    // Check if we do not have a username/password
    if(!isset($user) || !isset($pass))
    {
      return false;
    }

    if (!isset($auth["auth_basic"]["passwd_file"]))
    {
      error_log("auth_basic: passwd file not specified");
      return false;
    }

    if (!isset($auth["auth_basic"]["mode"]))
    {
      error_log("auth_basic: mode not specified");
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
