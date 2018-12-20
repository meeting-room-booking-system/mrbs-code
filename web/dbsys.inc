<?php
namespace MRBS;

global $dbsys, $db_tbl_prefix, $db_schema;

$tables = array('area', 'entry', 'repeat', 'room', 'sessions', 'users', 'variables', 'zoneinfo');
$table_vars = array();

foreach ($tables as $table)
{
  $var = 'tbl_' . $table;
  $$var = $db_tbl_prefix . $table;
  // Prepend the schema name if set and form a qualified name for all databases
  // other than MySQL, which is one of the few that doesn't support schemas.
  // (Although in practice this means PostgreSQL at the moment, it's possible that
  // in the future support for more databases may be added)
  if ((strpos($dbsys, 'mysql') === false) && isset($db_schema))
  {
    $$var = $db_schema . '.' . $$var;
  }
  $table_vars[$table] = $var;
}


// Convenience wrapper function to provide access to a DB object for
// default MRBS database
function db()
{
  static $db_obj = null;

  if (is_null($db_obj))
  {
    global $db_persist, $db_host, $db_login, $db_password,
           $db_database, $db_port, $dbsys;

    $db_obj = DBFactory::create($dbsys, $db_host, $db_login, $db_password,
                                $db_database, $db_persist, $db_port);
  }
  return $db_obj;
}

