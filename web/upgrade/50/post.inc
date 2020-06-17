<?php
namespace MRBS;

// Populate the new periods column in the area table with the default values taken
// from the config file.

global $periods;
global $admin_handle;

$sql = "UPDATE " . _tbl('area') . " SET periods = ?";
    
$admin_handle->command($sql, array(json_encode($periods)));
