<?php
namespace MRBS;


// Initialise error reporting
init_errors();

// Flush the mail queue on shutdown
register_shutdown_function(__NAMESPACE__ . "\\MailQueue::flush");

// Start up sessions
session()->init();

if (method_exists(session(), 'processForm'))
{
  session()->processForm();
}
