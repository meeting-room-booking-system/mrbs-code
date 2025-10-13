<?php
declare(strict_types=1);
namespace MRBS\Auth;

use MRBS\MailQueue;
use MRBS\User;
use PHPMailer\PHPMailer\PHPMailer;
use function MRBS\_tbl;
use function MRBS\auth;
use function MRBS\db;
use function MRBS\format_compound_name;
use function MRBS\generate_token;
use function MRBS\get_mail_charset;
use function MRBS\get_vocab;
use function MRBS\multisite;
use function MRBS\parse_email;
use function MRBS\row_cast_columns;
use function MRBS\toTimeString;
use function MRBS\url_base;

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
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
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
      $sql = "UPDATE " . _tbl('users') . "
                 SET last_login=?, timestamp=timestamp
               WHERE name=?";
      $sql_params = array($now, $result);
      db()->command($sql, $sql_params);
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
  private function validateUsername(string $user, string $pass)
  {
    $sql_params = array();

    // We use syntax_casesensitive_equals() rather than just '=' because '=' in MySQL
    // permits trailing spacings, eg 'john' = 'john '.   We could use LIKE, but that then
    // permits wildcards, so we could use a combination of LIKE and '=' but that's a bit
    // messy.  We could use STRCMP, but that's MySQL only.

    // Usernames are unique in the users table, so we only look for one.
    $sql = "SELECT password_hash, name
            FROM " . _tbl('users') . "
           WHERE " . db()->syntax_casesensitive_equals('name', mb_strtolower($user), $sql_params) . "
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

    // Email addresses are not unique in the users table, so we need to find all of them.
    $users = self::getUsersByEmail($email);

    // Check all the users that have this email address and password hash.
    foreach($users as $user)
    {
      if (isset($user['password_hash']) &&
          $this->checkPassword($pass, $user['password_hash'], 'email', $email))
      {
        $valid_usernames[] = $user['name'];
      }
    }

    return $valid_usernames;
  }


  protected function getUserFresh(string $username) : ?User
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
  public function getUsernames() : array
  {
    $sql = "SELECT name AS username,
                   CASE
                       WHEN display_name IS NOT NULL AND display_name!='' THEN display_name
                       ELSE name
                   END
                   AS display_name
              FROM " . _tbl('users') . "
          ORDER BY display_name";

    $res = db()->query($sql);

    $users =  $res->all_rows_keyed();

    // Although the users are probably already sorted, we sort them again because MRBS
    // offers an option for sorting by first or last name.
    self::sortUsers($users);

    return $users;
  }


  // Return an array of all users
  public function getUsers() : array
  {
    // Add in an extra column, last_updated, which is the SQL timestamp converted to a UNIX
    // timestamp.  We do the conversion in the SQL query so that it is converted using the
    // same timezone that it was stored with.
    $sql = "SELECT *, ". db()->syntax_timestamp_to_unix("timestamp") . " AS last_updated
              FROM " . _tbl('users') . "
             ORDER BY name";

    $res = db()->query($sql);

    $result = $res->all_rows_keyed();

    foreach ($result as &$row)
    {
      row_cast_columns($row, 'users');
      // Turn the last_updated column into an int (some MySQL drivers will return a string,
      // and it won't have been caught by row_cast_columns() as it's a derived result).
      $row['last_updated'] = intval($row['last_updated']);
    }

    return $result;
  }


  // Checks whether the authentication type allows the creation of new users.
  public function canCreateUsers() : bool
  {
    return true;
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
            (mb_strtolower($possible_users[0]['email']) !== mb_strtolower($login)))
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


  public function resetPassword(
    #[\SensitiveParameter]
    ?string $username,
    ?string $key,
    #[\SensitiveParameter]
    ?string $password) : bool
  {
    // Check that we've got a password and we're allowed to reset the password
    if (!isset($password) || !auth()->isValidReset($username, $key))
    {
      return false;
    }

    // Set the new password and clear the reset key
    $sql = "UPDATE " . _tbl('users') . "
               SET password_hash=:password_hash,
                   reset_key_hash=NULL,
                   reset_key_expiry=0
             WHERE name=:name";  // PostgreSQL does not support LIMIT with UPDATE

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
              FROM " . _tbl('users') . "
             WHERE name=:name
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


  // Returns an unsorted array of registrants display names
  protected function getRegistrantsDisplayNamesUnsortedWithout(int $id, bool $with_registrant_username) : array
  {
    // For the 'db' auth type we can improve performance by doing a single query
    // on the participants table joined with the users table.  (Actually it's two
    // queries in a UNION: one getting the rows where there isn't an entry in the
    // users table and another the rows where there is.)
    $sql = "SELECT P.username as username,
                   P.username as display_name
              FROM " . _tbl('participants') . " P
         LEFT JOIN " . _tbl('users') . " U
                ON P.username=U.name
             WHERE P.entry_id=:entry_id
               AND (U.display_name IS NULL OR U.display_name='')
             UNION
            SELECT U.name as username,
                   U.display_name as display_name
              FROM " . _tbl('participants') . " P
         LEFT JOIN " . _tbl('users') . " U
                ON P.username=U.name
             WHERE P.entry_id=:entry_id
               AND U.display_name IS NOT NULL AND U.display_name!=''";

    $result = array();
    $res = db()->query($sql, array(':entry_id' => $id));

    while (false !== ($row = $res->next_row_keyed()))
    {
      $result[] = ($with_registrant_username) ? format_compound_name($row['username'], $row['display_name']) : $row['display_name'];
    }

    return $result;
  }


  // Returns an unsorted array of registrants display names, including, if
  // different, the display name of the person that registered them.
  protected function getRegistrantsDisplayNamesUnsortedWith(int $id, bool $with_registrant_username) : array
  {
    // For the 'db' auth type we can improve performance by doing a single query
    // on the participants table joined with the users table.  (Actually it's four
    // queries in a UNION: one getting the rows where there isn't an entry in the
    // users table and another the rows where there is, etc. for both the registrant
    // and the person that registered them.)
    $sql = "SELECT P.username as registrant_username,
                   P.username as registrant_display_name,
                   P.create_by as create_by_username,
                   P.create_by as create_by_display_name
              FROM " . _tbl('participants') . " P
         LEFT JOIN " . _tbl('users') . " U1
                ON P.username=U1.name
         LEFT JOIN " . _tbl('users') . " U2
                ON P.create_by=U2.name
             WHERE P.entry_id=:entry_id
               AND (U1.display_name IS NULL OR U1.display_name='')
               AND (U2.display_name IS NULL OR U2.display_name='')

             UNION

            SELECT P.username as registrant_username,
                   P.username as registrant_display_name,
                   P.create_by as create_by_username,
                   U2.display_name as registrant_display_name
              FROM " . _tbl('participants') . " P
         LEFT JOIN " . _tbl('users') . " U1
                ON P.username=U1.name
         LEFT JOIN " . _tbl('users') . " U2
                ON P.create_by=U2.name
             WHERE P.entry_id=:entry_id
               AND (U1.display_name IS NULL OR U1.display_name='')
               AND U2.display_name IS NOT NULL AND U2.display_name!=''

             UNION

            SELECT P.username as registrant_username,
                   U1.display_name as registrant_display_name,
                   P.create_by as create_by_username,
                   P.create_by as registrant_display_name
              FROM " . _tbl('participants') . " P
         LEFT JOIN " . _tbl('users') . " U1
                ON P.username=U1.name
         LEFT JOIN " . _tbl('users') . " U2
                ON P.create_by=U2.name
             WHERE P.entry_id=:entry_id
               AND U1.display_name IS NOT NULL AND U1.display_name!=''
               AND (U2.display_name IS NULL OR U2.display_name='')

             UNION

            SELECT P.username as registrant_username,
                   U1.display_name as registrant_display_name,
                   P.create_by as create_by_username,
                   U2.display_name as registrant_display_name
              FROM " . _tbl('participants') . " P
         LEFT JOIN " . _tbl('users') . " U1
                ON P.username=U1.name
         LEFT JOIN " . _tbl('users') . " U2
                ON P.create_by=U2.name
             WHERE P.entry_id=:entry_id
               AND U1.display_name IS NOT NULL AND U1.display_name!=''
               AND U2.display_name IS NOT NULL AND U2.display_name!=''";

    $result = array();

    $res =  db()->query($sql, array(':entry_id' => $id));

    while (false !== ($row = $res->next_row_keyed()))
    {
      if ($row['registrant_username'] === $row['create_by_username'])
      {
        if ($with_registrant_username)
        {
          $result[] = format_compound_name($row['registrant_username'], $row['registrant_display_name']);
        }
        else
        {
          $result[] = $row['registrant_display_name'];
        }
      }
      else
      {
        if ($with_registrant_username && ($row['registrant_username'] !== $row['registrant_display_name']))
        {
          $result[] = get_vocab('registrant_username_and_registered_by',
                                $row['registrant_username'],
                                $row['registrant_display_name'],
                                $row['create_by_display_name']);
        }
        else
        {
          $result[] = get_vocab('registrant_registered_by',
                                $row['registrant_display_name'],
                                $row['create_by_display_name']);
        }
      }
    }

    return $result;
  }


  protected function getRegistrantsDisplayNamesUnsorted(int $id, bool $with_registered_by, $with_registrant_username) : array
  {
    if ($with_registered_by)
    {
      return $this->getRegistrantsDisplayNamesUnsortedWith($id, $with_registrant_username);
    }
    else
    {
      return $this->getRegistrantsDisplayNamesUnsortedWithout($id, $with_registrant_username);
    }
  }


  private function notifyUser(array $users, string $key) : bool
  {
    global $auth, $mail_settings;

    if (empty($users) || !isset($users[0]['email']) || ($users[0]['email'] === ''))
    {
      return false;
    }

    $expiry_time = $auth['db']['reset_key_expiry'];
    toTimeString($expiry_time, $expiry_units, true, 'hours');
    $addresses = array(
        'from'  => $mail_settings['from']
      );
    // Add the To address, using the display name if possible (ie if it exists and there's
    // only one user).
    // Also get a name to use in the message body
    if ((count($users) == 1) &&
        isset($users[0]['display_name']) &&
        ($users[0]['display_name'] !== ''))
    {
      $mailer = new PHPMailer();
      $mailer->CharSet = get_mail_charset();
      // Note that addrFormat() returns a MIME-encoded address
      $addresses['to'] = $mailer->addrFormat(array($users[0]['email'], $users[0]['display_name']));
      $name = $users[0]['display_name'];
    }
    else
    {
      $addresses['to'] = $users[0]['email'];
      // If there's only one user we can use the username, otherwise we have to use the
      // email address which is the same for all users.
      $name = (count($users) == 1) ? $users[0]['name'] : $users[0]['email'];
    }
    $subject = get_vocab('password_reset_subject');
    $body = '<p>';
    $body .= get_vocab('password_reset_body', intval($expiry_time), $expiry_units, $name);
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
    $href = url_base() . multisite("reset_password.php?$query");
    $body .= "<p><a href=\"$href\">" . get_vocab('reset_password') . "</a>.</p>";

    MailQueue::add(
        $addresses,
        $subject,
        strip_tags($body),
        $body,
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
      $ids[] = intval($user['id']);
    }

    $sql = "UPDATE " . _tbl('users') . "
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


  private function getUserByUsername(string $username) : ?array
  {
    $sql = "SELECT *
              FROM " . _tbl('users') . "
             WHERE name=:name
             LIMIT 1";

    $result = db()->query($sql, array(':name' => $username));

    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }

    return $result->next_row_keyed();
  }


  public function getUserByUserId(int $id) : ?User
  {
    $sql = "SELECT *
              FROM " . _tbl('users') . "
             WHERE id=:id
             LIMIT 1";

    $result = db()->query($sql, array(':id' => $id));

    // The username doesn't exist - return NULL
    if ($result->count() === 0)
    {
      return null;
    }

    // The username does exist - return a User object
    $user = new User();
    $row = $result->next_row_keyed();

    // $user->level and $user->display_name will be set as part of this
    foreach ($row as $key => $value)
    {
      if ($key == 'name')
      {
        $user->username = $value;
      }
      $user->$key = $value;
    }

    return $user;
  }


  // Returns a username given an email address.  Note that if two or more
  // users share the same email address then the first one found will be
  // returned.  If no user is found then NULL is returned.
  public function getUsernameByEmail(string $email) : ?string
  {
    $sql = "SELECT name
              FROM " . _tbl('users') . "
             WHERE email=?";

    $res = db()->query($sql, array($email));

    if ($res->count() == 0)
    {
      return null;
    }

    if ($res->count() > 1)
    {
      // Could maybe do something better here
      trigger_error("Email address not unique", E_USER_NOTICE);
    }
    $row = $res->next_row_keyed();
    return $row['name'];
  }


  // Returns an array of rows for all users with the email address $email.
  // Assumes that email addresses are case insensitive.
  // Allows equivalent Gmail addresses, ie ignores dots in the local part and
  // treats gmail.com and googlemail.com as equivalent domains.
  private function getUsersByEmail(string $email) : array
  {
    global $auth;

    $result = array();

    // For the moment we will assume that email addresses are case-insensitive.   Whilst it is true
    // on most systems, it isn't always true.  The domain is case-insensitive but the local-part can
    // be case-sensitive.  But before we can take account of this, the email addresses in the database
    // need to be normalised so that all the domain names are stored in lower case.  Then it will be
    // possible to do a case-sensitive comparison.
    if (mb_strpos($email, '@') === false)
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
      elseif (in_array(mb_strtolower($address['domain']), array('gmail.com', 'googlemail.com')))
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
              FROM " . _tbl('users') . "
             WHERE $condition";

    $res = db()->query($sql, $sql_params);

    while (false !== ($row = $res->next_row_keyed()))
    {
      $result[] = $row;
    }

    return $result;
  }


  private function rehash(
    #[\SensitiveParameter]
    string $password,
    string $column_name,
    string $column_value) : void
  {
    $sql_params = array(password_hash($password, PASSWORD_DEFAULT));

    switch ($column_name)
    {
      case 'name':
        $condition = db()->syntax_casesensitive_equals($column_name, mb_strtolower($column_value), $sql_params);
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

    $sql = "UPDATE " . _tbl('users') . "
               SET password_hash=?
             WHERE $condition";

    db()->command($sql, $sql_params);
  }


  // Checks $password against $password_hash for the row in the user table
  // where $column_name=$column_value.  Typically $column_name will be either
  // 'name' or 'email'.
  // Returns a boolean: true if they match, otherwise false.
  private function checkPassword(
    #[\SensitiveParameter]
    string $password,
    string $password_hash,
    string $column_name,
    string $column_value) : bool
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
