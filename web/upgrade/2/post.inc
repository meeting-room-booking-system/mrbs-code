<?php
namespace MRBS;

// Populate the new columns in the area table with the default values taken
// from the config file.

global $resolution, $default_duration;
global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
global $admin_handle;

$sql = "UPDATE " . _tbl('area') . " SET
        resolution = $resolution,
        default_duration = $default_duration,
        morningstarts = $morningstarts,
        morningstarts_minutes = $morningstarts_minutes,
        eveningends = $eveningends,
        eveningends_minutes = $eveningends_minutes";
    
$admin_handle->command($sql);
