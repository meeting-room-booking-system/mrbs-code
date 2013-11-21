<?php

// $Id$

// Update the area table getting rid of NULLs for the private settings and replacing them
// with the default settings.

global $tbl_area;
global $private_enabled, $private_default, $private_mandatory, $private_override;

// Convert booleans to 1 or 0, as the fields are tinyints/smallints
$private_enabled = ($private_enabled) ? 1 : 0;
$private_default = ($private_default) ? 1 : 0;
$private_mandatory = ($private_mandatory) ? 1 : 0;

if (sql_command("UPDATE $tbl_area SET private_enabled=$private_enabled WHERE private_enabled IS NULL") < 0)
{
  fatal_error(0, "Error updating table. " . sql_error());
}
if (sql_command("UPDATE $tbl_area SET private_default=$private_default WHERE private_default IS NULL") < 0)
{
  fatal_error(0, "Error updating table. " . sql_error());
}
if (sql_command("UPDATE $tbl_area SET private_mandatory=$private_mandatory WHERE private_mandatory IS NULL") < 0)
{
  fatal_error(0, "Error updating table. " . sql_error());
}
if (sql_command("UPDATE $tbl_area SET private_override='$private_override' WHERE private_override IS NULL") < 0)
{
  fatal_error(0, "Error updating table. " . sql_error());
}

