<?php

/* $Id$
 *
 * Authentication scheme that uses NIS as the source for user
 * authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    # Or any other string
 * $auth["type"]  = "nis";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "nisuser1";
 * $auth["admin"][] = "nisuser2";
 *
 */

/* ~~JFL 2003/11/12 By default, use the http session mechanism */
if (!isset($auth['session'])) $auth['session']='http';

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
        global $auth;

	// Check if we do not have a username/password
	if(!isset($user) || !isset($pass))
	{
		return 0;
	}

        // untaint $user
        //preg_match("/(^\w+).*/",$user,$regs);
        //$user = $regs[1];

        $rs = yp_match (yp_get_default_domain(), "passwd.byname", $user);
	if ( ! empty ( $rs ) ) {
          $rs = explode ( ":", $rs );
	  if ( $rs[1] == crypt ( $pass, substr ( $rs[1], 0, 2 ) ) ) { 
            return 1;
          } else {
            return 0;
          }
        }

	// return failure
	return 0;
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
	for($i = 0; $lev1_admin[$i]; $i++)
	{
		if(strcmp($user, $lev1_admin[$i]) == 0)
			return 2;
	}
	
	// Everybody else is access level '1'
	return 1;
}

?>