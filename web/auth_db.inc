<?php
/*****************************************************************************\
*									      *
*   File name       auth_db.inc						      *
*									      *
*   Description	    Authenticate users from a table in the MRBS database.     *
*									      *
*   Notes	    To use this authentication scheme, set in config.inc.php: *
*			$auth["type"]  = "db";				      *
*									      *
*   History								      *
*    2004/01/11 JFL Created this file					      *
*									      *
\*****************************************************************************/

// $Id$

/* session_php.inc and session_cookie.inc will add a link to the user list
    in the logon box, if the value $user_list_link is set. */
$user_list_link = "edit_users.php";
$user_link = "edit_users.php";

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
   global $mdb;
   $user=strtolower($user);
   //return sql_query1("select count(*) from mrbs_users where name='$user' and password='$pass';");
   return $mdb->queryOne("SELECT count(*)
   						  FROM   mrbs_users
                          WHERE  name=" . $mdb->getTextValue("$user") . "
                          AND    password=" . $mdb->getTextValue("$pass"), 'integer');
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