<?php

/* $Id$
 *
 * Authentication scheme that uses IMAP as the source for user
 * authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    # Or any other string
 * $auth["type"]  = "imap";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "imapuser1";
 * $auth["admin"][] = "imapuser2";
 */

/* quoteIMAP($str)
 *
 * quote char's into valid IMAP string
 *
 * $str - String to be quoted
 *
 * Returns:
 *   quoted string
 */
function quoteIMAP($str)
{
    return ereg_replace('(["\\])', '\\\\1', $str);
}

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
	global $imap_host;
	global $imap_port;
	
	// Check if we do not have a username/password
	if(!isset($user) || !isset($pass) || strlen($pass)==0)
	{
		authGet($auth["realm"]);
		return 0;
	}
	
	$error_number = "";
	$error_string = "";
	
	// Connect to IMAP-server
	$stream = fsockopen( $imap_host, $imap_port, $error_number, $error_string, 15 );
	$response = fgets( $stream, 1024 );
	if( $stream ) {
		$logon_str = "a001 LOGIN \"" . quoteIMAP( $user ) . "\" \"" . quoteIMAP( $pass ) . "\"\r\n";
		fputs( $stream, $logon_str );
		$response = fgets( $stream, 1024 );
		if( substr( $response, 5, 2 ) == 'OK' ) {
			fputs( $stream, "a001 LOGOUT\r\n" );
			$response = fgets( $stream, 1024 );
			return 1;
		}
		fputs( $stream, "a001 LOGOUT\r\n" );
	}
	
	// If we failed prompt for username/password
	authGet($realm);
	
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
    return getAuthUserName();
}

function getUserPassword()
{
    return getAuthUserPassword();
}

?>