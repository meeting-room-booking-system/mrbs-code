<?php

// include the authentification wrappers
include "auth_$auth[type].inc";

/* getAuthorised($user, $pass)
 * 
 * Check to see if the user name/password is valid
 * 
 * $user  - The user name
 * $pass  - The users password
 * 
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getAuthorised($user, $pass)
{
	return authValidateUser($user, $pass);
}

/* getWritable($creator, $user)
 * 
 * Determines if a user is able to modify an entry
 *
 * $creator - The creator of the entry
 * $user    - Who wants to modify it
 *
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getWritable($creator, $user, $lev1_admin)
{
	// Always allowed to modify your own stuff
	if($creator == $user)
		return 1;
	
	if(authGetUserLevel($user, $lev1_admin) >= 2)
		return 1;
	
	// Unathorised access
	return 0;
}


?>
