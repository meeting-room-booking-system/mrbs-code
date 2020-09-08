<?php
namespace MRBS;


class UserDb extends User
{

  public static function getUserByUsername($username)
  {
    $sql = "SELECT *
              FROM " . _tbl(self::TABLE_NAME) . "
             WHERE name=:name
               AND auth_type = 'db'
             LIMIT 1";

    $result = db()->query($sql, array(':name' => $username));

    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }

    $user = new self();
    $user->load($result->next_row_keyed());
    $user->username = $user->name;

    return $user;
  }


  // Gets the number of admins in the system
  public static function getNAdmins()
  {
    global $max_level;

    $sql = "SELECT COUNT(*)
              FROM " . _tbl(self::TABLE_NAME) . "
             WHERE level=?
               AND auth_type='db'";

    return db()->query1($sql, array($max_level));
  }

}
