<?php
namespace MRBS\Auth;

use \MRBS\User;


abstract class Auth
{
  public function getUser($username)
  {
    $user = new User($username);
    $user->level = $this->getLevel($username);
     
    return $user;
  }
  
  
  protected function getLevel($username)
  {
    global $auth;

    // User not logged in, user level '0'
    if(!isset($username))
    {
      return 0;
    }

    // Check whether the user is an admin
    foreach ($auth['admin'] as $admin)
    {
      if(strcasecmp($username, $admin) === 0)
      {
        return 2;
      }
    }

    // Everybody else is access level '1'
    return 1;
  }
  
}