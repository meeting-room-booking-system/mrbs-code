<?php

# No need to perform authentication - ip address always there
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
	for($i = 0; $lev1_admin[$i]; $i++)
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
	return $REMOTE_ADDR;
}

function getUserPassword()
{
	return "dummy";
}

?>
