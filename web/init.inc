<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Session\SessionFactory;
use SessionHandler;


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


function init_errors() : void
{
  global $debug;

  // If the error log file is a relative path then turn it into an absolute one in
  // order to avoid problems in shutdown when the working directory can change.
  // (See the notes in https://www.php.net/manual/en/function.register-shutdown-function.php).
  // Check for both Windows and Unix style separators because Unix separators can be used
  // on Windows.
  $error_log = ini_get('error_log');
  if (isset($error_log) &&
      ($error_log !== '') &&
      (utf8_strpos($error_log, '/') === false) &&
      (utf8_strpos($error_log, '\\') === false))
  {
    ini_set('error_log', getcwd() . '/' . $error_log);
  }

  // Make sure notice errors are not reported, they can break mrbs code:
  $error_level = E_ALL & ~E_NOTICE & ~E_USER_NOTICE;

  if (defined("E_DEPRECATED"))
  {
    $error_level = $error_level & ~E_DEPRECATED;
  }

  // The Mail and Net libraries generate E_STRICT errors, so disable E_STRICT (which became
  // part of E_ALL in PHP 5.4)
  if (defined("E_STRICT"))
  {
    $error_level = $error_level & ~E_STRICT;
  }

  if ($debug)
  {
    $error_level = -1;
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', true);
    assert_options(ASSERT_ACTIVE, true);  // the default, but we'll make it explicit
  }
  else
  {
    assert_options(ASSERT_ACTIVE, false);
  }

  error_reporting ($error_level);
  set_error_handler(__NAMESPACE__ . "\\error_handler", $error_level);
  set_exception_handler(__NAMESPACE__ . "\\exception_handler");

  register_shutdown_function(__NAMESPACE__ . "\\shutdown_function");
}


// Initialise error reporting
init_errors();

// Start up sessions
session()->init();

// Flush the mail queue on shutdown
register_shutdown_function(__NAMESPACE__ . "\\MailQueue::flush");

