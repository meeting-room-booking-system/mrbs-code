<?php

/* $Id$
 *
 * Authentication scheme that uses POP3 as the source for user
 * authentication.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    # Or any other string
 * $auth["type"]  = "pop3";
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "pop3user1";
 * $auth["admin"][] = "pop3user2";
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
   global $pop3_host;
   global $pop3_port;
   $match= array();
   $shared_secret = "";

   // Check if we do not have a username/password
   if(!isset($user) || !isset($pass) || strlen($pass)==0)
   {
      return 0;
   }

   $error_number = "";
   $error_string = "";

   // Connect to POP3 server
   $stream = fsockopen( $pop3_host, $pop3_port, $error_number, $error_string, 15 );
   $response = fgets( $stream, 1024 );

   // first we try to use APOP, and then if that fails we fall back to traditional stuff

   // get the shared secret ( something on the greeting line that looks like <XXXX> )
   if ( preg_match( '/(<[^>]*>)/', $response, $match ) )
   {
       $shared_secret = $match[0];
   }

   // if we have a shared secret then try APOP
   if ($shared_secret) {
      $md5_token = md5("$shared_secret$pass");

      if ($stream) {
	 $auth_string = "APOP $user $md5_token\r\n";
	 fputs( $stream, $auth_string );

	 // read the response. if it's an OK then we're authenticated
	 $response = fgets( $stream, 1024 );
	 if( substr( $response, 0, 3 ) == '+OK' ) {
	    fputs( $stream, "QUIT\r\n" );
	    return 1;
	 }
      }
   } // end shared secret if

   // if we've still not authenticated then try using traditional methods
   // need to reconnect if we tried APOP
   if ($shared_secret) {
      $stream = fsockopen( $pop3_host, '110', $error_number, $error_string, 15 );
      $response = fgets( $stream, 1024 );
   }

   // send standard POP3 USER and PASS commands
   if( $stream ) {
      fputs( $stream, "USER $user\r\n" );
      $response = fgets( $stream, 1024 );
      if( substr( $response, 0, 3 ) == '+OK' ) {
	 fputs( $stream, "PASS $pass\r\n" );
	 $response = fgets( $stream, 1024 );
	 if( substr( $response, 0, 3 ) == '+OK' ) {
	    return 1;
	 }
      }
      fputs( $stream, "QUIT\r\n" );
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
      if(strcasecmp($user, $lev1_admin[$i]) == 0)
	 return 2;
   }

   // Everybody else is access level '1'
   return 1;
}

?>
