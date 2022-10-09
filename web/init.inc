<?php
namespace MRBS;

use MRBS\Session\SessionFactory;


// Convenience wrapper function to provide access to a Session object
function session()
{
  global $auth;

  static $session_obj = null;

  if (is_null($session_obj))
  {
    $session_obj = SessionFactory::create($auth['session']);
  }

  return $session_obj;
}


// Initialise error reporting
init_errors();

// Start up sessions
session()->init();

// Flush the mail queue on shutdown
register_shutdown_function(__NAMESPACE__ . "\\MailQueue::flush");

