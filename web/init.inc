<?php
declare(strict_types=1);
namespace MRBS;


// Initialise error reporting
init_errors();

// Flush the mail queue on shutdown
register_shutdown_function(__NAMESPACE__ . "\\MailQueue::flush");

// Check extensions
if ((version_compare(PHP_VERSION, '8.1.0') >= 0) && !extension_loaded('intl'))
{
  // We suppress deprecation messages each time we use strftime() in MRBS's emulation of the IntlDateFormatter
  // class and just have a single message here, in order to avoid flooding the log with messages.
  $message = "MRBS has detected that you do not have the PHP 'intl' extension loaded on your server and " .
             "so it is using the deprecated strftime() function instead.  You are recommended to enable " .
             "the 'intl' extension.";
  trigger_error($message, E_USER_DEPRECATED);
}

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


if (method_exists(session(), 'processForm'))
{
  session()->processForm();
}

// If we're in kiosk mode make sure somebody's not trying to get to anywhere other than
// index.php with a kiosk query string parameter, kiosk.php or one of the js/***.js.php pages.
if (is_kiosk_mode() &&
    !((isset($server['REQUEST_URI']) && preg_match('/index\.php\?.*kiosk=/', $server['REQUEST_URI'])) ||
      (isset($server['SCRIPT_NAME']) && preg_match('/kiosk\.php$|js\/.+\.js\.php$/', $server['SCRIPT_NAME']) )))
{
  $location = (session()->isset('kiosk_url')) ? session()->get('kiosk_url') : "index.php?kiosk=$kiosk_default_mode";
  location_header(multisite($location));
}
