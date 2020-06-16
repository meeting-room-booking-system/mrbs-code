<?php
namespace MRBS;

global $admin_handle;

$exists = $admin_handle->table_exists(_tbl('users'));

/* If the table does not exist */
if (!$exists)
{
  /* Then create it */
  $cmd = "
CREATE TABLE " . _tbl('users') . "
(
  /* The first four fields are required. Don't remove. */
  id        ".$admin_handle->syntax_createtable_autoincrementcolumn().",
  level     smallint DEFAULT '0' NOT NULL,  /* play safe and give no rights */
  name      varchar(30),
  password  varchar(40),
  email     varchar(75),

  /* The following fields are application-specific. However only int and varchar are editable. */


  PRIMARY KEY (id)
);";
  $admin_handle->command($cmd);
}
