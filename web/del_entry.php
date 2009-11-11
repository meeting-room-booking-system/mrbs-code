<?php
// $Id$

require_once "defaultincludes.inc";

require_once "mrbs_sql.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');
$returl = get_form_var('returl', 'string');
$action = get_form_var('action', 'string');
$note = get_form_var('note', 'string');

if (!isset($note))
{
  $note = "";
}

if (empty($returl))
{
  switch ($default_view)
  {
    case "month":
      $returl = "month.php";
      break;
    case "week":
      $returl = "week.php";
      break;
    default:
      $returl = "day.php";
  }
  $returl .= "?year=$year&month=$month&day=$day&area=$area";
}

if (getAuthorised(1) && ($info = mrbsGetBookingInfo($id, FALSE, TRUE)))
{
  $user = getUserName();
  // check that the user is allowed to delete this entry
  if (isset($action) && ($action="reject"))
  {
    $authorised = auth_can_confirm($user, $info['room_id']);
  }
  else
  {
    $authorised = getWritable($info['create_by'], $user);
  }
  if ($authorised)
  {
    $day   = strftime("%d", $info["start_time"]);
    $month = strftime("%m", $info["start_time"]);
    $year  = strftime("%Y", $info["start_time"]);
    $area  = mrbsGetRoomArea($info["room_id"]);
    
    $notify_by_email = $mail_settings['admin_on_delete']  || $mail_settings['book_admin_on_provisional'];

    if ($notify_by_email)
    {
      require_once "functions_mail.inc";
      // Gather all fields values for use in emails.
      $mail_previous = getPreviousEntryData($id, $series);
    }
    sql_begin();
    $result = mrbsDelEntry(getUserName(), $id, $series, 1);
    sql_commit();
    if ($result)
    {
      // Send a mail to the Administrator
      if ($notify_by_email)
      {
        if (isset($action) && ($action == "reject"))
        {
          $result = notifyAdminOnDelete($mail_previous, $action, $note);
        }
        else
        {
          $result = notifyAdminOnDelete($mail_previous);
        }
      }
      Header("Location: $returl");
      exit();
    }
  }
}

// If you got this far then we got an access denied.
showAccessDenied($day, $month, $year, $area, "");
?>
