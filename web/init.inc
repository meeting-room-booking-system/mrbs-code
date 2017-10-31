<?php
namespace MRBS;

function session_init()
{
  global $auth;
  
  $cookie_path = get_cookie_path();

  if (!isset($auth["session_php"]["session_expire_time"]))
  {
    // Default to the behaviour of previous versions of MRBS, use only
    // session cookies - no persistent cookie.
    $auth["session_php"]["session_expire_time"] = 0;
  }

  session_name("MRBS_SESSID");  // call before session_set_cookie_params() - see PHP manual
  session_set_cookie_params($auth["session_php"]["session_expire_time"],
                            $cookie_path);
                            
  // Sessions are only essential if we're using the 'php' session scheme.   For other schemes
  // they are desirable as if they are working we will use them for storing CSRF tokens, but
  // if they are not working we will fall back to other techniques, eg cookies.
  if (($auth['session'] == 'php') && (false === session_start()))
  {
    // Checking the result of session_start() is not very reliable.   Before PHP 5.3.0
    // it always returned TRUE.   Even after 5.3.0 it seems to return TRUE even if the
    // session_save_path is not writable.  A warning is generated, but session_start()
    // still returns TRUE.
    trigger_error("MRBS: could not start session", E_USER_WARNING);
    fatal_error("MRBS: server configuration error.");
  }
}

// Start up sessions 
session_init();