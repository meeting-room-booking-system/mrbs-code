<?php

/* $Id$
 *
 * This is a slight variant of auth_ip. 
 * Authentication scheme try to use DNS name to determine
 * admin privileges instaed of ip. Anyone who can access
 *  the server can make bookings etc.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["type"]  = "name";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "DNSname1";
 * $auth["admin"][] = "DNSname2";
 */

# No need to perform authentication - if no DNSname is returned, ip address
# is used
function authGet($realm) { }

# Always validates
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
	global $REMOTE_ADDR;
	$remotehostname = gethostbyaddr($REMOTE_ADDR);
        return $remotehostname;
}

# NOT used :
function getUserPassword()
{
	return "dummy";
}

?>