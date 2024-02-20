<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";

use MRBS\Form\Form;
use UnexpectedValueException;


// Cancel a user's registration
function cancel_registration(int $registration_id) : void
{
  $registration = get_registration_by_id($registration_id);

  if (!isset($registration))
  {
    return;
  }

  $entry = get_entry_by_id($registration['entry_id']);

  // Check that the user is authorised for this operation
  if (!isset($entry) ||
      (!getWritable($registration['username'], $entry['room_id']) &&
       !getWritable($registration['create_by'], $entry['room_id'])))
  {
    return;
  }

  // Check that it is not too late to cancel a registration
  if (!is_book_admin($entry['room_id']) && entry_registration_cancellation_has_closed($entry))
  {
    return;
  }

  // They are authorised, so go ahead and delete the registration
  $sql = "DELETE FROM " . _tbl('participant') . "
                WHERE id=:registration_id";

  $sql_params = array(
      ':registration_id' => $registration_id
    );

  db()->command($sql, $sql_params);
}


// Register a user for an event
function register_user(string $username, int $event_id) : void
{
  $entry = get_entry_by_id($event_id);

  // Check that the user is authorised for this operation
  if (!isset($entry) || !(can_register_others($entry['room_id']) || getWritable($username, $entry['room_id'])))
  {
    return;
  }

  // Check that the user is an admin or else that the entry is open for registration
  if (!is_book_admin($entry['room_id']) && !entry_registration_is_open($entry))
  {
    return;
  }

  // Obtain a lock to make sure no one else registers after we've checked that there
  // are spare places
  db()->mutex_lock(_tbl('participant'));

  $data = get_booking_info($event_id, false);

  // Check that registration is allowed ...
  if (!empty($data['allow_registration']))
  {
    // ... and that there are spare places
    $n_registered = count($data['registrants']);
    if (empty($data['registrant_limit_enabled']) ||
      ($data['registrant_limit'] > $n_registered))
    {
      // ... and that the user hasn't already been registered
      if (!in_arrayi($username, array_column($data['registrants'], 'username')))
      {
        $mrbs_user = session()->getCurrentUser();
        $mrbs_username = (isset($mrbs_user)) ? $mrbs_user->username : null;
        // then register the user
        add_registrant($event_id, array(
          'username'    => $username,
          'create_by'   => $mrbs_username,
          'registered'  => time()
        ));
      }
    }
  }

  // Release the lock
  db()->mutex_unlock(_tbl('participant'));
}

// Check the CSRF token.
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

// Get the form vars
$action = get_form_var('action', 'string');
$event_id = get_form_var('event_id', 'int');
$returl = get_form_var('returl', 'string');

// Take the appropriate action.  The individual functions check that the user
// is authorised to take the action.
try
{
  switch ($action)
  {
    case 'cancel':
      $registration_id = get_form_var('registration_id', 'int');
      if (!isset($registration_id))
      {
        throw new UnexpectedValueException("No registration_id received from form.");
      }
      cancel_registration($registration_id);
      break;
    case 'register':
      $username = get_form_var('username', 'string');
      if (!isset($username) || ($username === ''))
      {
        throw new UnexpectedValueException("No username received from form.");
      }
      elseif (!isset($event_id))
      {
        throw new UnexpectedValueException("No event_id received from form.");
      }
      register_user($username, $event_id);
      break;
    default:
      throw new UnexpectedValueException("Unknown action '$action'");
      break;
  }
}
catch (UnexpectedValueException $e)
{
  trigger_error($e->getMessage(), E_USER_WARNING);
}

location_header($returl);
