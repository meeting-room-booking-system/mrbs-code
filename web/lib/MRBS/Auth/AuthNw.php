<?php
namespace MRBS\Auth;


class AuthNw extends Auth
{
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
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
