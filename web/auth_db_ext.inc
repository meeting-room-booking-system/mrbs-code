<?php
/*****************************************************************************\
*									      *
*   File name       auth_db_ext.inc					      *
*									      *
*   Description	    Authenticate users from a table in another database.      *
*									      *
*   Notes	    To use this authentication scheme, set in config.inc.php: *
*			$auth["type"]  = "db_ext";			      *
*                   Assumes passwords are stored in the other table in        *
*                   plaintext, authValidateUser() will need to be changed if  *
*                   the password is stored differently.                       *
*									      *
*   History								      *
*    2005/05/11 JPB Created this file					      *
*									      *
\*****************************************************************************/

// $Id$

// Config

$my_db_host = 'localhost';
$my_db_username = 'db_user';
$my_db_password = 'db_password';
$my_db_name = 'db_name';
$my_db_table = 'table_name';
$column_name_username = 'name';
$column_name_password = 'password';


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
  global $my_db_host, $my_db_username, $my_db_password, $my_db_name,
    $my_db_table, $column_name_username, $column_name_password;

   $user=strtolower($user);

   $conn = mysql_connect($my_db_host, $my_db_username, $my_db_password);

   mysql_select_db($my_db_name, $conn);

   $r = mysql_query("SELECT count(*) FROM $my_db_table WHERE $column_name_username='$user' AND $column_name_password='$pass'", $conn);

   if ($r && (mysql_result($r, 0) == 1))
   {
     $retval = 1;
   }
   else
   {
     $retval = 0;
   }

   mysql_close($conn);

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
function authGetUserLevel($user, $lev1_admin)
{
   // User not logged in, user level '0'
   if(!isset($user))
      return 0;

   // Check if the user is can modify
   for($i = 0; isset($lev1_admin[$i]); $i++)
   {
      if(strcasecmp($user, $lev1_admin[$i]) == 0)
	 return 2;
   }

   // Everybody else is access level '1'
   return 1;
}

?>
