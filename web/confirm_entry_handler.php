<?php
// $Id$

// Handles actions on provisional bookings

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_mail.inc";

// Get non-standard form variables
$action = get_form_var('action', 'string');
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');
$returl = get_form_var('returl', 'string');
$room_id = get_form_var('room_id', 'int');
$note = get_form_var('note', 'string');


// Check the user is authorised for this page
checkAuthorised();
$user = getUserName();

                  
if (isset($action))
{                     
  if ($need_to_send_mail)
  { 
    $is_new_entry = TRUE;  // Treat it as a new entry unless told otherwise    
  }
  
  // If we have to accept or reject a booking, check that we have rights to do so
  // for this room
  if ((($action == "accept") || ($action == "reject")) 
       && !auth_book_admin($user, $room_id))
  {
    showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
    exit;
  }
  
  switch ($action)
  {
    // ACTION = "ACCEPT"
    case 'accept':
      if ($need_to_send_mail)
      {
        $is_new_entry = FALSE;
        // Get the current booking data, before we change anything, for use in emails
        $mail_previous = getPreviousEntryData($id, $series);
      }
      $result = mrbsConfirmEntry($id, $series);
      if (!$result)
      {
        $returl .= "&error=accept_failed";
      }
      break;
    
      
    // ACTION = "MORE_INFO"  
    case 'more_info':
      // update the last reminded time (the ball is back in the 
      // originator's court, so the clock gets reset)
      mrbsUpdateLastReminded($id, $series);
      // update the more info fields
      mrbsUpdateMoreInfo($id, $series, $user, $note);
      $result = TRUE;  // We'll assume success and end an email anyway
      break;
    
      
    // ACTION = "REMIND"
    case 'remind':
      // update the last reminded time
      mrbsUpdateLastReminded($id, $series);
      $result = TRUE;  // We'll assume success and end an email anyway
      break;
      
    default:
      $result = FALSE;  // should not get here
      break;
      
  }  // switch ($action)
  
  
  
  // Now send an email if required and the operation was successful
  if ($result && $need_to_send_mail)
  {
    // Retrieve the booking details which we will need for the email
    // (notifyAdminOnBooking relies on them being available as globals)

    $row = mrbsGetBookingInfo($id, $series);
    
    $name          = $row['name'];
    $description   = $row['description'];
    $create_by     = $row['create_by'];
    $type          = $row['type'];
    $status        = $row['status'];
    $starttime     = $row['start_time'];
    $endtime       = $row['end_time'];
    $room_name     = $row['room_name'];
    $room_id       = $row['room_id'];
    $area_name     = $row['area_name'];
    $duration      = ($row['end_time'] - $row['start_time']) - cross_dst($row['start_time'], $row['end_time']);
    $rep_type      = $row['rep_type'];
    $repeat_id     = isset($row['repeat_id'])     ? $row['repeat_id']     : NULL;
    $rep_enddate   = isset($row['rep_enddate'])   ? $row['rep_enddate']   : NULL;
    $rep_opt       = isset($row['rep_opt'])       ? $row['rep_opt']       : NULL;
    $rep_num_weeks = isset($row['rep_num_weeks']) ? $row['rep_num_weeks'] : NULL;
    
    if ($enable_periods)
    {
      list($start_period, $start_date) =  period_date_string($row['start_time']);
    }
    else
    {
      $start_date = time_date_string($row['start_time']);
    }

    if ($enable_periods)
    {
      list( , $end_date) =  period_date_string($row['end_time'], -1);
    }
    else
    {
      $end_date = time_date_string($row['end_time']);
    }
  
    // The optional last parameters below are set to FALSE because we don't want the units
    // translated - otherwise they will end up getting translated twice, resulting
    // in an undefined index error.
    $enable_periods ? toPeriodString($start_period, $duration, $dur_units, FALSE) : toTimeString($duration, $dur_units, FALSE);

    $result = notifyAdminOnBooking($is_new_entry, $id, $series, $action);
  }
}

// Now it's all done go back to the previous view
header("Location: $returl");
exit;

?>
