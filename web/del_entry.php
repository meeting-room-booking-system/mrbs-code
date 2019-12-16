<?php
namespace MRBS;

use MRBS\Form\Form;


// Deletes an entry, or a series.    The $id is always the id of
// an individual entry.   If $series is set then the entire series
// of which $id is a member should be deleted. [Note - this use of
// $series is inconsistent with use in the rest of MRBS where it
// means that $id is the id of an entry in the repeat table.   This
// should be fixed sometime.]

require "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_mail.inc";

// Get non-standard form variables
$id = get_form_var('id', 'int', null, INPUT_POST);
$series = get_form_var('series', 'int', null, INPUT_POST);
$returl = get_form_var('returl', 'string', null, INPUT_POST);
$action = get_form_var('action', 'string', null, INPUT_POST);
$note = get_form_var('note', 'string', '', INPUT_POST);

// Check the CSRF token
Form::checkToken();

// Check the user is authorised for this page
checkAuthorised(this_page());

if (empty($returl))
{
  $vars = array('view'  => $default_view,
                'year'  => $year,
                'month' => $month,
                'day'   => $day,
                'area'  => $area,
                'room'  => $room);

  $returl .= 'index.php?' . http_build_query($vars, '', '&');
}

if ($info = get_booking_info($id, FALSE, TRUE))
{
  // check that the user is allowed to delete this entry
  if (isset($action) && ($action == "reject"))
  {
    $authorised = is_book_admin($info['room_id']);
  }
  else
  {
    $authorised = getWritable($info['create_by'], $info['room_id']);
  }
  if ($authorised)
  {
    $day   = strftime("%d", $info["start_time"]);
    $month = strftime("%m", $info["start_time"]);
    $year  = strftime("%Y", $info["start_time"]);
    $area  = mrbsGetRoomArea($info["room_id"]);
    // Get the settings for this area (they will be needed for policy checking)
    get_area_settings($area);

    $notify_by_email = $mail_settings['on_delete'] && need_to_send_mail();

    if ($notify_by_email)
    {
      // Gather all fields values for use in emails.
      $mail_previous = get_booking_info($id, FALSE);
      // If this is an individual entry of a series then force the entry_type
      // to be a changed entry, so that when we create the iCalendar object we know that
      // we only want to delete the individual entry
      if (!$series && ($mail_previous['rep_type'] != REP_NONE))
      {
        $mail_previous['entry_type'] = ENTRY_RPT_CHANGED;
      }
    }

    $start_times = mrbsDelEntry($id, $series, 1);

    // [At the moment MRBS does not inform the user if it was not able to delete
    // an entry, or, for a series, some entries in a series.  This could happen for
    // example if a booking policy is in force that prevents the deletion of entries
    // in the past.   It would be better to inform the user that the operation has
    // been unsuccessful or only partially successful]
    if (($start_times !== FALSE) && (count($start_times) > 0))
    {
      // Send a mail to the Administrator
      if ($notify_by_email)
      {
        // Now that we've finished with mrbsDelEntry, change the id so that it's
        // the repeat_id if we're looking at a series.   (This is a complete hack,
        // but brings us back into line with the rest of MRBS until the anomaly
        // of del_entry is fixed)
        if ($series)
        {
          $mail_previous['id'] = $mail_previous['repeat_id'];
        }
        if (isset($action) && ($action == "reject"))
        {
          $result = notifyAdminOnDelete($mail_previous, $series, $start_times, $action, $note);
        }
        else
        {
          $result = notifyAdminOnDelete($mail_previous, $series, $start_times);
        }
      }

    }
    header("Location: $returl");
    exit();
  }
}

// If you got this far then we got an access denied.
showAccessDenied($view, $view_all, $year, $month, $day, $area);

