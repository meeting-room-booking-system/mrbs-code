<?php

# $Id$

#Index is just a stub to redirect to the appropriate day view

$day   = date("d");
$month = date("m");
$year  = date("Y");

header("Location: day.php?day=$day&month=$month&year=$year");

?>
