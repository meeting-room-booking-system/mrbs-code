<?php

# $Id$

#Index is just a stub to redirect to the appropriate day view
require_once "grab_globals.inc.php";
include("config.inc.php");

$day   = date("d");
$month = date("m");
$year  = date("Y");

switch ($default_view)
{
	case "month":
		header("Location: month.php?year=$year&month=$month");
		break;
	case "week":
		header("Location: week.php?year=$year&month=$month&day=$day");
		break;
	default:
		header("Location: day.php?day=$day&month=$month&year=$year");
}
?>
