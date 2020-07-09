<?php
namespace MRBS\Auth;

/*
 * Authentication scheme that uses an password hash file as the source
 * for user authentication.
 *
 * This supports any password hash format that your installation of PHP
 * supports.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["type"]   = "crypt";
 * $auth["crypt"]["passwd_file] = "/etc/httpd/mrbs_passwd";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "username1";
 * $auth["admin"][] = "username2";
 *
 */

class AuthCrypt extends Auth
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

    if (!isset($auth["crypt"]["passwd_file"]))
    {
      error_log("auth_crypt: passwd file not specified");
      return false;
    }

    $fh = fopen($auth["crypt"]["passwd_file"], "r");
    if (!$fh)
    {
      error_log("auth_crypt: couldn't open passwd file\n");
      return false;
    }

    $ret = false; // Default to failure
    while ($line = fgets($fh))
    {
      if (preg_match("/^\Q$user\E:(.*)/", $line, $matches))
      {
        if (password_verify($pass, $matches[1]))
        {
          $ret = $user; // Success!
        }
      }
    }

    fclose($fh);
    return $ret;
  }

}
