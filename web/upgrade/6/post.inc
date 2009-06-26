<?php

// $Id$

global $admin_handle;
global $tbl_users;
global $auth;

$r = sql_query("SELECT level FROM $tbl_users ".
               sql_mysql_syntax_limit(0,1),$admin_handle);

// If the 'level' field does not exist (as it won't if it was created pre-MRBS 1.4.2),
// then create it and populate it with existing admins defined in the config file
if ($r == 0)
{
  // Default is '1' because we will assume all existing entries in the database are ordinary
  // users.   In a moment we will go through the admins and upgrade them.
  $r = sql_command("ALTER TABLE $tbl_users ADD COLUMN level smallint DEFAULT '1' NOT NULL ".sql_syntax_addcolumn_after("id"), $admin_handle);
  if ($r == -1)
  {
    // No need to localize this: Only the admin running this for the first time would see it.
    print "<p class=\"error\">Error adding the 'level' column.</p>\n";
    print "<p class=\"error\">" . sql_error($admin_handle) . "</p>\n";
    exit();
  }
  
  // Now populate the table with the existing admins
  foreach ($auth['admin'] as $admin_name)
  {
    $sql = "UPDATE $tbl_users SET level=2 WHERE name='".addslashes($admin_name)."'";
    if (sql_command($sql) < 0)
    {
      fatal_error(0, "Error adding existing admins. " . sql_error());
    }
  }
}
else
{
  sql_free($r, $admin_handle);
}

?>
