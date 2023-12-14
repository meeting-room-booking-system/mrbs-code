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
$returl = get_form_var('returl', 'string', 'admin.php');
$save_button = get_form_var('save_button', 'string');

if (!empty($save_button))
{
  $message = Message::getInstance($message_text, $message_until);
  $message->save();
}

location_header($returl);
