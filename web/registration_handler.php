<?php
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;


// Cancel a user's registration
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


// Register a user for an event
function register($username, $event_id)
{
  // Obtain a lock to make sure no one else registers after we've checked that there
  // are spare places
  db()->mutex_lock(_tbl('participants'));

  $data = get_booking_info($event_id, false);

  // Check that registration is allowed ...
  if (!empty($data['allow_registration']))
  {
    // ... and that there are spare places
    $n_registered = count($data['registrants']);
    if (empty($data['enable_registrant_limit']) ||
      ($data['registrant_limit'] > $n_registered))
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
  }

  // Release the lock
  db()->mutex_unlock(_tbl('participants'));
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
