<?php
namespace MRBS\Auth;

use MRBS\MailQueue;
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
      $sql = "UPDATE " . \MRBS\_tbl('users') . "
                 SET last_login=?, timestamp=timestamp
               WHERE name=?";
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
    $sql_params = array();

    // We use syntax_casesensitive_equals() rather than just '=' because '=' in MySQL
    // permits trailing spacings, eg 'john' = 'john '.   We could use LIKE, but that then
    // permits wildcards, so we could use a combination of LIKE and '=' but that's a bit
    // messy.  WE could use STRCMP, but that's MySQL only.

    // Usernames are unique in the users table, so we only look for one.
    $sql = "SELECT password_hash, name
            FROM " . \MRBS\_tbl('users') . "
           WHERE " . \MRBS\db()->syntax_casesensitive_equals('name', \MRBS\utf8_strtolower($user), $sql_params) . "
           LIMIT 1";

    $res = \MRBS\db()->query($sql, $sql_params);

    $row = $res->next_row_keyed();

    if (!isset($row['password_hash']))
    {
      // No user found with that name
      return false;
    }

    return ($this->checkPassword($pass, $row['password_hash'], 'name', $row['name'])) ? $row['name'] : false;
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
            FROM " . \MRBS\_tbl('users') . "
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
    $row = $this->getUserByUsername($username);

    // The username doesn't exist - return NULL
    if (!isset($row))
    {
      return null;
    }

    // The username does exist - return a User object
    $user = new User($username);

    // $user->level and $user->display_name will be set as part of this
    foreach ($row as $key => $value)
    {
      if ($key == 'name')
      {
        // This has already been set as the 'username' property;
        continue;
      }
      $user->$key = $value;
    }

    return $user;
  }


  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames()
  {
    $sql = "SELECT name AS username, display_name AS display_name
              FROM " . \MRBS\_tbl('users') . "
          ORDER BY display_name";

    $res = \MRBS\db()->query($sql);

    return $res->all_rows_keyed();
  }


  // Return an array of all users
  public function getUsers()
  {
    $sql = "SELECT *
              FROM " . \MRBS\_tbl('users') . "
             ORDER BY name";

    $res = \MRBS\db()->query($sql);

    return $res->all_rows_keyed();
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail()
  {
    return true;
  }


  // Checks whether the method has a password reset facility
  public function canResetPassword()
  {
    return true;
  }


  // Checks whether the password by reset by supplying an email address.
  // If there are duplicate email addresses in the table (only the username is required
  // to be unique) then we can't, because we won't know which user has requested the reset.
  public function canResetByEmail()
  {
    return ($this->canValidateByEmail() && !\MRBS\db()->tableHasDuplicates(\MRBS\_tbl('users'),'email'));
  }


  public function resetPassword($login)
  {
    global $auth, $mail_settings;

    if (!isset($login) || ($login === ''))
    {
      return false;
    }

    // Get the possible user ids given this login, which could be a username or email address
    $possible_user_ids = array();

    $user = $this->getUserByUsername($login);

    if (!empty($user))
    {
      $possible_user_ids[] = $user['id'];
    }

    if ($this->canValidateByEmail())
    {
      $users = $this->getUsersByEmail($login);
      if (!empty($users))
      {
        foreach ($users as $user)
        {
          $possible_user_ids[] = $user['id'];
        }
      }
    }

    // If we haven't got exactly one user with this login, then don't do anything.
    if (count($possible_user_ids) !== 1)
    {
      return false;
    }

    // Generate a key
    $key = \MRBS\generate_token(32);

    // Update the database
    $user_id = $possible_user_ids[0];
    $this->setResetKey($user_id, $key);

    // Email the user
    $user = $this->getUserByUserId($user_id);
    if (!isset($user['email']) || ($user['email'] === ''))
    {
      return false;
    }
    $expiry_time = $auth['db']['reset_key_expiry'];
    \MRBS\toTimeString($expiry_time, $expiry_units, true, 'hours');
    $addresses = array(
        'from'  => $mail_settings['from'],
        'to'    => $user['email']
      );
    $subject = \MRBS\get_vocab('password_reset_subject');
    $body = '<p>';
    $body .= \MRBS\get_vocab('password_reset_body', $user['name'], intval($expiry_time), $expiry_units);
    $body .= "</p>\n";
    MailQueue::add(
        $addresses,
        $subject,
        array('content' => strip_tags($body)),
        array('content' => $body,
              'cid'     => \MRBS\generate_global_uid("html")),
        null,
        \MRBS\get_mail_charset()
      );
    return true;
  }


  private function setResetKey($user_id, $key)
  {
    global $auth;

    $sql = "UPDATE " . \MRBS\_tbl('users') . "
               SET reset_key_hash=:reset_key_hash,
                   reset_key_expiry=:reset_key_expiry
             WHERE id=:id
             LIMIT 1";

    $sql_params = array(
        ':reset_key_hash' => password_hash($key, PASSWORD_DEFAULT),
        ':reset_key_expiry' => time() + $auth['db']['reset_key_expiry'],
        ':id' => $user_id
      );

    \MRBS\db()->command($sql, $sql_params);
  }


  private function getUserByUsername($username)
  {
    $sql = "SELECT *
              FROM " . \MRBS\_tbl('users') . "
             WHERE name=:name
             LIMIT 1";

    $result = \MRBS\db()->query($sql, array(':name' => $username));

    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }

    return $result->next_row_keyed();
  }


  private function getUserByUserId($id)
  {
    $sql = "SELECT *
              FROM " . \MRBS\_tbl('users') . "
             WHERE id=:id
             LIMIT 1";

    $result = \MRBS\db()->query($sql, array(':id' => $id));

    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }

    return $result->next_row_keyed();
  }


  private function getUsersByEmail($email)
  {
    $result = array();

    $sql = "SELECT *
              FROM " . \MRBS\_tbl('users') . "
             WHERE email=:email";

    $res = \MRBS\db()->query($sql, array(':email' => $email));

    // The username doesn't exist - return NULL
    while (false !== ($row = $res->next_row_keyed()))
    {
      $result[] = $row;
    }

    return $result;
  }


  private function rehash($password, $column_name, $column_value)
  {
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

    $sql = "UPDATE " . \MRBS\_tbl('users') . "
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
