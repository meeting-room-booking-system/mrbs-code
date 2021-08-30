<?php
namespace MRBS\Auth;

use \MRBS\User;

// For use with mod_idcheck (http://idcheck.sourceforge.net/)
// Must have $auth['session'] set to 'remote_user'.

class AuthIdcheck extends AuthNone
{

  public function __construct()
  {
    global $auth;

    if ($auth['session'] != 'remote_user')
    {
      $message = 'MRBS configuration error.  If $auth["type"] is set to "idcheck"' .
                 ' then $auth["session"] must be set to "remote_user"';
      die($message);
    }
  }


  /* validateUser($user, $pass)
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
  public function validateUser(?string $user, ?string $pass)
  {
    // Method provided for completeness as it's an abstract method.
    // However it's not used by the 'remote_user' session scheme.
    return $user;
  }


  public function getUser(string $username) : ?User
  {
    global $server;

    $user = new User($username);

    // We only know the details of the currently logged in user
    if (isset($username) && isset($server['REMOTE_USER']) && ($username == $server['REMOTE_USER']))
    {
      $user->display_name = $server['IDCHECK_NAME'];
      $user->email = $server['IDCHECK_MAIL'];
    }
    else
    {
      $user->display_name = $username;
      $user->email = '';
    }

    return $user;
  }

}
