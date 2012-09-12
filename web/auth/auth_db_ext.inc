<?php
/*****************************************************************************
*
*   File name       auth_db_ext.inc
*
*   Description     Authenticate users from a table in another database.
*
*   Notes           To use this authentication scheme, set in config.inc.php:
*         $auth["type"]  = "db_ext";
*                   Assumes passwords are stored in the other table in
*                   plaintext, authValidateUser() will need to be changed if
*                   the password is stored differently.
*
*   History
*    Available in the source control system
*
******************************************************************************/

// $Id$


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
  global $auth;

  $retval = 0;

  if (empty($auth['db_ext']['db_system']))
  {
    $auth['db_ext']['db_system'] = 'mysql';
  }

  $conn = sql_connect($auth['db_ext']['db_system'],
                      $auth['db_ext']['db_host'],
                      $auth['db_ext']['db_username'],
                      $auth['db_ext']['db_password'],
                      $auth['db_ext']['db_name']);

  // sql_syntax_casesensitive_equals() does the escaping.   We need an exact match -
  // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '
  $query = "SELECT " . $auth['db_ext']['column_name_password'] .
            " FROM " . $auth['db_ext']['db_table'] .
           " WHERE " . sql_syntax_casesensitive_equals($auth['db_ext']['column_name_username'], strtolower($user));

  $r = sql_query($query, $conn);

  if ($r && (sql_count($r, $conn) == 1)) // force a unique match
  {
    $row = sql_row($r, 0, $conn);

    switch ($auth['db_ext']['password_format'])
    {
      case 'md5':
        if (md5($pass) == $row[0])
        {
          $retval = 1;
        }
        break;

      case 'sha1':
        if (sha1($pass) == $row[0])
        {
          $retval = 1;
        }
        break;

      case 'crypt':
        $recrypt = crypt($pass,$row[0]);
        if ($row[0] == $recrypt)
        {
          $retval = 1;
        }
        break;

      default:
        // Otherwise assume plaintext

        // Backwards-compatibility config option
        if ($auth['db_ext']['use_md5_passwords'] == 1)
        {
          $pass = md5($pass);
        }

        if ($pass == $row[0])
        {
          $retval = 1;
        }
        break;
    }
  }

  return $retval;
}

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user)
{
  global $auth;
  $admins = $auth['admin'];
  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }

  // Check if the user is can modify
  for($i = 0; isset($admins[$i]); $i++)
  {
    if(strcasecmp($user, $admins[$i]) == 0)
    {
      return 2;
    }
  }

  // Everybody else is access level '1'
  return 1;
}

?>
