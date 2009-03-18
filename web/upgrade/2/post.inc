<?php

// $Id$

// Populate the new columns in the area table with the default values taken
// from the config file.

global $tbl_area;
global $resolution, $default_duration;
global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;

$sql = "UPDATE $tbl_area SET
        resolution = $resolution,
        default_duration = $default_duration,
        morningstarts = $morningstarts,
        morningstarts_minutes = $morningstarts_minutes,
        eveningends = $eveningends,
        eveningends_minutes = $eveningends_minutes";
    
$res = sql_command($sql);
if ($res == -1)
{
  // No need to localise, should never happen
  print "<span class=\"error\">Failed to set default values for new columns in area table.</span>";
}

?>