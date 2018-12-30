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

populate_maxlength();


// Populate the $maxlength global.
function populate_maxlength()
{
  global $maxlength, $table_vars;
  
  foreach ($table_vars as $table => $var)
  {
    global $$var;
    
    // Check to see if the table exists, as it won't yet if it's about
    // to be created as part of an upgrade
    if (db()->table_exists($$var))
    {
      // Find the maximum length of the CHAR and VARCHAR fields (we won't
      // worry about TEXT fields)
      $field_info = db()->field_info($$var);
      
      foreach ($field_info as $field)
      {
        if (($field['nature'] == 'character') && 
            isset($field['length']) &&
            ($field['length'] < 256))
        {
          $maxlength[$table . '.' . $field['name']] = (int) $field['length'];
        }
      }
    }
  }
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
  global $tbl_variables;
  
  if (db()->table_exists($tbl_variables))
  {
    $result = db()->query1("SELECT variable_content " .
                             "FROM $tbl_variables " .
                            "WHERE variable_name = 'db_version'");
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
  global $tbl_variables;
  
  if (db()->table_exists($tbl_variables))
  {
    $result = db()->query1("SELECT variable_content " .
                             "FROM $tbl_variables " .
                            "WHERE variable_name = 'local_db_version'");
  }
  else
  {
    // Default version is 0, before we had schema versions
    $result = 0;
  }
  
  return max($result, 0);
}
