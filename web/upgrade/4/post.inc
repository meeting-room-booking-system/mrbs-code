<?php
namespace MRBS;

// Populate the new columns in the area table with the default values taken
// from the config file.

global $tbl_area;
global $private_enabled, $private_default, $private_mandatory, $private_override;
global $admin_handle;

$private_enabled = ($private_enabled) ? 1 : 0;
$private_default = ($private_default) ? 1 : 0;
$private_mandatory = ($private_mandatory) ? 1 : 0;

$sql = "UPDATE $tbl_area SET
        private_enabled = $private_enabled,
        private_default = $private_default,
        private_mandatory = $private_mandatory,
        private_override = '$private_override'";
    
$admin_handle->command($sql);
