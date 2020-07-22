<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

// Get the form vars
$action = get_form_var('action', 'string');
$event_id = get_form_var('event_id', 'int');
$returl = get_form_var('returl', 'string');
$username = get_form_var('username', 'string');

// Check that the user is authorised for this operation
if ((session()->getCurrentUser()->username !== $username) && !is_book_admin($room))
{
  location_header($returl);
}

$sql = "INSERT INTO " . _tbl('participants') . " (entry_id, username)
             VALUES (:entry_id, :username)";

$sql_params = array(
  ':entry_id' => $event_id,
  ':username' => $username
);

db()->command($sql, $sql_params);

location_header($returl);
