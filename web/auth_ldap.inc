<?php

// $Id$

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
	global $ldap_host;
	global $ldap_base_dn;
	global $ldap_filter;
	
	// Check if we do not have a username/password
	// User can always bind to LDAP anonymously with empty password,
	// therefore we need to block empty password here...
	if(!isset($user) || !isset($pass) || strlen($pass)==0)
	{
		authGet($auth["realm"]);
		return 0;
	}
	
	// Check if $pass equal to "userPassword" attribute in $dn	
	$ldap=@ldap_connect($ldap_host);
	if($ldap)
	{
		for($i = 0; $ldap_base_dn[$i]; $i++)
		{
		        $dn = "cn=" . $user . "," . $ldap_base_dn[$i];
			
			if(@ldap_bind($ldap, $dn, $pass))
			{
                               if (! $ldap_filter)
                               {
                                      @ldap_unbind($ldap);
                                      return 1;
                               }
                               else
                               {
                                      $res = @ldap_search($ldap,
                                                          $ldap_base_dn[$i],
                                                          "(&(uid=$user)($ldap_filter))",
                                                          array());
                                       if (@ldap_count_entries($ldap, $res) > 0)
                                       {
                                              @ldap_unbind($ldap);
                                              return 1;
                                       }
                               }
			}
		}
		
		@ldap_unbind($ldap);
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
	global $PHP_AUTH_USER;
	return $PHP_AUTH_USER;
}

function getUserPassword()
{
	global $PHP_AUTH_PW;
	return $PHP_AUTH_PW;
}

?>
