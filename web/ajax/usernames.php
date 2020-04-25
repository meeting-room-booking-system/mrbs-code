<?php
namespace MRBS;

// Returns an object containing all the usernames available for use by the Select2
// tool on the edit_entry page.

use MRBS\Form\Form;

require '../defaultincludes.inc';

// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

$result = array();

if (function_exists(__NAMESPACE__ . "\\authGetUsernames"))
{
  $result = authGetUsernames();
}

http_headers(array("Content-Type: application/json"));

echo json_encode($result);
