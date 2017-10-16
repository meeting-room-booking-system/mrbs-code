<?php
namespace MRBS;

// Populate the new periods column in the area table with the default values taken
// from the config file.

global $tbl_area;
global $periods;
global $admin_handle;

$sql = "UPDATE $tbl_area SET periods = ?";
    
$admin_handle->command($sql, array(json_encode($periods)));
