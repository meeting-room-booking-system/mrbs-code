<?php

/* $Id$
 *
 * Authentication scheme that uses an external script as the source
 * for user authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc:
 *
 * $auth["realm"]  = "MRBS";    # Or any other string
 * $auth["type"]   = "ext";
 * $auth["prog"]   = "authenticationprogram";  # The full path to the external
 *                                             # script
 * $auth["params"] = "#USERNAME# #PASSWORD# other-params" # Parameters to pass to
 *                                                        # the script, #USERNAME#
 *                                                        # and #PASSWORD#
 *                                                        # will be expanded to
 *                                                        # the values typed by
 *                                                        # the user.
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "username1";
 * $auth["admin"][] = "username2";
 *
 */

/* getAuth($realm)
 * 
 * Request that the username/password be given for the specified realm
 * 
 * $realm - Which username/password do we want.
 * 
 * Nothing
 */
function authGet($realm)
{
	Header("WWW-Authenticate: Basic realm=\"$realm\"");
	Header("HTTP/1.0 401 Unauthorized");
}

function version_check($vercheck)
{
  $minver = str_replace(".","", $vercheck);
  $curver = str_replace(".","", phpversion());

  if($curver >= $minver)
  {
    return true;
  }
  return false;
}

if (version_check("4.0.3") == false)
{
    include "escapeshellarg.inc";
}

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
		authGet($auth["realm"]);
		return 0;
	}
	
	// Generate the command line
	$cmd = $auth["prog"] . ' ' . $auth["params"];
	$cmd = preg_replace('/#USERNAME#/',escapeshellarg($user),$cmd);
	$cmd = preg_replace('/#PASSWORD#/',escapeshellarg($pass),$cmd);
	
	// Run the program
	exec($cmd, $output, $ret);
	
	// If it succeeded, return success
	if($ret == 0)
		return 1;
	
	// If we failed prompt for username/password
	if($user || $pass)
		authGet($auth["realm"]);
	
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
		if(strcasecmp($user, $lev1_admin[$i]) == 0)
			return 2;
	}
	
	// Everybody else is access level '1'
	return 1;
}

function getUserName()
{
	global $PHP_AUTH_USER;
	return $PHP_AUTH_USER;
}

function getUserPassword()
{
	global $PHP_AUTH_PW;
	return $PHP_AUTH_PW;
}

?>
