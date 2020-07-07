<?php
namespace MRBS\Auth;

use MRBS\User;

/*
 * Authentication scheme that uses an external script as the source
 * for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"]  = "MRBS";    // Or any other string
 * $auth["type"]   = "ext";
 * $auth["prog"]   = "authenticationprogram"; // The full path to the external
 *                                            // script
 * $auth["params"] = "<...>"                  // Parameters to pass to
 *                                            // the script, #USERNAME#
 *                                            // and #PASSWORD#
 *                                            // will be expanded to
 *                                            // the values typed by
 *                                            // the user.
 *
 *                                            // e.g.
 *                                            // "/etc/htpasswd #USERNAME# #PASSWORD#"
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "username1";
 * $auth["admin"][] = "username2";
 *
 */

class AuthExt extends Auth
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

    // Generate the command line
    $cmd = $auth["prog"] . ' ' . $auth["params"];
    $cmd = str_replace('#USERNAME#', escapeshellarg($user), $cmd);
    $cmd = str_replace('#PASSWORD#', escapeshellarg($pass), $cmd);

    // Run the program
    exec($cmd, $output, $ret);

    // If it succeeded, return success
    if ($ret == 0)
    {
      return $user;
    }

    // return failure
    return false;
  }

}
