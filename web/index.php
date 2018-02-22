<?php
namespace MRBS;

require 'defaultincludes.inc';

// Index is just a stub to redirect to the appropriate view
// as defined in config.inc.php using the variable $default_view

$vars = array('view'  => $default_view,
              'year'  => $year,
              'month' => $month,
              'day'   => $day,
              'area'  => $area,
              'room'  => $room);
              
$query = http_build_query($vars, '', '&');

header("Location: calendar.php?$query");
