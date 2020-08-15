<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;

// If we haven't got the ability to reset passwords then get out of here
if (!auth()->canResetPassword())
{
  location_header('index.php');
}

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$username = get_form_var('username', 'string');

if (isset($username) && ($username !== ''))
{
  auth()->resetPassword($username);
}
