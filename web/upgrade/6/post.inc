<?php
namespace MRBS;


global $admin_handle;
global $auth;
global $max_level;


// If the 'level' field does not exist (as it won't if it was created pre-MRBS 1.4.2),
// then create it and populate it with existing admins defined in the config file
if (!$admin_handle->field_exists(_tbl('users'), 'level'))
{
  // Default is '0' because we want to play safe and give no rights.
  // In a moment we will go through the table and add users and admins.
  $admin_handle->command("ALTER TABLE " . _tbl('users') . " ADD COLUMN level smallint DEFAULT '0' NOT NULL ".
    $admin_handle->syntax_addcolumn_after("id"));
  
  // Assuming that all existing entries in the users table are at least users, 
  // make them all Level 1
  $sql = "UPDATE " . _tbl('users') . " SET level=?";
  $admin_handle->command($sql, array(1));
  
  // Now populate the table with the existing admins
  foreach ($auth['admin'] as $admin_name)
  {
    $sql = "UPDATE " . _tbl('users') . " SET level=? WHERE name=?";
    $admin_handle->command($sql, array($max_level, $admin_name));
  }
}
