<?php
namespace MRBS;

// Returns the full table name including schema and prefix for a given table.
// Needs to be dynamic rather than static in case we are running a multisite
// installation and want to switch sites, eg during a database upgrade.
function _tbl($short_name, $include_schema=true)
{
  global $dbsys, $db_tbl_prefix, $db_schema;

  // Do some sanity checking
  if (!isset($short_name))
  {
    throw new \Exception('$short_name not set.');
  }

  $result = $db_tbl_prefix . $short_name;

  // Prepend the schema name if set and form a qualified name for all databases
  // other than MySQL, which is one of the few that doesn't support schemas.
  // (Although in practice this means PostgreSQL at the moment, it's possible that
  // in the future support for more databases may be added)
  if ($include_schema && (utf8_strpos($dbsys, 'mysql') === false) && isset($db_schema))
  {
    $result = $db_schema . '.' . $result;
  }

  return $result;
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


// Returns the db schema version as recorded in the database.   If there is no version
// recorded then returns 0.
function db_schema_version()
{
  if (db()->table_exists(_tbl('variables')))
  {
    $result = db()->query1("SELECT variable_content
                              FROM " . _tbl('variables') . "
                             WHERE variable_name = 'db_version'");
  }
  else
  {
    // Default version is 0, before we had schema versions
    $result = 0;
  }

  return max($result, 0);
}


// Returns the local db schema version as recorded in the database.   If there is no version
// recorded then returns 0.
function db_schema_version_local()
{
  if (db()->table_exists(_tbl('variables')))
  {
    $result = db()->query1("SELECT variable_content
                              FROM " . _tbl('variables') . "
                             WHERE variable_name = 'local_db_version'");
  }
  else
  {
    // Default version is 0, before we had schema versions
    $result = 0;
  }

  return max($result, 0);
}
