<?php

/* $Id$
 *
 * Authentication scheme that uses Windows NT domain users and Internet 
 * Information Server as the source for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 *      $auth["realm"]  = "";
 *      $auth["type"]   = "nt";
 *      $auth["prog"]   = "";
 *      $auth["params"] = "";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "nt_username1";
 * $auth["admin"][] = "nt_username2";
 *
 * See AUTHENTICATION  for more informations.
 */

/** 
 * Request that the username/password be given for the specified realm.
 *
 * For this scheme no need to perform authentication - NT User always there.
 * 
 * @param   string  $realm - Which username/password do we want.
 */
function authGet($realm) { }

/** 
 * Checks if the specified username/password pair are valid.
 *
 * For this authentication scheme always validates.
 * 
 * @param   string  $user   The user name
 * @param   string  $pass   The password
 * @return  int             non-zero - Always valid
 */
function authValidateUser($user, $pass)
{
    return 1;
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

function getUserName()
{
	global $AUTH_USER;
	$AUTH_USER = get_current_user();  
	return $AUTH_USER;

}

function getUserPassword()
{
	return "dummy";
}

?>