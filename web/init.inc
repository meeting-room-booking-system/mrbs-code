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
  location_header(multisite($_SESSION['kiosk_url'] ?? url_base() . "index.php?kiosk=$kiosk_default_mode"));
}
