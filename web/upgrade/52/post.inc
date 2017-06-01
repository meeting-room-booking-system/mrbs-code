<?php
// -*- mode: php; -*-
namespace MRBS;

global $dbsys, $tbl_entry, $tbl_repeat, $tbl_users, $admin_handle;

// Special case for PostgreSQL as attempting to create a function
// with the pgsql.sql file doesn't work due to the way the file
// is split by semi-colons.
if ($dbsys == "pgsql")
{
  // Add function to update timestamp column
  $sql = <<<END_OF_SQL
CREATE OR REPLACE FUNCTION update_timestamp_column()	
RETURNS TRIGGER AS \$\$
BEGIN
  NEW.timestamp = NOW();
  RETURN NEW;	
END;
\$\$ language 'plpgsql';
END_OF_SQL;
  $admin_handle->command($sql);

  // Add triggers for tables with timestamp columns
  $admin_handle->command("CREATE TRIGGER update_${tbl_entry}_timestamp BEFORE UPDATE ON ${tbl_entry} FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column()");
  $admin_handle->command("CREATE TRIGGER update_${tbl_repeat}_timestamp BEFORE UPDATE ON ${tbl_repeat} FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column()");
  $admin_handle->command("CREATE TRIGGER update_${tbl_users}_timestamp BEFORE UPDATE ON ${tbl_users} FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column()");
}
