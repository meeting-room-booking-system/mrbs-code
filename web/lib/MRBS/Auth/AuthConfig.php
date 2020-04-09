<?php
namespace MRBS\Auth;


class AuthConfig extends Auth
{
  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames()
  {
    global $auth;

    $result = array();

    foreach ($auth['user'] as $user => $password)
    {
      $result[] = array('username'     => $user,
        'display_name' => $user);
    }

    // Need to sort the users
    self::sortUsers($result);

    return $result;
  }
}
