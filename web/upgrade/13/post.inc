<?php
namespace MRBS;

// Populate the new columns in the area table with the default values taken
// from the config file.

global $admin_handle;
global $tbl_area;
global $provisional_enabled, $reminders_enabled;

$provisional_enabled = ($provisional_enabled) ? 1 : 0;
$reminders_enabled = ($reminders_enabled) ? 1 : 0;

$sql = "UPDATE $tbl_area SET
        provisional_enabled=$provisional_enabled,
        reminders_enabled=$reminders_enabled";
    
$admin_handle->command($sql);
