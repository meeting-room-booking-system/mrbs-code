<?php

// include the authentification wrappers
include "auth_$auth[type].inc";

/* getAuthorised($user, $pass, $level)
 * 
 * Check to see if the user name/password is valid
 * 
 * $user  - The user name
 * $pass  - The users password
 * $level - The access level required
 * 
 * Returns:
 *   0        - The user does not have the required access
 *   non-zero - The user has the required access
 */
function getAuthorised($user, $pass, $level)
{
	if(!authValidateUser($user, $pass))
		return 0;
	
	return (authGetUserLevel($user, $auth[admin]) >= $level);
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
function getWritable($creator, $user)
{
	global $auth;
	
	// Always allowed to modify your own stuff
	if($creator == $user)
		return 1;
	
	if(authGetUserLevel($user, $auth[admin]) >= 2)
		return 1;
	
	// Unathorised access
	return 0;
}

/* showAccessDenied()
 * 
 * Displays an appropate message when access has been denied
 * 
 * Retusns: Nothing
 */
function showAccessDenied($day, $month, $year, $area)
{
	global $lang;
	
	print_header($day, $month, $year, $area);
?>
  <H1><?echo $lang[accessdenied]?></H1>
  <P>
   <?echo $lang[norights]?>
  </P>
  <P>
   <A HREF=<? echo $HTTP_REFERER; ?>><? echo $lang[returnprev]; ?></A>
  </P>
 </BODY>
</HTML>
<?
}
?>
