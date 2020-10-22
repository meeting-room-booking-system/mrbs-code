<?php
namespace MRBS;

use MRBS\Form\Form;

// Handles actions on bookings awaiting approval

require "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_mail.inc";

// Get non-standard form variables
$action = get_form_var('action', 'string');
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');
$returl = get_form_var('returl', 'string');
$note = get_form_var('note', 'string');

// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());
$mrbs_user = session()->getCurrentUser();
$mrbs_username = (isset($mrbs_user)) ? $mrbs_user->username : null;

// Retrieve the booking details
$data = get_booking_info($id, $series);
$room_id = $data['room_id'];

// Initialise $mail_previous so that we can use it as a parameter for notifyAdminOnBooking
$mail_previous = array();
$start_times = array();

// Give the return URL a query string if it doesn't already have one
if (utf8_strpos($returl, '?') === false)
{
  $returl .= "?year=$year&month=$month&day=$day&area=$area&room=$room";
}


if (isset($action))
{
  if (need_to_send_mail())
  {
    $is_new_entry = TRUE;  // Treat it as a new entry unless told otherwise
  }

  // If we have to approve or reject a booking, check that we have rights to do so
  // for this room
  if ((($action == "approve") || ($action == "reject"))
       && !is_book_admin($room_id))
  {
    showAccessDenied($view, $view_all, $year, $month, $day, $area, isset($room) ? $room : null);
    exit;
  }

  switch ($action)
  {
    // ACTION = "APPROVE"
    case 'approve':
      if (need_to_send_mail())
      {
        $is_new_entry = FALSE;
        // Get the current booking data, before we change anything, for use in emails
        $mail_previous = get_booking_info($id, $series);
      }
      $start_times = mrbsApproveEntry($id, $series);
      $result = ($start_times !== FALSE);
      if ($result === FALSE)
      {
        $returl .= "&error=approve_failed";
      }
      // Get the new data, which will have the status changed
      $data = get_booking_info($id, $series);
      break;


    // ACTION = "MORE_INFO"
    case 'more_info':
      // update the last reminded time (the ball is back in the
      // originator's court, so the clock gets reset)
      update_last_reminded($id, $series);
      // update the more info field
      update_more_info($id, $series, $mrbs_user->username, $note);
      $result = TRUE;  // We'll assume success and end an email anyway
      break;


    // ACTION = "REMIND"
    case 'remind':
      // update the last reminded time
      update_last_reminded($id, $series);
      $result = TRUE;  // We'll assume success and end an email anyway
      break;

    default:
      $result = FALSE;  // should not get here
      break;

  }  // switch ($action)



  // Now send an email if required and the operation was successful
  if ($result && need_to_send_mail())
  {
    // Get the area settings for this area (we will need to know if periods are enabled
    // so that we will know whether to include iCalendar information in the email)
    get_area_settings($data['area_id']);
    // Send the email
    notifyAdminOnBooking($data, $mail_previous, $is_new_entry, $series, $start_times, $action, $note);
  }
}

// Now it's all done go back to the previous view
location_header($returl);
