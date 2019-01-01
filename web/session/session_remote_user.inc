<?php 
namespace MRBS;

/********************************************************************************************\
*                                                                                            *
*   File name       session_remote_user.inc                                                  *
*                                                                                            *
*   Description     Get user identity/password using the REMOTE_USER environment variable.   *
*                   Both identity and password equal the value of REMOTE_USER.               *
*                                                                                            *
*   Notes           To use this session scheme, set in config.inc.php:                       *
*                     $auth['session']  = 'remote_user';                                     *
*                     $auth['type'] = 'none';                                                *
*                                                                                            *
*                                                                                            *
*                   If you want to display a login link, set in config.inc.php:              *
*                     $auth['remote_user']['login_link'] = '/login/link.html';               *
*                                                                                            *
*                   If you want to display a logout link, set in config.inc.php:             *
*                     $auth['remote_user']['logout_link'] = '/logout/link.html';             *
*                                                                                            *
\*********************************************************************************************/

require_once MRBS_ROOT . '/session/functions_session.inc';


