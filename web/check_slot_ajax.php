<?php
// $Id$

// An Ajax function to check which of an array of time slots is invalid.  (We need to do
// this server side because the client does not have sophisticated enough timezone
// handling facilities)
//
// Input parameters:
//    $id       the request id so that the client can match results to requests
//    $slots    an array of slot times in seconds from the start of the calendar day
//    $day
//    $month
//    $year
//    $tz
//
//  Returns an array of slots which are invalid

require "defaultincludes.inc";

// Check the user is authorised for this page
checkAuthorised();

// Get the non-standard form vatiables ($day, $month and $year are standard)
$id = get_form_var('id', 'string');
$slots = get_form_var('slots', 'array');
$tz = get_form_var('tz', 'string');

$result = array('id' => $id, 'slots' => array());

foreach ($slots as $s)
{
  if (is_invalid_datetime(0, 0, $s, $month, $day, $year, $tz))
  {
    $result['slots'][] = $s;
  }
}

echo json_encode($result);
?>