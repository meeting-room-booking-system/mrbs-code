<?php

// $Id$

// Update the area table getting rid of NULLs for enable_periods and replacing them
// with the default setting.

global $tbl_area;
global $enable_periods;

// Convert booleans to 1 or 0, as the fields are tinyints/smallints
$value = ($enable_periods) ? 1 : 0;

if (sql_command("UPDATE $tbl_area SET enable_periods=$value WHERE enable_periods IS NULL") < 0)
{
  fatal_error(0, "Error updating table. " . sql_error());
}

?>
