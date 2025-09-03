<?php
declare(strict_types=1);
namespace MRBS;

// Returns the full table name including schema and prefix for a given table.
// Needs to be dynamic rather than static in case we are running a multisite
// installation and want to switch sites, eg during a database upgrade.

use MRBS\DB\DB;
use MRBS\DB\DBFactory;
use PDO;

function _tbl(string $short_name, bool $include_schema=true) : string
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
  if ($include_schema && (mb_strpos($dbsys, 'mysql') === false) && isset($db_schema))
  {
    $result = $db_schema . '.' . $result;
  }

  return $result;
}


function get_table_short_name(string $table) : string
{
  global $db_tbl_prefix;

  // Get everything after the last '.', ie strip off any database
  // and schema names
  if (false !== ($pos = mb_strrpos($table, '.')))
  {
    $result = mb_substr($table, $pos + 1);
  }
  else
  {
    $result = $table;
  }

  // Strip the prefix off the table name
  return mb_substr($result, mb_strlen($db_tbl_prefix));
}


// Convenience wrapper function to provide access to a DB object for
// default MRBS database
function db() : DB
{
  global $db_persist, $db_host, $db_login, $db_password,
         $db_database, $db_port, $dbsys, $db_options;

  static $db_obj = null;

  try
  {
    if (is_null($db_obj))
    {
      throw new \Exception("DB object not yet created");
    }
    // Check to see if the connection is still there.  If it isn't - perhaps because it
    // has been timed out, eg by MySQL's wait_timeout - then we throw an exception which
    // will cause us to re-create a connection.
    // (Note that we cannot try a "SELECT 1" query or something similar to check if the
    // connection is still there, because if it is then the query will cause the last insert
    // id to be lost.)
    // TODO: sometimes the symptom of a lost connection is a warning of the form "Packets
    // TODO: out of order. Expected 1 received 0. Packet size=145".  Need to work out why
    // TODO: this happens and do something about it.
    if ($db_obj->getAttribute(PDO::ATTR_SERVER_INFO) == 'MySQL server has gone away')
    {
      // On most recent versions of PHP the call to getAttribute() on a lost connection will
      // throw an exception anyway, but just in case it doesn't we'll throw one. See
      // https://stackoverflow.com/questions/21595402/php-pdo-how-to-get-the-current-connection-status
      throw new \Exception('MySQL server has gone away');
    }
  }
  catch (\Exception $e)
  {
    try
    {
      $db_obj = DBFactory::create(
        $dbsys,
        $db_host,
        $db_login,
        $db_password,
        $db_database,
        (bool)$db_persist,
        $db_port,
        $db_options
      );
    }
    catch (\Exception $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      fatal_error(get_vocab('fatal_db_error'));
    }
  }

  return $db_obj;
}


// Returns the db schema version as recorded in the database.   If there is no version
// recorded then returns 0.  If $local is true then the local db schema version is returned.
function db_schema_version(DB $handle, bool $local=false) : int
{
  // The table was renamed from 'variables' to 'variable' in Upgrade 76
  if ($handle->table_exists(_tbl('variable')))
  {
    $short_name = 'variable';
  }
  elseif ($handle->table_exists(_tbl('variables')))
  {
    $short_name = 'variables';
  }
  else
  {
    // Default version is 0, before we had schema versions
    return 0;
  }

  $sql_params = [':variable_name' => ($local) ? 'local_db_version' : 'db_version'];
  $sql = "SELECT variable_content
            FROM " . _tbl($short_name) . "
           WHERE variable_name=:variable_name";
  $result = $handle->query1($sql, $sql_params);

  return max($result, 0);
}
