<?php
namespace MRBS\Auth;


class AuthNw extends Auth
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
    if (empty($user) || empty($pass))
    {
      return false;
    }

    // Generate the command line
    $cmd = $auth["prog"] . " -S " . $auth["params"] . " -U '$user'";

    // Run the program, sending the password to stdin.
    $p = popen($cmd, "w");

    if (!$p)
    {
      return false;
    }

    fputs($p, $pass);

    if (pclose($p) == 0)
    {
      return $user;
    }

    // return failure
    return false;
  }

}
