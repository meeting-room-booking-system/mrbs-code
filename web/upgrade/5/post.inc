<?php

// $Id$

global $admin_handle;
global $tbl_users;

$nusers = sql_query1("select count(*) from $tbl_users", $admin_handle);

if ($nusers == -1)   /* If the table does not exist */
{         /* Then create it */
  $cmd = "
CREATE TABLE $tbl_users
(
  /* The first four fields are required. Don't remove. */
  id        ".sql_syntax_createtable_autoincrementcolumn().",
  level     smallint DEFAULT '0' NOT NULL,  /* play safe and give no rights */
  name      varchar(30),
  password  varchar(40),
  email     varchar(75),

  /* The following fields are application-specific. However only int and varchar are editable. */


  PRIMARY KEY (id)
);";
  $r = sql_command($cmd, $admin_handle);
  if ($r == -1)
  {
    // No need to localize this: Only the admin running this for the first time would see it.
    print "<p class=\"error\">Error creating the $tbl_users table.</p>\n";
    print "<p class=\"error\">" . sql_error($admin_handle) . "</p>\n";
    exit();
  }
}

?>
