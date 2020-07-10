<?php
namespace MRBS\Auth;


class AuthConfig extends Auth
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
    if(!isset($user) || !isset($pass) || strlen($pass)==0)
    {
      return false;
    }

    if ((isset($auth["user"][$user]) &&
        ($auth["user"][$user] == $pass)
      ) ||
      (isset($auth["user"][\MRBS\utf8_strtolower($user)]) &&
        ($auth["user"][\MRBS\utf8_strtolower($user)] == $pass)
      ))
    {
      return $user;    // User validated
    }

    return false;      // User unknown or password invalid
  }


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
