<?php
namespace MRBS;

use MRBS\Form\Form;


function init_errors()
{
  global $debug;
  
  // Make sure notice errors are not reported, they can break mrbs code:
  $error_level = E_ALL & !E_NOTICE & !E_USER_NOTICE;

  if (defined("E_DEPRECATED"))
  {
    $error_level = $error_level & !E_DEPRECATED;
  }

  // The Mail and Net libraries generate E_STRICT errors, so disable E_STRICT (which became
  // part of E_ALL in PHP 5.4)
  if (defined("E_STRICT"))
  {
    $error_level = $error_level & !E_STRICT;
  }
  
  if ($debug)
  {
    $error_level = -1;
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', true);
  }
  
  error_reporting ($error_level);
  set_error_handler(__NAMESPACE__ . "\\error_handler");
  set_exception_handler(__NAMESPACE__ . "\\exception_handler");
  
  register_shutdown_function(__NAMESPACE__ . "\\shutdown_function");
}


function init_joomla()
{
  // Not quite sure what's going on here.   JFactory::getSession() needs to be called
  // before JFactory::getApplication() in order to keep Joomla 2 happy and after it for
  // Joomla 3.  Doing it both before and after seems to keep them both happy.
  $session = JFactory::getSession();
  $mainframe = JFactory::getApplication('site');
  $session = JFactory::getSession();
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

  session_name('MRBS_SESSID');  // call before session_set_cookie_params() - see PHP manual
  session_set_cookie_params($auth['session_php']['session_expire_time'], $cookie_path);
  
  try
  {
    $handler = new SessionHandlerDb();
    session_set_save_handler($handler, true);
    $session_started = session_start();
  }
  catch(\Exception $e)
  {
    $session_started = false;
  }
  
  if ($session_started === false)
  {
    $message = "Could not start DB sessions, trying ordinary PHP sessions.  Note that this " .
               "error will normally only occur if the sessions table doesn't exist, which " .
               "will be the case when upgrading to database schema version 56.  If it " .
               "happens at any other time then something has gone wrong and it should be " .
               "investigated.";
               
    trigger_error($message, E_USER_WARNING);
    
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

// Initialise error reporting
init_errors();

// Start up sessions 
init_sessions();

