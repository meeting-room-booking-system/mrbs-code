<?php 
/*****************************************************************************\
*									      *
*   File name       auth_http.inc					      *
*									      *
*   Description	    Get user identity using the HTTP basic authentication.    *
*									      *
*   Notes	    To use this session scheme, set in config.inc.php:        *
*			$auth["session"]  = "http";			      *
*									      *
*   History								      *
*    2003/11/12 JFL Created this file					      *
*									      *
\*****************************************************************************/

// $Id$

/* authGet()
 * 
 * Request the user name/password
 * 
 * Returns: Nothing
 */
function authGet()
{
    global $auth;
    header("WWW-Authenticate: Basic realm=\"$auth[realm]\"");
    Header("HTTP/1.0 401 Unauthorized");
}

function getAuthUserName()
{
    global $PHP_AUTH_USER;
    if (isset($PHP_AUTH_USER))
    {
        $user = $PHP_AUTH_USER;
        if (get_magic_quotes_gpc())
        {
            $user = stripslashes($user);
        }
        return $user;
    }
    else
    {
        return null;
    }
}

function getAuthUserPassword()
{
    global $PHP_AUTH_PW;

    if (isset($PHP_AUTH_PW))
    {
        $pw = $PHP_AUTH_PW;
        if (get_magic_quotes_gpc())
        {
            $pw = stripslashes($pw);
        }
        return $pw;
    }
    else
    {
        return null;
    }
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
