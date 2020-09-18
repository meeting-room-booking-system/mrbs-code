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


  public function getUser($username)
  {
    global $server;

    $user = new User($username);
    $user->level = $this->getDefaultLevel($username);

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
