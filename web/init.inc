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
session()->init();

if (method_exists(session(), 'processForm'))
{
  session()->processForm();
}
