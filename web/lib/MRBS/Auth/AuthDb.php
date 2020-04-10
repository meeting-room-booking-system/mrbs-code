<?php
namespace MRBS\Auth;

use MRBS\User;

class AuthDb extends Auth
{
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
  public function validateUser($user, $pass)
  {
    global $tbl_users;

    // The string $user that the user logged on with could be either a username or
    // an email address, or even possibly just the local part of an email address.
    // So it's just possible that there is more than one user with this password and
    // username | email address | local-part.    If we get more than one, then we don't
    // know which user it is, so we return false.
    $valid_usernames = array();

    if (($valid_username = $this->validateUsername($user, $pass)) !== false)
    {
      $valid_usernames[] = $valid_username;
    }

    $valid_usernames = array_merge($valid_usernames, $this->validateEmail($user, $pass));
    $valid_usernames = array_unique($valid_usernames);

    if (count($valid_usernames) == 1)
    {
      $result = $valid_usernames[0];
      // Update the database with this login, but don't change the timestamp
      $now = time();
      $sql = "UPDATE $tbl_users SET last_login=?, timestamp=timestamp WHERE name=?";
      $sql_params = array($now, $result);
      \MRBS\db()->command($sql, $sql_params);
      return $result;
    }
    else
    {
      return false;
    }
  }


  /* validateUsername($user, $pass)
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
  private function validateUsername($user, $pass)
  {
    global $tbl_users;

    $sql_params = array();

    // We use syntax_casesensitive_equals() rather than just '=' because '=' in MySQL
    // permits trailing spacings, eg 'john' = 'john '.   We could use LIKE, but that then
    // permits wildcards, so we could use a comnination of LIKE and '=' but that's a bit
    // messy.  WE could use STRCMP, but that's MySQL only.

    // Usernames are unique in the users table, so we only look for one.
    $sql = "SELECT password_hash
            FROM $tbl_users
           WHERE " . \MRBS\db()->syntax_casesensitive_equals('name', \MRBS\utf8_strtolower($user), $sql_params) . "
           LIMIT 1";

    $res = \MRBS\db()->query($sql, $sql_params);

    $row = $res->next_row_keyed();

    if (!isset($row['password_hash']))
    {
      // No user found with that name
      return false;
    }

    return ($this->checkPassword($pass, $row['password_hash'], 'name', $user)) ? $user : false;
  }


  /* authValidateEmail($email, $pass)
   *
   * Checks if the specified email/password pair are valid
   *
   * $email - The email address
   * $pass  - The password
   *
   * Returns:
   *   array    - An array of valid usernames, empty if none found
   */
  private function validateEmail($email, $pass)
  {
    global $tbl_users;
    global $auth;

    $valid_usernames = array();

    $sql_params = array($email);

    // For the moment we will assume that email addresses are case insensitive.   Whilst it is true
    // on most systems, it isn't always true.  The domain is case insensitive but the local-part can
    // be case sensitive.   But before we can take account of this, the email addresses in the database
    // need to be normalised so that all the domain names are stored in lower case.  Then it will be
    // possible to do a case sensitive comparison.
    if (strpos($email, '@') === false)
    {
      if (!empty($auth['allow_local_part_email']))
      {
        // We're just checking the local-part of the email address
        $condition = "LOWER(?)=LOWER(" . \MRBS\db()->syntax_simple_split('email', '@', 1, $sql_params) .")";
      }
      else
      {
        return $valid_usernames;
      }
    }
    else
    {
      // Check the complete email address
      $condition = "LOWER(?)=LOWER(email)";
    }

    // Email addresses are not unique in the users table, so we need to find all of them.
    $sql = "SELECT password_hash, name
            FROM $tbl_users
           WHERE $condition";

    $res = \MRBS\db()->query($sql, $sql_params);

    $rows = $res->all_rows_keyed();

    // Check all the users that have this email address and password hash.
    foreach($rows as $row)
    {
      if ($this->checkPassword($pass, $row['password_hash'], 'email', $email))
      {
        $valid_usernames[] = $row['name'];
      }
    }

    return $valid_usernames;
  }


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
    $data = $result->next_row_keyed();

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


  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames()
  {
    global $tbl_users;

    $res = \MRBS\db()->query("SELECT name AS username, name AS display_name FROM $tbl_users ORDER BY name");

    return $res->all_rows_keyed();
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail()
  {
    return true;
  }


  private function rehash($password, $column_name, $column_value)
  {
    global $tbl_users;

    $sql_params = array(password_hash($password, PASSWORD_DEFAULT));

    switch ($column_name)
    {
      case 'name':
        $condition = \MRBS\db()->syntax_casesensitive_equals($column_name, \MRBS\utf8_strtolower($column_value), $sql_params);
        break;
      case 'email':
        // For the moment we will assume that email addresses are case insensitive.   Whilst it is true
        // on most systems, it isn't always true.  The domain is case insensitive but the local-part can
        // be case sensitive.   But before we can take account of this, the email addresses in the database
        // need to be normalised so that all the domain names are stored in lower case.  Then it will be possible
        // to do a case sensitive comparison.
        $sql_params[] = $column_value;
        $condition = "LOWER($column_name)=LOWER(?)";
        break;
      default:
        trigger_error("Unsupported column name '$column_name'.", E_USER_NOTICE);
        return;
        break;
    }

    $sql = "UPDATE $tbl_users
               SET password_hash=?
             WHERE $condition";

    \MRBS\db()->command($sql, $sql_params);
  }


  // Checks $password against $password_hash for the row in the user table
  // where $column_name=$column_value.  Typically $column_name will be either
  // 'name' or 'email'.
  // Returns a boolean: true if they match, otherwise false.
  private function checkPassword($password, $password_hash, $column_name, $column_value)
  {
    $result = false;
    $do_rehash = false;

    /* If the hash starts '$' it's a PHP password hash */
    if (substr($password_hash, 0, 1) == '$')
    {
      if (password_verify($password, $password_hash))
      {
        $result = true;
        if (password_needs_rehash($password_hash, PASSWORD_DEFAULT))
        {
          $do_rehash = true;
        }
      }
    }
    /* Otherwise it's a legacy MD5 hash */
    else
    {
      if (md5($password) == $password_hash)
      {
        $result = true;
        $do_rehash = true;
      }
    }

    if ($do_rehash)
    {
      $this->rehash($password, $column_name, $column_value);
    }

    return $result;
  }

}
