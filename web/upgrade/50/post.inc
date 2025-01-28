<?php
namespace MRBS;

// Populate the new periods column in the area table with the default values taken
// from the config file.

global $periods;

$sql = "UPDATE " . _tbl('area') . " SET periods = ?";

$upgrade_handle->command($sql, array(json_encode($periods)));
