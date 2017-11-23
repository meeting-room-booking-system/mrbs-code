<?php
namespace MRBS;

use MRBS\Form\Form;

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
  if ((false === session_start()) && ($auth['session'] == 'php'))
  {
    // Checking the result of session_start() is not very reliable.   Before PHP 5.3.0
    // it always returned TRUE.   Even after 5.3.0 it seems to return TRUE even if the
    // session_save_path is not writable.  A warning is generated, but session_start()
    // still returns TRUE.
    $message = "MRBS: could not start session.  The most likely causes are that session.save_path " .
               "has not been set properly in your php.ini file, or else that the session.save_path " .
               "directory is not writable.  Check your php.ini file and the file permissions on the ".
               "session save path.";
    trigger_error($message, E_USER_WARNING);
    fatal_error("MRBS: server configuration error. Check your error log for more details.");
  }
}

// Start up sessions 
session_init();

// If sessions didn't work, then set a cookie containing the CSRF token.
if (session_id() === '')
{
  // Note that the technique of creating a dummy form to store the token
  // does not work when using sessions.  It only works for cookies.   That's
  // because when using sessions, the new token is stored immediately.  So by
  // the time we come to read $_SESSION to check the token we will be looking
  // at the *new* token.   However, when using cookies, the browser will have
  // already sent the cookie by the time we get to this point, so when reading
  // $_COOKIE we are looking at the *old* token, which is what we want.
  $dummy_form = new Form();
}
