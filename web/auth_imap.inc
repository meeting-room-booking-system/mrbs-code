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

/* ~~JFL 2003/11/12 By default, use the http session mechanism */
if (!isset($auth['session'])) $auth['session']='http';

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
	$all_imap_hosts = array();
	$all_imap_ports = array();

	// Check if we do not have a username/password
	if(!isset($user) || !isset($pass) || strlen($pass)==0)
	{
		return 0;
	}

	# Check that if there is an array of hosts and an array of ports
	# then the number of each is the same
	if(is_array( $imap_host ) && is_array( $imap_port ) && count($imap_port) != count($imap_host) )
	{
		return 0;
	}

	# Transfer the list of imap hosts to an new value to ensure that
	# an array is always used.
	# If a single value is passed then turn it into an array
	if(is_array( $imap_host ) )
	{
		$all_imap_hosts = $imap_host;
	}
	else
	{
		$all_imap_hosts = array($imap_host);
	}

	# create an array of the port numbers to match the number of
	# hosts if a single port number has been passed.
	if(is_array( $imap_port ) )
	{
		$all_imap_ports = $imap_port;
	}
	else
	{
		while( each($all_imap_hosts ) )
		{
			$all_imap_ports[] = $imap_port;
		}
	}

	# iterate over all hosts and return if you get a successful login
	foreach( $all_imap_hosts as $idx => $host)
	{
		$error_number = "";
		$error_string = "";

		// Connect to IMAP-server
		$stream = fsockopen( $host, $all_imap_ports[$idx], $error_number, $error_string, 15 );
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
