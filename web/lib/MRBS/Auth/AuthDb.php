<?php
namespace MRBS\Auth;

use MRBS\User;

class AuthDb extends Auth
{
  
  public function getUser($username)
  {
    global $tbl_users;
    
    $sql = "SELECT * FROM $tbl_users WHERE name=:name LIMIT 1";
    $result = \MRBS\db()->query($sql, array(':name' => $username));
    
    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }
    
    // The username does exist - return a User object
    $data = $result->row_keyed(0);

    $user = new User($username);

    // $user->level will be set as part of this
    foreach ($data as $key => $value)
    {
      if ($key == 'name')
      {
        // This has already been set as the 'username' property;
        continue;
      }
      $user->$key = $value;
    }
    
    // We don't yet have a displayname field in the 'db' scheme, so make it the username
    $user->display_name = $user->username;
    
    return $user;
  }
  
}