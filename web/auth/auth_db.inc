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

/* session_php.inc and session_cookie.inc will add a link to the user list
    in the logon box, if the value $user_list_link is set. */
$user_list_link = "edit_users.php";


function rehash($password_hash, $column_name, $column_value)
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
      // on most systems, it isn't always true.  The domain part is case insensitive but the local part can
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
    rehash($password_hash, $column_name, $column_value);
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
 *   true     - The pair are invalid or do not exist
 *   false    - The pair are valid
 */
function authValidateUser($user, $pass)
{
  global $tbl_users;

  $sql_params = array();

  // We use syntax_casesensitive_equals() rather than just '=' because '=' in MySQL
  // permits trailing spacings, eg 'john' = 'john '.   We could use LIKE, but that then
  // permits wildcards, so we could use a comnination of LIKE and '=' but that's a bit
  // messy.  WE could use STRCMP, but that's MySQL only.
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

  return checkPassword($pass, $row['password_hash'], 'name', $user);
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

