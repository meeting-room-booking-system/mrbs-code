<?php

// $Id$

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
    if(empty($user) || empty($pass))
    {
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
