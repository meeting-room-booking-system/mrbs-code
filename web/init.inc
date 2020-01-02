<?php
namespace MRBS;

use MRBS\Form\Form;


function init_joomla()
{
  $mainframe = JFactory::getApplication('site');
  $mainframe->initialise();
}


function init_sessions()
{
  global $auth;

  // The cookie session scheme doesn't use PHP sessions
  if ($auth['session'] == 'cookie')
  {
    return;
  }

  // Joomla has its own session management
  if ($auth['session'] == 'joomla')
  {
    init_joomla();
    return;
  }

  // Set some session settings, as a defence against session fixation.
  ini_set('session.use_only_cookies', '1');
  ini_set('session.use_strict_mode', '1');  // Only available since PHP 5.5.2, but does no harm before then
  ini_set('session.use_trans_sid', '0');

  $cookie_path = get_cookie_path();

  if (!isset($auth['session_php']['session_expire_time']))
  {
    // Default to the behaviour of previous versions of MRBS, use only
    // session cookies - no persistent cookie.
    $auth['session_php']['session_expire_time'] = 0;
  }

  // We don't want the session garbage collector to delete the session before it has expired
  if ($auth['session_php']['session_expire_time'] !== 0)
  {
    ini_set('session.gc_maxlifetime', $auth['session_php']['session_expire_time']);
  }

  session_name('MRBS_SESSID');  // call before session_set_cookie_params() - see PHP manual
  session_set_cookie_params($auth['session_php']['session_expire_time'], $cookie_path);

  $current_db_schema_version = db_schema_version();
  // The session table was created in Upgrade 56.   Before that we will ignore any errors
  // to do with DB sessions.
  $session_table_should_exist = ($current_db_schema_version >= 56);

  try
  {
    $handler = new SessionHandlerDb();
    session_set_save_handler($handler, true);
    $session_started = session_start();
  }
  catch(\Exception $e)
  {
    if ($session_table_should_exist)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
    }
    $session_started = false;
  }

  if ($session_started === false)
  {
    if ($session_table_should_exist)
    {
      $message = "Could not start DB sessions, trying ordinary PHP sessions.";
      trigger_error($message, E_USER_WARNING);
    }

    $handler = new \SessionHandler();
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

        // If sessions didn't work, then set a cookie containing the CSRF token.

        // Note that the technique of creating a dummy form to store the token
        // does not work when using sessions.  It only works for cookies.   That's
        // because when using sessions, the new token is stored immediately.  So by
        // the time we come to read $_SESSION to check the token we will be looking
        // at the *new* token.   However, when using cookies, the browser will have
        // already sent the cookie by the time we get to this point, so when reading
        // $_COOKIE we are looking at the *old* token, which is what we want.
        $dummy_form = new Form();
      }
    }
  }
}


// Start up sessions
init_sessions();

