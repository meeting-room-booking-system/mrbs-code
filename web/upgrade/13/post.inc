<?php

// $Id$

// Populate the new columns in the area table with the default values taken
// from the config file.

global $tbl_area;
global $provisional_enabled, $reminders_enabled;

$provisional_enabled = ($provisional_enabled) ? 1 : 0;
$reminders_enabled = ($reminders_enabled) ? 1 : 0;

$sql = "UPDATE $tbl_area SET
        provisional_enabled=$provisional_enabled,
        reminders_enabled=$reminders_enabled";
    
$res = sql_command($sql);
if ($res == -1)
{
  echo sql_error();
  // No need to localise, should never happen
  print "<span class=\"error\">Failed to set default values for new columns in area table.</span>";
}

?>
