<?php

// $Id$

// Populate the new columns in the area table with the default values taken
// from the config file.

global $tbl_area;
global $max_duration_enabled, $max_duration_secs, $max_duration_periods;

$sql = "UPDATE $tbl_area
           SET max_duration_enabled = " . ((empty($max_duration_enabled)) ? 0 : 1) . ",
               max_duration_secs = " . ((empty($max_duration_secs)) ? 0 : (int)$max_duration_secs) . ",
               max_duration_periods = " . ((empty($max_duration_periods)) ? 0 : (int)$max_duration_periods);
    
$res = sql_command($sql);
if ($res == -1)
{
  echo sql_error();
  // No need to localise, should never happen
  print "<span class=\"error\">Failed to set default values for new columns in area table.</span>";
}

