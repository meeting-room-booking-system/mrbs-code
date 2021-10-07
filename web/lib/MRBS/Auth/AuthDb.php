<?php
namespace MRBS\Auth;

use MRBS\MailQueue;
use MRBS\User;
use function MRBS\_tbl;
use function MRBS\auth;
use function MRBS\db;
use function MRBS\generate_global_uid;
use function MRBS\generate_token;
use function MRBS\get_mail_charset;
use function MRBS\get_vocab;
use function MRBS\is_https;
use function MRBS\multisite;
use function MRBS\parse_email;
use function MRBS\to_time_string;
use function MRBS\url_base;
use function MRBS\utf8_strpos;
use function MRBS\utf8_strtolower;


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
  public function validateUser(?string $user, ?string $pass)
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

    return (count($valid_usernames) == 1) ? $valid_usernames[0] : false;
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
  private function validateUsername(string $user, string $pass)
  {
    $sql_params = array();

    // We use syntax_casesensitive_equals() rather than just '=' because '=' in MySQL
    // permits trailing spacings, eg 'john' = 'john '.   We could use LIKE, but that then
    // permits wildcards, so we could use a combination of LIKE and '=' but that's a bit
    // messy.  WE could use STRCMP, but that's MySQL only.

    // Usernames are unique in the user table, so we only look for one.
    $sql = "SELECT password_hash, name
            FROM " . _tbl(User::TABLE_NAME) . "
           WHERE " . db()->syntax_casesensitive_equals('name', utf8_strtolower($user), $sql_params) . "
             AND auth_type='db'
           LIMIT 1";

    $res = db()->query($sql, $sql_params);

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
  private function validateEmail(string $email, string $pass) : array
  {
    $valid_usernames = array();

    // Email addresses are not unique in the user table, so we need to find all of them.
    $users = self::getUsersByEmail($email);

    // Check all the users that have this email address and password hash.
    foreach($users as $user)
    {
      if (isset($user->password_hash) &&
          $this->checkPassword($pass, $user->password_hash, 'email', $email))
      {
        $valid_usernames[] = $user->name;
      }
    }

    return $valid_usernames;
  }


  public function getUser(string $username) : ?User
  {
    return User::getByName($username, 'db');
  }


  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames() : array
  {
    $sql = "SELECT name AS username, display_name AS display_name
              FROM " . _tbl(User::TABLE_NAME) . "
             WHERE auth_type='db'
          ORDER BY display_name";

    $res = db()->query($sql);
    
    return $res->all_rows_keyed();
  }


  // Checks whether validation of a user by email address is possible and allowed.
  public function canValidateByEmail() : bool
  {
    return true;
  }


  // Checks whether the method has a password reset facility
  public function canResetPassword() : bool
  {
    return true;
  }


  // Checks whether the password by reset by supplying an email address.
  // We allow resetting by email, even if there are multiple users with the
  // same email address.
  public function canResetByEmail() : bool
  {
    return $this->canValidateByEmail();
  }


  public function requestPassword(?string $login) : bool
  {
    global $auth;

    if (!isset($login) || ($login === ''))
    {
      return false;
    }

    // Get the possible users given this login, which could be a username or email address.
    // However all the possible users must have the same email address, so check the email
    // addresses at the same time.
    $possible_users = array();

    $user = User::getByName($login, $auth['type']);

    // Users must have an email address otherwise we won't be able to mail a reset link
    if (isset($user) && isset($user->email) && ($user->email !== ''))
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
            (utf8_strtolower($possible_users[0]->email) !== utf8_strtolower($login)))
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
      $key = generate_token(32);

      // Update the database
      if ($this->setResetKey($possible_users, $key))
      {
        // Email the user
        return $this->notifyUser($possible_users, $key);
      }
    }

    return false;
  }


  public function resetPassword(?string $username, ?string $key, ?string $password) : bool
  {
    // Check that we've got a password and we're allowed to reset the password
    if (!isset($password) || !auth()->isValidReset($username, $key))
    {
      return false;
    }

    // Set the new password and clear the reset key
    $sql = "UPDATE " . _tbl(User::TABLE_NAME) . "
               SET password_hash=:password_hash,
                   reset_key_hash=NULL,
                   reset_key_expiry=0
             WHERE name=:name
               AND auth_type='db'";  // PostgreSQL does not support LIMIT with UPDATE

    $sql_params = array(
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':name' => $username
      );

    db()->command($sql, $sql_params);

    return true;
  }


  public function isValidReset(?string $user, ?string $key) : bool
  {
    if (!isset($user) || !isset($key) || ($user === '') || ($key === ''))
    {
      return false;
    }

    $sql = "SELECT reset_key_hash, reset_key_expiry
              FROM " . _tbl(User::TABLE_NAME) . "
             WHERE name=:name
               AND auth_type='db'
             LIMIT 1";

    $sql_params = array(':name' => $user);
    $res = db()->query($sql,$sql_params);

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


  protected function getRegistrantsDisplayNamesUnsorted(int $id) : array
  {
    // For the 'db' auth type we can improve performance by doing a single query
    // on the participants table joined with the users table.  (Actually it's two
    // queries in a UNION: one getting the rows where there isn't an entry in the
    // users table and another the rows where there is.)
    $sql = "SELECT P.username as display_name
              FROM " . _tbl('participant') . " P
         LEFT JOIN " . _tbl(User::TABLE_NAME) . " U
                ON P.username=U.name
             WHERE P.entry_id=:entry_id
               AND U.name IS NULL
             UNION
            SELECT U.display_name
              FROM " . _tbl('participant') . " P
         LEFT JOIN " . _tbl(User::TABLE_NAME) . " U
                ON P.username=U.name
             WHERE P.entry_id=:entry_id
               AND U.name IS NOT NULL";

    return db()->query_array($sql, array(':entry_id' => $id));
  }

  private function notifyUser(array $users, string $key) : bool
  {
    global $auth, $mail_settings;

    if (empty($users) || !isset($users[0]->email) || ($users[0]->email === ''))
    {
      return false;
    }

    $expiry = to_time_string($auth['db']['reset_key_expiry'], true, 'hours');
    $addresses = array(
        'from'  => $mail_settings['from'],
        'to'    => $users[0]->email
      );
    // Add the To address.  Also get a name to use in the message body.
    // If there's only one user we can use the username and display name, otherwise
    // we have to use the email address which is the same for all users.
    if ((count($users) == 1))
    {
      $addresses['to'] = $users[0]->mailbox();
      if (isset($users[0]->display_name) && ($users[0]->display_name !== ''))
      {
        $name = $users[0]->display_name;
      }
      else
      {
        $name = $users[0]->username;
      }
    }
    else
    {
      $addresses['to'] = $users[0]->email;
      $name = $users[0]->email;
    }

    $subject = get_vocab('password_reset_subject');
    $body = '<p>';
    $body .= get_vocab('password_reset_body', intval($expiry['value']), $expiry['units'], $name);
    $body .= "</p>\n";

    // Construct and add in the link
    $usernames = array();
    foreach ($users as $user)
    {
      $usernames[] = $user->username;
    }
    $usernames = array_unique($usernames);

    $vars = array(
        'action'    => 'reset',
        'usernames' => $usernames,
        'key'       => $key
      );
    $query = http_build_query($vars, '', '&');
    $href = url_base() . multisite("reset_password.php?$query");
    $body .= "<p><a href=\"$href\">" . get_vocab('reset_password') . "</a>.</p>";

    MailQueue::add(
        $addresses,
        $subject,
        array('content' => strip_tags($body)),
        array('content' => $body,
          'cid'     => generate_global_uid("html")),
        null,
        get_mail_charset()
      );

    return true;
  }

  private function setResetKey(array $users, string $key) : bool
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
      $ids[] = intval($user->id);
    }

    $sql = "UPDATE " . _tbl(User::TABLE_NAME) . "
               SET reset_key_hash=:reset_key_hash,
                   reset_key_expiry=:reset_key_expiry
             WHERE id IN (" . implode(',', $ids) . ")";

    $sql_params = array(
        ':reset_key_hash' => password_hash($key, PASSWORD_DEFAULT),
        ':reset_key_expiry' => time() + $auth['db']['reset_key_expiry']
      );

    db()->command($sql, $sql_params);

    return true;
  }


  private function getUserByUserId(int $id) : ?array
  {
    $sql = "SELECT *
              FROM " . _tbl(User::TABLE_NAME) . "
             WHERE id=:id
             LIMIT 1";

    $result = db()->query($sql, array(':id' => $id));

    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }

    return $result->next_row_keyed();
  }


  // Returns an array of User objects for users with the email address $email.
  // Assumes that email addresses are case insensitive.
  // Allows equivalent Gmail addresses, ie ignores dots in the local part and
  // treats gmail.com and googlemail.com as equivalent domains.
  private function getUsersByEmail(string $email) : array
  {
    global $auth;

    $result = array();

    // For the moment we will assume that email addresses are case insensitive.   Whilst it is true
    // on most systems, it isn't always true.  The domain is case insensitive but the local-part can
    // be case sensitive.  But before we can take account of this, the email addresses in the database
    // need to be normalised so that all the domain names are stored in lower case.  Then it will be
    // possible to do a case sensitive comparison.
    if (utf8_strpos($email, '@') === false)
    {
      if (!empty($auth['allow_local_part_email']))
      {
        // We're just checking the local-part of the email address
        $sql_params = array($email);
        $condition = "LOWER(?)=LOWER(" . db()->syntax_simple_split('email', '@', 1, $sql_params) .")";
      }
      else
      {
        return $result;
      }
    }
    else
    {
      $address = parse_email($email);
      // Invalid email address
      if ($address === false)
      {
        return $result;
      }
      // Special case for Gmail addresses: ignore dots in the local part and treat gmail.com and
      // googlemail.com as equivalent domains.
      elseif (in_array(utf8_strtolower($address['domain']), array('gmail.com', 'googlemail.com')))
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
              FROM " . _tbl(User::TABLE_NAME) . "
             WHERE ($condition)
               AND auth_type='db'";

    $res = db()->query($sql, $sql_params);

    while (false !== ($row = $res->next_row_keyed()))
    {
      $user = new User();
      $user->load($row);
      $user->username = $user->name;
      $result[] = $user;
    }

    return $result;
  }


  private function rehash(string $password, string $column_name, string $column_value) : void
  {
    $sql_params = array(password_hash($password, PASSWORD_DEFAULT));

    switch ($column_name)
    {
      case 'name':
        $condition = db()->syntax_casesensitive_equals($column_name, utf8_strtolower($column_value), $sql_params);
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

    $sql = "UPDATE " . _tbl(User::TABLE_NAME) . "
               SET password_hash=?
             WHERE $condition
               AND auth_type='db'";

    db()->command($sql, $sql_params);
  }


  // Checks $password against $password_hash for the row in the user table
  // where $column_name=$column_value.  Typically $column_name will be either
  // 'name' or 'email'.
  // Returns a boolean: true if they match, otherwise false.
  private function checkPassword(string $password, string $password_hash, string $column_name, string $column_value) : bool
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
