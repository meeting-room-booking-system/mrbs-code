<?php
namespace MRBS\Auth;

use MRBS\MailQueue;
use MRBS\User;
use PHPMailer\PHPMailer\PHPMailer;

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
    $valid_usernames = array();

    // Email addresses are not unique in the users table, so we need to find all of them.
    $users = self::getUsersByEmail($email);

    // Check all the users that have this email address and password hash.
    foreach($users as $user)
    {
      if ($this->checkPassword($pass, $user['password_hash'], 'email', $email))
      {
        $valid_usernames[] = $user['name'];
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
  // We allow resetting by email, even if there are multiple users with the
  // same email address.
  public function canResetByEmail()
  {
    return $this->canValidateByEmail();
  }


  public function requestPassword($login)
  {
    if (!isset($login) || ($login === ''))
    {
      return false;
    }

    // Get the possible users given this login, which could be a username or email address.
    // However all the possible users must have the same email address, so check the email
    // addresses at the same time.
    $possible_users = array();

    $user = $this->getUserByUsername($login);

    // Users must have an email address otherwise we won't be able to mail a reset link
    if (isset($user) && isset($user['email']) && ($user['email'] !== ''))
    {
      $possible_users[] = $user;
    }

    if ($this->canValidateByEmail())
    {
      $users = $this->getUsersByEmail($login);
      if (!empty($users))
      {
        // Check that the email addresses are the same
        if (!empty($possible_users) &&
            (\MRBS\utf8_strtolower($possible_users[0]['email']) !== \MRBS\utf8_strtolower($login)))
        {
          return false;
        }
        foreach ($users as $user)
        {
          $possible_users[] = $user;
        }
      }
    }

    if (!empty($possible_users))
    {
      // Generate a key
      $key = \MRBS\generate_token(32);

      // Update the database
      if ($this->setResetKey($possible_users, $key))
      {
        // Email the user
        return $this->notifyUser($possible_users, $key);
      }
    }

    return false;
  }


  public function resetPassword($username, $key, $password)
  {
    // Check that we've got a password and we're allowed to reset the password
    if (!isset($password) || !\MRBS\auth()->isValidReset($username, $key))
    {
      return false;
    }

    // Set the new password and clear the reset key
    $sql = "UPDATE " . \MRBS\_tbl('users') . "
               SET password_hash=:password_hash,
                   reset_key_hash=NULL,
                   reset_key_expiry=0
             WHERE name=:name";  // PostgreSQL does not support LIMIT with UPDATE

    $sql_params = array(
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':name' => $username
      );

    \MRBS\db()->command($sql, $sql_params);

    return true;
  }


  public function isValidReset($user, $key)
  {
    if (!isset($user) || !isset($key) || ($user === '') || ($key === ''))
    {
      return false;
    }

    $sql = "SELECT reset_key_hash, reset_key_expiry
              FROM " . \MRBS\_tbl('users') . "
             WHERE name=:name
             LIMIT 1";

    $sql_params = array(':name' => $user);
    $res = \MRBS\db()->query($sql,$sql_params);

    // Check we've found a row
    if ($res->count() == 0)
    {
      return false;
    }

    $row = $res->next_row_keyed();

    // Check that the reset hasn't expired
    if (time() > $row['reset_key_expiry'])
    {
      return false;
    }

    // Check we've got the correct key
    return password_verify($key, $row['reset_key_hash']);
  }


  private function notifyUser(array $users, $key)
  {
    global $auth, $mail_settings;

    if (empty($users) || !isset($users[0]['email']) || ($users[0]['email'] === ''))
    {
      return false;
    }

    $expiry_time = $auth['db']['reset_key_expiry'];
    \MRBS\toTimeString($expiry_time, $expiry_units, true, 'hours');
    $addresses = array(
        'from'  => $mail_settings['from'],
        'to'    => $users[0]['email']
      );
    // Add the display name, if there is one, to the To address
    if (isset($users[0]['display_name']) && ($users[0]['display_name'] !== ''))
    {
      $mailer = new PHPMailer();
      $addresses['to'] = $mailer->addrFormat(array($addresses['to'], $users[0]['display_name']));
    }
    $subject = \MRBS\get_vocab('password_reset_subject');
    $body = '<p>';
    $body .= \MRBS\get_vocab('password_reset_body', intval($expiry_time), $expiry_units);
    $body .= "</p>\n";

    // Construct and add in the link
    $usernames = array();
    foreach ($users as $user)
    {
      $usernames[] = $user['name'];
    }
    $usernames = array_unique($usernames);

    $vars = array(
        'action'    => 'reset',
        'usernames' => $usernames,
        'key'       => $key
      );
    $query = http_build_query($vars, '', '&');
    $href = (\MRBS\is_https()) ? 'https' : 'http';
    $href .= '://' . \MRBS\url_base() . \MRBS\multisite("reset_password.php?$query");
    $body .= "<p><a href=\"$href\">" . \MRBS\get_vocab('reset_password') . "</a>.</p>";

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

  private function setResetKey(array $users, $key)
  {
    global $auth;

    if (empty($users))
    {
      return false;
    }

    $ids = array();
    foreach($users as $user)
    {
      // Use intval to make sure the string is safe for the SQL query
      $ids[] = intval($user['id']);
    }

    $sql = "UPDATE " . \MRBS\_tbl('users') . "
               SET reset_key_hash=:reset_key_hash,
                   reset_key_expiry=:reset_key_expiry
             WHERE id IN (" . implode(',', $ids) . ")";

    $sql_params = array(
        ':reset_key_hash' => password_hash($key, PASSWORD_DEFAULT),
        ':reset_key_expiry' => time() + $auth['db']['reset_key_expiry']
      );

    \MRBS\db()->command($sql, $sql_params);

    return true;
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


  // Returns an array of rows for all users with the email address $email.
  // Assumes that email addresses are case insensitive.
  // Allows equivalent Gmail addresses, ie ignores dots in the local part and
  // treats gmail.com and googlemail.com as equivalent domains.
  private function getUsersByEmail($email)
  {
    global $auth;

    $result = array();

    // For the moment we will assume that email addresses are case insensitive.   Whilst it is true
    // on most systems, it isn't always true.  The domain is case insensitive but the local-part can
    // be case sensitive.  But before we can take account of this, the email addresses in the database
    // need to be normalised so that all the domain names are stored in lower case.  Then it will be
    // possible to do a case sensitive comparison.
    if (\MRBS\utf8_strpos($email, '@') === false)
    {
      if (!empty($auth['allow_local_part_email']))
      {
        // We're just checking the local-part of the email address
        $sql_params = array($email);
        $condition = "LOWER(?)=LOWER(" . \MRBS\db()->syntax_simple_split('email', '@', 1, $sql_params) .")";
      }
      else
      {
        return $result;
      }
    }
    else
    {
      $address = \MRBS\parse_email($email);
      // Invalid email address
      if ($address === false)
      {
        return $result;
      }
      // Special case for Gmail addresses: ignore dots in the local part and treat gmail.com and
      // googlemail.com as equivalent domains.
      elseif (in_array(\MRBS\utf8_strtolower($address['domain']), array('gmail.com', 'googlemail.com')))
      {
        $sql_params = array(str_replace('.', '', $address['local']));
        $sql_params[] = $sql_params[0];
        $condition = "(LOWER(?) = REPLACE(TRIM(TRAILING '@gmail.com' FROM LOWER(email)), '.', '')) OR " .
                     "(LOWER(?) = REPLACE(TRIM(TRAILING '@googlemail.com' FROM LOWER(email)), '.', ''))";
      }
      // Everything else: check the complete email address
      else
      {
        $sql_params = array($email);
        $condition = "LOWER(?)=LOWER(email)";
      }
    }

    $sql = "SELECT *
              FROM " . \MRBS\_tbl('users') . "
             WHERE $condition";

    $res = \MRBS\db()->query($sql, $sql_params);

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
