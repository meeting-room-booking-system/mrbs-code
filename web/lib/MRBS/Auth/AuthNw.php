<?php
namespace MRBS\Auth;


/**
 * Authentication scheme for Netware servers.  This is like AuthExt except that
 * the program is always invoked as
 *
 *     $auth["prog"] -S <$auth["params"]> -U <username>
 *
 * and the password is passed to the program on its standard input.
 */
class AuthNw extends AuthExt
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

    $arguments = array_merge([$auth["prog"], '-S'], $this->getParams(), ['-U', $user]);

    // Run the program, sending the password to stdin.
    if ($this->run($arguments, $pass) == 0)
    {
      return $user;
    }

    // return failure
    return false;
  }

}
