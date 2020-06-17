<?php
namespace MRBS;

// Make sure that any null values for the periods column are replaced with
// the config default.  (It's possible that somebody working from the latest
// default branch could have created a new area with a null value.  That has
// now been fixed, so won't happen again, but we need to clean up the area
// table just in case).

global $periods;
global $admin_handle;

$sql = "UPDATE " . _tbl('area') . " SET periods = ? WHERE periods IS NULL";
    
$admin_handle->command($sql, array(json_encode($periods)));
