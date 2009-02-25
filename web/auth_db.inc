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

// The highest level of access (0=none; 1=user; 2+=admin).    Used in edit_users.php
// In the future we might want a higher level of granularity, eg to distinguish between
// different levels of admin
$max_level = 2;
// The lowest level of admin allowed to edit other users
$min_user_editing_level = 2;

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

  $user = strtolower(addslashes($user));

  // No addslashes() required because it's put in the database as an MD5,
  // which is safe.
  $pass = md5($pass);

  return max(0, sql_query1("select count(*) from $tbl_users where name='$user' and password='$pass';"));
  // The max is used to cover the case when there's an SQL Query error (ie -1), probably because $tbl_users
  // hasn't been set up yet.   In that case we want to return 0 (ie validation failed)  
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
  global $tbl_users;
  // User not logged in, user level '0'
  if(!isset($user))
  {
    return 0;
  }
  
  return max (0, sql_query1("SELECT level FROM $tbl_users WHERE name='$user' LIMIT 1"));
  // The max is used to cover the case when there's an SQL Query error (ie -1), probably because
  // the database hasn't yet been upgraded and there's no 'level' column.   In that case we want 
  // to return 0 (ie the user has no rights)
}

?>
