<?php

/* $Id$
 *
 * Authentication scheme that rely on OmniHttpd security for user 
 * authentication. THIS is suitable for few users because we have to create all
 * users connecting to MRBS, since they will have to login.
 *
 * To use this authentication scheme set the following things :
 * - Edit your virtual server hosting MRBS.
 * - Select security tab.
 * - IF not yet set, choose "User and Directory" security type. 
 * - Select "Users and groups" tab. 
 * - Here, select "New User" and create as many users (Username/passwords) as 
 *   you have users using MRBS. 
 * - Select "New Group".
 * - Type "MRBS" as group name and add all users you just created to this group.
 * - Now select "Access Control list" tab. 
 * - Select New. ENTER the relative path to MRBS. FOR example, if you created 
 *   the MRBS folder on the root web folder, you should type /MRBS/. 
 * - Now go to "user permission" tab, select " * ",
 * - select Properties", and type MRBS (remove the star) and select "Is group".
 *
 * That's all ! Confirm all windows. Now it is the web server that authenticate
 * each user. 
 *
 * 
 * in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    # Or any other string
 * $auth["type"]  = "omni";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "user1";
 * $auth["admin"][] = "user2";
 */
 
 /* getAuth($realm)
 * 
 *  No need to perform authentication - this is done by the server
 *  Always validates
 */
function authGet() { }

/* authValidateUser($user, $pass)
 * 
 * Always validate since this is the server that perform authentification
 */
function authValidateUser($user, $pass) { return 1; }

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
	
	// Check if the user can modify
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
	global $REMOTE_USER;
	return $REMOTE_USER;
}


# NOT used :
function getUserPassword()
{
	global $PHP_AUTH_PW;
	return $PHP_AUTH_PW;
}

?>