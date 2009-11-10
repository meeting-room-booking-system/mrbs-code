<?php
// $Id$

// Handles actions on provisional bookings

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_mail.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$action = get_form_var('action', 'string');
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');
$returl = get_form_var('returl', 'string');
$room_id = get_form_var('room_id', 'int');

// If we dont know the right date then make it up 
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

if (empty($area))
{
  $area = get_default_area();
}

// Check that we're allowed to use this page
// (1) We must be at least a logged in user
if(!getAuthorised(1))
{
  showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
  exit;
}
$user = getUserName();
// (2) We must also have confirm rights for this room
if (!auth_can_confirm($user, $room_id))
{
  showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
  exit;
}

// ACTION = "ACCEPT"
if ($action == "accept")
{
  mrbsConfirmEntry($id, $series);
}

// Now it's all done go back to the previous view
header("Location: $returl");
exit;

?>
