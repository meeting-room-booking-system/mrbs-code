<?php
namespace MRBS;



/*****************************************************************************\
*                                                                            *
*   File name      session_cookie.inc                                        *
*                                                                            *
*   Description    Manage sessions via cookies stored in the client browser. *
*                                                                            *
*   URL arguments  UserName       The user name                              *
*                  UserPassword   His password                               *
*                  target_url      Where we were going before login.          *
*                                                                            *
*   Notes          To use this session mechanism, set in config.inc.php:     *
*                  $auth["session"]  = "cookie";                             *
*                                                                            *
\*****************************************************************************/



$cookie_path = get_cookie_path();

// Delete old-style cookies
if (!empty($_COOKIE) && isset($_COOKIE["UserName"]))
{
  setcookie("UserName", '', time()-42000, $cookie_path);
}


