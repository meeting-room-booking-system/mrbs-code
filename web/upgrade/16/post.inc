<?php
namespace MRBS;

// Update the area table getting rid of NULLs for enable_periods and replacing them
// with the default setting.

global $tbl_area;
global $enable_periods;
global $admin_handle;

// Convert booleans to 1 or 0, as the fields are tinyints/smallints
$value = ($enable_periods) ? 1 : 0;

$admin_handle->command("UPDATE $tbl_area SET enable_periods=$value WHERE enable_periods IS NULL");
