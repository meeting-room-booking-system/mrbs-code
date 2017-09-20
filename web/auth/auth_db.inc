<?php
namespace MRBS;

/*****************************************************************************
*
*   File name       auth_db.inc
*
*   Description     Authenticate users from a table in the MRBS database.
*
*   Notes           To use this authentication scheme, set in config.inc.php:
*         $auth["type"]  = "db";
*
*   History
*    Available in the source control system
*
******************************************************************************/


function rehash($password, $column_name, $column_value)
{
  global $tbl_users;
  
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
  
  $sql = "UPDATE $tbl_users
             SET password_hash=?
           WHERE $condition";
    
  db()->command($sql, $sql_params);
}


// Checks $password against $password_hash for the row in the user table
// where $column_name=$column_value.  Typically $column_name will be either
// 'name' or 'email'.
// Returns a boolean: true if they match, otherwise false.
function checkPassword($password, $password_hash, $column_name, $column_value)
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
    elseif (!\PasswordCompat\binary\check())
    {
      // password_verify() failed and we are using a version of PHP where
      // crypt() is broken and so we are stuck, unable to verify the 
      // password.   What has probably happened is that the passwords were
      // created on a system running PHP >= 5.3.7 and since then the database
      // was migrated to a system running PHP < 5.3.7.    For further details
      // see http://stackoverflow.com/questions/12459896/password-compat-for-older-php-version
      trigger_error("Password created in a newer version of PHP and cannot be checked in this version",
                    E_USER_WARNING);
    }
  }
  /* Otherwise it's a legacy MD5 hash */
  else
  {
    if (md5($password) == $password_hash)
    {
      $result = true;

      if (\PasswordCompat\binary\check())
      {
        $do_rehash = true;
      }
    }
  }

  if ($do_rehash)
  {
    rehash($password, $column_name, $column_value);
  }

  return $result;
}

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
function authValidateUser($user, $pass)
{
  // The string $user that the user logged on with could be either a username or
  // an email address, or even possibly just the local part of an email address.
  // So it's just possible that there is more than one user with this password and
  // username | email address | local-part.    If we get more than one, then we don't
  // know which user it is, so we return false.
  $valid_usernames = array();
  
  if (($valid_username = authValidateUsername($user, $pass)) !== false)
  {
    $valid_usernames[] = $valid_username;
  }

  $valid_usernames = array_merge($valid_usernames, authValidateEmail($user, $pass));
  $valid_usernames = array_unique($valid_usernames);

  return (count($valid_usernames) == 1) ? $valid_usernames[0] : false;
}


/* authValidateUsername($user, $pass)
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
function authValidateUsername($user, $pass)
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
           WHERE " .
         db()->syntax_casesensitive_equals('name', utf8_strtolower($user), $sql_params) .
         "
           LIMIT 1";

  $res = db()->query($sql, $sql_params);
  
  $row = $res->row_keyed(0);
  
  if (!isset($row['password_hash']))
  {
    // No user found with that name
    return false;
  }

  return (checkPassword($pass, $row['password_hash'], 'name', $user)) ? $user : false;
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
function authValidateEmail($email, $pass)
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
      $condition = "LOWER(?)=LOWER(" . db()->syntax_simple_split('email', '@', 1, $sql_params) .")";
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

  $res = db()->query($sql, $sql_params);
  
  $rows = $res->all_rows_keyed();
  
  // Check all the users that have this email address and password hash. 
  foreach($rows as $row)
  {
    if (checkPassword($pass, $row['password_hash'], 'email', $email))
    {
      $valid_usernames[] = $row['name'];
    }
  }
  
  return $valid_usernames;
}


// Checks whether validation of a user by email address is possible
// and allowed.
function canValidateByEmail()
{
  return true;
}


/* authGetUserLevel($user)
 * 
 * Determines the user's access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user)
{
  global $tbl_users;
  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }
  
  $result = db()->query1("SELECT level FROM $tbl_users WHERE name=? LIMIT 1",
                       array(utf8_strtolower($user)));

  return ($result == -1) ? 0 : $result;
}


// Gets the user's email address.   Returns an empty
// string if one can't be found
function authGetUserEmail($user)
{
  global $tbl_users;
  
  if (!isset($user) || $user === '')
  {
    return '';
  }
  
  $sql = "SELECT email 
            FROM $tbl_users 
           WHERE name=?
           LIMIT 1";
           
  $email = db()->query1($sql, array($user));
  
  return ($email == -1) ? '' : $email;
}

