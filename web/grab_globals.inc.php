<?php
// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.
// +---------------------------------------------------------------------------+
// | Grabs the names and values of the '$HTTP_*_VARS' variables.
// |---------------------------------------------------------------------------+
// | This library grabs the names and values of the variables sent or posted to
// | a script in the '$HTTP_*_VARS' and new globals arrays defined with
// | php 4.1+ and sets simple globals variables from them.
// | It does the same work for other external variables used in MRBS.
// | USE : This file should be included in all files where external variables
// |       are used, preferably before other included files.
// | :WARNING: thierry_bo 030216: if any new external variable is used,
// |           it must also be added here.
// +---------------------------------------------------------------------------+
// | @author    Original Authors : PhpMyAdmin project.
// | @author    thierry_bo.
// | @version   $Revision$.
// +---------------------------------------------------------------------------+
//
// $Id$

// -- GET --
if (!empty($_GET))
{
    extract($_GET, EXTR_OVERWRITE);
}
else if (!empty($HTTP_GET_VARS))
{
    extract($HTTP_GET_VARS, EXTR_OVERWRITE);
}

// -- POST --
if (!empty($_POST))
{
    extract($_POST, EXTR_OVERWRITE);
}
else if (!empty($HTTP_POST_VARS))
{
    extract($HTTP_POST_VARS, EXTR_OVERWRITE);
}

// -- PHP_SELF --
if (!empty($_SERVER) && isset($_SERVER['PHP_SELF']))
{
    $PHP_SELF = $_SERVER['PHP_SELF'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['PHP_SELF']))
{
    $PHP_SELF = $HTTP_SERVER_VARS['PHP_SELF'];
}

// -- PHP_AUTH_USER --
if (!empty($_SERVER) && isset($_SERVER['PHP_AUTH_USER']))
{
    $PHP_AUTH_USER = $_SERVER['PHP_AUTH_USER'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['PHP_AUTH_USER']))
{
    $PHP_AUTH_USER = $HTTP_SERVER_VARS['PHP_AUTH_USER'];
}

// -- PHP_AUTH_PW --
if (!empty($_SERVER) && isset($_SERVER['PHP_AUTH_PW']))
{
    $PHP_AUTH_PW = $_SERVER['PHP_AUTH_PW'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['PHP_AUTH_PW']))
{
    $PHP_AUTH_PW = $HTTP_SERVER_VARS['PHP_AUTH_PW'];
}

// -- REMOTE_USER --
if (!empty($_SERVER) && isset($_SERVER['REMOTE_USER']))
{
    $REMOTE_USER = $_SERVER['REMOTE_USER'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['REMOTE_USER']))
{
    $REMOTE_USER = $HTTP_SERVER_VARS['REMOTE_USER'];
}

// -- REMOTE_ADDR --
if (!empty($_SERVER) && isset($_SERVER['REMOTE_ADDR']))
{
    $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['REMOTE_ADDR']))
{
    $REMOTE_ADDR = $HTTP_SERVER_VARS['REMOTE_ADDR'];
}

// -- QUERY_STRING --
if (!empty($_SERVER) && isset($_SERVER['QUERY_STRING']))
{
    $QUERY_STRING = $_SERVER['QUERY_STRING'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['QUERY_STRING']))
{
    $QUERY_STRING = $HTTP_SERVER_VARS['QUERY_STRING'];
}

// -- HTTP_ACCEPT_LANGUAGE --
if (!empty($_SERVER) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
{
    $HTTP_ACCEPT_LANGUAGE = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE']))
{
    $HTTP_ACCEPT_LANGUAGE = $HTTP_SERVER_VARS['HTTP_ACCEPT_LANGUAGE'];
}

// -- HTTP_REFERER --
if (!empty($_SERVER) && isset($_SERVER['HTTP_REFERER']))
{
    $HTTP_REFERER = $_SERVER['HTTP_REFERER'];
}
else if (!empty($HTTP_SERVER_VARS) && isset($HTTP_SERVER_VARS['HTTP_REFERER']))
{
    $HTTP_REFERER = $HTTP_SERVER_VARS['HTTP_REFERER'];
}

// +---------------------------------------------------------------------------+
/* Changes to this file :
 * $Log$
 */
?>
