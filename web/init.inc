<?php
declare(strict_types=1);
namespace MRBS;

// Site-specific php.ini settings
use MRBS\Errors\Errors;

if (!empty($ini_directives))
{
  foreach ($ini_directives as $option => $value)
  {
    ini_set($option, $value);
  }
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

// Initialise the session.  This is necessary because some session schemes, eg Joomla,
// use the same third party interfaces, eg \Psr\Log\LoggerInterface, as MRBS, but of
// different versions. The MRBS one tends to be an earlier version of the interface than
// Joomla's, with less strict type declarations.  If the MRBS autoloader loads the MRBS
// version first, then when the Joomla autoloader tries to load a class that implements
// the interface you get errors such as
//
// "Declaration of Psr\Log\AbstractLogger::emergency(Stringable|string $message, array
// $context = []): void must be compatible with Psr\Log\LoggerInterface::emergency($message,
// array $context = [])"
//
// But if we initialise the session *before* calling Errors::init(), then this will force the
// Joomla version of the interface to be loaded first, and there won't be any errors because
// the MRBS classes implementing the interface are compatible (ie the MRBS type declarations
// are a superset of the Joomla interface declarations).
//
// TODO: is there a better way of solving this problem?  Maybe use a REST API to communicate with
// TODO: Joomla, rather than including Joomla files?  We'd then also be able to authenticate
// TODO: against remote servers.
session();

// Initialise error reporting
Errors::init();

// Check and adjust if necessary the format of $override_locale which changed in versions
// of MRBS > 1.7.1
if (!empty($override_locale))
{
  $new_override_locale = System::getBCPlocale($override_locale);
  if (mb_strtolower($override_locale) != mb_strtolower($new_override_locale))
  {
    $message = 'The config variable $override_locale should now be in BCP 47 format. ' .
      "Please change '$override_locale' to '$new_override_locale'.";
    trigger_error($message, E_USER_NOTICE);
    $override_locale = $new_override_locale;
  }
}

// Initialise the language system
Language::init($override_locale ?? null);

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
