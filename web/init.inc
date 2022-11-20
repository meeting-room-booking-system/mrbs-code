<?php
namespace MRBS;


// Initialise error reporting
init_errors();

// Flush the mail queue on shutdown
register_shutdown_function(__NAMESPACE__ . "\\MailQueue::flush");

// Set the session scheme if it hasn't been already
if (!isset($auth['session']))
{
  switch ($auth['type'])
  {
    case 'cas':
    case 'joomla':
    case 'saml':
    case 'wordpress':
      $auth['session'] = $auth['type'];
      break;
    default:
      $auth['session'] = 'php';
      break;
  }
}

// Start up sessions
// Default to the behaviour of previous versions of MRBS, use only
// session cookies - no persistent cookie.
$lifetime = $auth['session_php']['session_expire_time'] ?? 0;
session()->init($lifetime);

if (method_exists(session(), 'processForm'))
{
  session()->processForm();
}

// If we're in kiosk mode make sure somebody's not trying to get to
// anywhere other than index.php, kiosk.php or one of the js/***.js.php pages.
if (is_kiosk_mode() &&
    (!isset($server['SCRIPT_NAME']) || !preg_match('/index\.php$|kiosk\.php$|js\/.+\.js\.php$/', $server['SCRIPT_NAME'])))
{
  location_header(multisite($_SESSION['kiosk_url'] ?? 'index.php'));
}
