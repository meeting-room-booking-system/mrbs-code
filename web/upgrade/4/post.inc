<?php

// $Id$

// Populate the new columns in the area table with the default values taken
// from the config file.

global $tbl_area;
global $private_enabled, $private_default, $private_mandatory, $private_override;

$private_enabled = ($private_enabled) ? 1 : 0;
$private_default = ($private_default) ? 1 : 0;
$private_mandatory = ($private_mandatory) ? 1 : 0;

$sql = "UPDATE $tbl_area SET
        private_enabled = $private_enabled,
        private_default = $private_default,
        private_mandatory = $private_mandatory,
        private_override = '$private_override'";
    
$res = sql_command($sql);
if ($res == -1)
{
  echo sql_error();
  // No need to localise, should never happen
  print "<span class=\"error\">Failed to set default values for new columns in area table.</span>";
}

