<?php
namespace MRBS;

/*****************************************************************************
*
*   File name       auth_db_ext.inc
*
*   Description     Authenticate users from a table in another database.
*
*   Notes           To use this authentication scheme, set in config.inc.php:
*         $auth["type"]  = "db_ext";
*                   Supports passwords stored in a variety of formats.
*
*   History
*    Available in the source control system
*
******************************************************************************/

global $auth;

if (empty($auth['db_ext']['db_system']))
{
  $auth['db_ext']['db_system'] = 'mysql';
}

$persist = 0;
$port = isset($auth['db_ext']['db_port']) ? (int)$auth['db_ext']['db_port'] : NULL;
  
$db_ext_conn = DBFactory::create($auth['db_ext']['db_system'],
                                 $auth['db_ext']['db_host'],
                                 $auth['db_ext']['db_username'],
                                 $auth['db_ext']['db_password'],
                                 $auth['db_ext']['db_name'],
                                 $persist,
                                 $port);


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
  global $auth, $db_ext_conn;

  $retval = false;


  // syntax_casesensitive_equals() modifies our SQL params array for us.   We need an exact match -
  // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '

  $sql_params = array();

  $query = "SELECT " . $db_ext_conn->quote($auth['db_ext']['column_name_password']) .
           "FROM " . $db_ext_conn->quote($auth['db_ext']['db_table']) .
           "WHERE " . $db_ext_conn->syntax_casesensitive_equals($auth['db_ext']['column_name_username'],
                                                                utf8_strtolower($user),
                                                                $sql_params);

  $stmt = $db_ext_conn->query($query, $sql_params);

  if ($stmt->count() == 1) // force a unique match
  {
    $row = $stmt->row(0);

    switch ($auth['db_ext']['password_format'])
    {
      case 'md5':
        if (md5($pass) == $row[0])
        {
          $retval = $user;
        }
        break;

      case 'sha1':
        if (sha1($pass) == $row[0])
        {
          $retval = $user;
        }
        break;

      case 'sha256':
        if (hash('sha256', $pass) == $row[0])
        {
          $retval = $user;
        }
        break;

      case 'crypt':
        $recrypt = crypt($pass,$row[0]);
        if ($row[0] == $recrypt)
        {
          $retval = $user;
        }
        break;

      case 'password_hash':
        if (password_verify($pass, $row[0]))
        {
          $retval = $user;
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
          $retval = $user;
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
  global $auth, $db_ext_conn;

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

  // Get user level from external database
  if (isset($auth['db_ext']['column_name_level']) && ($auth['db_ext']['column_name_level'] != ''))
  {
     $persist = 0;
     $port = isset($auth['db_ext']['db_port']) ? (int)$auth['db_ext']['db_port'] : NULL;
     
     // syntax_casesensitive_equals() modifies our SQL params array for us.   We need an exact match -
     // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '

     $sql_params = array();
     $query = "SELECT " . $db_ext_conn->quote($auth['db_ext']['column_name_level']) . "
                 FROM " . $db_ext_conn->quote($auth['db_ext']['db_table']) . "
                WHERE " . $db_ext_conn->syntax_casesensitive_equals($auth['db_ext']['column_name_username'],
                                                                    utf8_strtolower($user),
                                                                    $sql_params) . "
                LIMIT 1";

     $stmt = $db_ext_conn->query($query, $sql_params);
     
     if ($stmt->count() == 0)
     {
       return 0;
     }
     
     $row = $stmt->row(0);
     
     return $row[0];
  }

  // Everybody else is access level '1'
  return 1;
}


// Gets the user's email address.   Returns an empty
// string if one can't be found
function authGetUserEmail($user)
{
  global $auth, $db_ext_conn;
  
  if (!isset($user) || $user === '')
  {
    return '';
  }
  
  if (!isset($auth['db_ext']['column_name_email']) || ($auth['db_ext']['column_name_email'] == ''))
  {
    return get_default_email($user);
  }
  

  $persist = 0;
  $port = isset($auth['db_ext']['db_port']) ? (int)$auth['db_ext']['db_port'] : NULL;
  
  // syntax_casesensitive_equals() modifies our SQL params array for us.   We need an exact match -
  // MySQL allows trailing spaces when using an '=' comparison, eg 'john' = 'john '

  $sql_params = array();
  $query = "SELECT " . $db_ext_conn->quote($auth['db_ext']['column_name_email']) . "
              FROM " . $db_ext_conn->quote($auth['db_ext']['db_table']) . "
             WHERE " . $db_ext_conn->syntax_casesensitive_equals($auth['db_ext']['column_name_username'],
                                                                 utf8_strtolower($user),
                                                                 $sql_params) . "
             LIMIT 1";

  $stmt = $db_ext_conn->query($query, $sql_params);
  
  if ($stmt->count() == 0)
  {
    return 0;
  }
  
  $row = $stmt->row(0);
  
  return $row[0];
}
