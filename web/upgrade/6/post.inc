<?php
namespace MRBS;

// $Id$


global $admin_handle;
global $tbl_users;
global $auth;
global $max_level;


// If the 'level' field does not exist (as it won't if it was created pre-MRBS 1.4.2),
// then create it and populate it with existing admins defined in the config file
if (sql_field_exists($tbl_users, 'level'))
{
  // Default is '0' because we want to play safe and give no rights.
  // In a moment we will go through the table and add users and admins.
  sql_command("ALTER TABLE $tbl_users ADD COLUMN level smallint DEFAULT '0' NOT NULL ".sql_syntax_addcolumn_after("id"),
              array(), $admin_handle);
  
  // Assuming that all existing entries in the users table are at least users, 
  // make them all Level 1
  $sql = "UPDATE $tbl_users SET level=?";
  sql_command($sql, array(1));
  
  // Now populate the table with the existing admins
  foreach ($auth['admin'] as $admin_name)
  {
    $sql = "UPDATE $tbl_users SET level=? WHERE name=?";
    sql_command($sql, array($max_level, $admin_name));
  }
}
