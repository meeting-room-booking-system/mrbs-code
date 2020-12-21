<?php
namespace MRBS;

// Rename the enable_registrant_limit column for consistency.
// Unfortunately due to a mess up in the default branch (upgrade 67 was
// inadvertently changed during a global replace after 1.9.2 was released),
// some sites that took the default branch after the 1.9.2 release will already
// have had their columns renamed, so we have to do this as a PHP post.inc
// operation, rather than doing it in mysql.sql or pgsql.sql.

global $dbsys;
global $admin_handle;

if ($admin_handle->field_exists(_tbl('entry'), 'enable_registrant_limit'))
{
  $sql = "ALTER TABLE " . _tbl('entry') . " ";
  if ($dbsys == 'pgsql')
  {
    $sql .= "RENAME COLUMN enable_registrant_limit TO registrant_limit_enabled";
  }
  else
  {
    $sql .= "CHANGE enable_registrant_limit registrant_limit_enabled tinyint(1) DEFAULT 1 NOT NULL";
  }

  $admin_handle->command($sql);
}
elseif (!$admin_handle->field_exists(_tbl('entry'), 'registrant_limit_enabled'))
{
  $message = "Something is wrong with the table structure.  Neither the column " .
             "'enable_registrant_limit' nor the column 'registrant_limit_enabled' exist. " .
             "Please raise an MRBS Sourceforge Support Request ticket.";
  throw new \Exception($message);
}

