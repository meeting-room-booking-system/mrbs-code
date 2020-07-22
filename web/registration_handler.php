<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


function cancel($username, $event_id)
{
  $sql = "DELETE FROM " . _tbl('participants') . "
                WHERE username=:username AND entry_id=:entry_id";

  $sql_params = array(
    ':entry_id' => $event_id,
    ':username' => $username
  );

  db()->command($sql, $sql_params);
}


function register($username, $event_id)
{
  $sql = "INSERT INTO " . _tbl('participants') . " (entry_id, username, registered)
               VALUES (:entry_id, :username, :registered)";

  $sql_params = array(
    ':entry_id' => $event_id,
    ':username' => $username,
    ':registered' => time()
  );

  db()->command($sql, $sql_params);
}

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

switch ($action)
{
  case 'cancel':
    cancel($username, $event_id);
    break;
  case 'register':
    register($username, $event_id);
    break;
  default:
    trigger_error("Unknown action '$action'", E_USER_WARNING);
    break;
}


location_header($returl);
