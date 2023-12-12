<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\Form;

require 'defaultincludes.inc';

// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$message_text = get_form_var('message_text', 'string', '');
$message_until = get_form_var('message_until', 'string', '');

if (isset($message_until) && ($message_until !== ''))
{
  // Set the date to the beginning of the next day and save it with a time.
  // This allows a time to be added to the form in the future.
  // Note that the time is without timezone, so will be the time in the
  // timezone of the area that the message will be displayed in.
  $input_format = 'Y-m-d';
  $date = DateTime::createFromFormat($input_format, $message_until);
  if ($date === false)
  {
    trigger_error("Could not create date from '$message_until'; expecting format '$input_format'.", E_USER_WARNING);
    $message_until = '';
  }
  else
  {
    $date->setTime(0, 0)->modify('+1 day');
    $message_until = $date->format('Y-m-d\TH:i:s');
  }
}

$message = array(
  'text' => $message_text,
  'until' => $message_until
);

message_save($message);
