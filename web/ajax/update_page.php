<?php
namespace MRBS;

// An Ajax page to update the current page in the server. Called by the client when it switches URL
// on the fly.

use MRBS\Form\Form;

require '../defaultincludes.inc';

// Check the CSRF token
Form::checkToken();

$page = get_form_var('page', 'string');

if (isset($page) && ($page !== ''))
{
  session()->updatePage($page);
}
