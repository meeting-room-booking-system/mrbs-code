<?php

// $Id$

// Index is just a stub to redirect to the appropriate view
// as defined in config.inc.php using the variable $default_view
// If $default_room is defined in config.inc.php then this will
// be used to redirect to a particular room.

require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

$day   = date("d");
$month = date("m");
$year  = date("Y");

switch ($default_view)
{
  case "month":
    $redirect_str = "month.php?year=$year&month=$month";
    break;
  case "week":
    $redirect_str = "week.php?year=$year&month=$month&day=$day";
    break;
  default:
    $redirect_str = "day.php?day=$day&month=$month&year=$year";
}

if ( ! empty($default_room) )
{
  $area = mrbsGetRoomArea($default_room);
  $room = $default_room;
  $redirect_str .= "&area=$area&room=$room";
}

header("Location: $redirect_str");

?>
