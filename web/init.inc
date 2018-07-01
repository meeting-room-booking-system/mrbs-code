<?php
namespace MRBS;

use MRBS\Form\Form;

function session_init()
{
  global $auth;
  
  $cookie_path = get_cookie_path();

  if (!isset($auth['session_php']['session_expire_time']))
  {
    // Default to the behaviour of previous versions of MRBS, use only
    // session cookies - no persistent cookie.
    $auth['session_php']['session_expire_time'] = 0;
  }

  session_name('MRBS_SESSID');  // call before session_set_cookie_params() - see PHP manual
  session_set_cookie_params($auth['session_php']['session_expire_time'], $cookie_path);

  $handler = new SessionHandlerDb();
  session_set_save_handler($handler, true);
  
  if (false === session_start())
  {
    // Check that the session started OK.   If we're using the 'php' session scheme then
    // they are essential.   Otherwise they are desirable for storing CSRF tokens, but if
    // they are not working we will fall back to using cookies.
    $message = "MRBS: could not start sessions";
    
    if ($auth['session'] == 'php')
    {
      throw new \Exception($message);
    }
    else
    {
      trigger_error($message, E_USER_WARNING);
    }
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
