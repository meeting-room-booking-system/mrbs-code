<?php
namespace MRBS;

// Populate the new columns in the area table with the default values taken
// from the config file.

global $times_along_top, $default_type;
global $admin_handle;

$times_along_top = ($times_along_top) ? 1 : 0;

$sql = "UPDATE " . _tbl('area') . "
           SET times_along_top = :times_along_top,
               default_type = :default_type";

$sql_params = array(
    ':times_along_top' => $times_along_top,
    ':default_type' => $default_type
  );
  
$admin_handle->command($sql, $sql_params);
