<?php
namespace MRBS;

// Populate the new columns in the area table with the default values taken
// from the config file.

global $max_duration_enabled, $max_duration_secs, $max_duration_periods;

$sql = "UPDATE " . _tbl('area') . "
           SET max_duration_enabled = " . ((empty($max_duration_enabled)) ? 0 : 1) . ",
               max_duration_secs = " . ((empty($max_duration_secs)) ? 0 : (int)$max_duration_secs) . ",
               max_duration_periods = " . ((empty($max_duration_periods)) ? 0 : (int)$max_duration_periods);

$upgrade_handle->command($sql);
