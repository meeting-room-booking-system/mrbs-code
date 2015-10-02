<?php
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

// $Id$

/* session_php.inc and session_cookie.inc will add a link to the user list
    in the logon box, if the value $user_list_link is set. */
$user_list_link = "edit_users.php";


/* authValidateUser($user, $pass)
 * 
 * Checks if the specified username/password pair are valid
 * 
 * $user  - The user name
 * $pass  - The password
 * 
 * Returns:
 *   0        - The pair are invalid or do not exist
 *   non-zero - The pair are valid
 */

function authValidateUser($user, $pass)
{
  global $tbl_users;
  $result = 0;

  // We use sql_syntax_casesensitive_equals() rather than just '=' because '=' in MySQL
  // permits trailing spacings, eg 'john' = 'john '.   We could use LIKE, but that then
  // permits wildcards, so we could use a comnination of LIKE and '=' but that's a bit
  // messy.  WE could use STRCMP, but that's MySQL only.
  $sql = "SELECT password_hash
            FROM $tbl_users
           WHERE " .
         sql_syntax_casesensitive_equals('name', utf8_strtolower($user)) .
         "
           LIMIT 1";

  $res = sql_query($sql);
  if ($res == FALSE)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    return 0;
  }

  $row = sql_row_keyed($res, 0);

  $do_rehash = false;

  /* If the hash starts '$' it's a PHP password hash */
  if (substr($row['password_hash'], 0, 1) == '$')
  {
    if (password_verify($pass, $row['password_hash']))
    {
      $result = 1;
      if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT))
      {
        $do_rehash = true;
      }
    }
    elseif (!PasswordCompat\binary\check())
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
    if (md5($pass) == $row['password_hash'])
    {
      $result = 1;

      if (PasswordCompat\binary\check())
      {
        $do_rehash = true;
      }
    }
  }

  if ($do_rehash)
  {
    $sql = "UPDATE $tbl_users
           SET password_hash='".
      sql_escape(password_hash($pass, PASSWORD_DEFAULT)) ."'
           WHERE " .
      sql_syntax_casesensitive_equals('name', utf8_strtolower($user));
    sql_command($sql);
  }

  return $result;
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
  
  $result = sql_query1("SELECT level FROM $tbl_users WHERE name='" . sql_escape(utf8_strtolower($user)) . "' LIMIT 1");
  if ($result == -1)
  {
    $sql_error = sql_error();
    if (!empty($sql_error))
    {
      // It's possible that sql_query1 returned -1 because there were no matching rows,
      // so we only trigger an error if there was a genuine SQL error.
      trigger_error($sql_error, E_USER_WARNING);
    }
    return 0;
  }

  return $result;
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
           WHERE name='" . sql_escape($user) . "'
           LIMIT 1";
           
  $email = sql_query1($sql);
  
  if ($email == -1)
  {
    $sql_error = sql_error();
    if (!empty($sql_error))
    {
      // It's possible that sql_query1 returned -1 because there were no matching rows,
      // so we only trigger an error if there was a genuine SQL error.
      trigger_error($sql_error, E_USER_WARNING);
    }
    $email = '';
  }
  
  return $email;
}

