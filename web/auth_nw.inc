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
    
    // Check if we do not have a username/password
    if(empty($user) || empty($pass))
    {
        authGet($auth["realm"]);
        return 0;
    }
    
    // Generate the command line
    $cmd = $auth["prog"] . " -S " . $auth["params"] . " -U '$user'";
    
    // Run the program, sending the password to stdin.
    $p = popen($cmd, "w");
    if (!$p)
        return 0;
    fputs($p, $pass);
    if (pclose($p) == 0)
        return 1;
    
    // If we failed prompt for username/password - WHY?
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
