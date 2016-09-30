<?php
namespace MRBS;

// $Id$

global $dbsys, $db_tbl_prefix, $db_schema, $maxlength;

$tables = array('area', 'entry', 'repeat', 'room', 'users', 'variables', 'zoneinfo');
$table_vars = array();

foreach ($tables as $table)
{
  $var = 'tbl_' . $table;
  $$var = $db_tbl_prefix . $table;
  // Prepend the schema name if set and form a qualified name for all databases
  // other than MySQL, which is one of the few that doesn't support schemas.
  // (Although in practice this means PostgreSQL at the moment, it's possible that
  // in the future support for more databases may be added)
  if ((strpos($dbsys, 'mysql') === FALSE) && isset($db_schema))
  {
    $$var = $db_schema . '.' . $$var;
  }
  $table_vars[$table] = $var;
}


$db_schema_version = 49;
$local_db_schema_version = 1;

// Convenience wrapper functions

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


function sql_free($stmt)
{
  return $stmt->free();
}
function sql_quote($identifier)
{
  return DB::default_db()->quote($identifier);
}
function sql_command($sql, $params = null)
{
  return DB::default_db()->command($sql, $params);
}
function sql_query1($sql, $params = null)
{
  return DB::default_db()->query1($sql, $params);
}
function sql_query_array($sql, $params = null)
{
  return DB::default_db()->query_array($sql, $params);
}
function sql_query($sql, $params = null)
{
  return DB::default_db()->query($sql, $params);
}
function sql_row($stmt, $i)
{
  return $stmt->row($i);
}
function sql_row_keyed($stmt, $i)
{
  return $stmt->row_keyed($i);
}
function sql_all_rows_keyed($stmt)
{
  return $stmt->all_rows_keyed();
}
function sql_count($stmt)
{
  return $stmt->count();
}
function sql_insert_id($table, $field)
{
  return DB::default_db()->insert_id($table, $field);
}
function sql_error()
{
  return DB::default_db()->error();
}
function sql_begin()
{
  return DB::default_db()->begin();
}
function sql_commit()
{
  return DB::default_db()->commit();
}
function sql_rollback()
{
  return DB::default_db()->rollback();
}
function sql_mutex_lock($name)
{
  return DB::default_db()->mutex_lock($name);
}
function sql_mutex_unlock($name)
{
  return DB::default_db()->mutex_unlock($name);
}
function sql_version()
{
  return DB::default_db()->version();
}
function sql_syntax_limit($count, $offset)
{
  return DB::default_db()->syntax_limit($count, $offset);
}
function sql_syntax_timestamp_to_unix($fieldname)
{
  return DB::default_db()->syntax_timestamp_to_unix($fieldname);
}
function sql_syntax_casesensitive_equals($fieldname, $s, &$params)
{
  return DB::default_db()->syntax_casesensitive_equals($fieldname, $s, $params);
}
function sql_syntax_caseless_contains($fieldname, $s, &$params)
{
  return DB::default_db()->syntax_caseless_contains($fieldname, $s, $params);
}
function sql_syntax_addcolumn_after($fieldname)
{
  return DB::default_db()->syntax_addcolumn_after($fieldname);
}
function sql_syntax_createtable_autoincrementcolumn()
{
  return DB::default_db()->syntax_createtable_autoincrementcolumn();
}
function sql_syntax_bitwise_xor()
{
  return DB::default_db()->syntax_bitwise_xor();
}
function sql_num_fields($stmt)
{
  return $stmt->num_fields();
}
function sql_table_exists($table)
{
  return DB::default_db()->table_exists($table);
}
function sql_field_exists($table, $field)
{
  return DB::default_db()->field_exists($table, $field);
}
function sql_field_info($table)
{
  return DB::default_db()->field_info($table);
}


// Get a database username and password
function db_get_userpass()
{
  print_header(0, 0, 0, 0, "");
  ?>
  <form class="form_general" id="db_logon" method="post" action="<?php htmlspecialchars(this_page()); ?>">
  <fieldset>
  <legend><?php echo get_vocab("database_login") ?></legend>
    <div>
      <label for="form_username">Database username</label>
      <input id="form_username" name="form_username" type="text">
    </div>
    <div>
      <label for="form_password">Database password</label>
      <input id="form_password" name="form_password" type="password">
    </div>
    <div id="db_logon_submit">
      <input class="submit" type="submit" value=" <?php echo get_vocab('login') ?>">
    </div>
  </fieldset>
  </form>
  <?php
  // Print footer and exit
  print_footer(TRUE);
}

// Sanity check:  check that we can access the MRBS tables.  If we can't, it's
// either because they don't exist or we don't have permission.
if (!db()->table_exists($tbl_entry))
{
  fatal_error(FALSE, get_vocab('fatal_no_tables'), FALSE);
}

// Default version is 0, before we had schema versions
$current_db_schema_version = 0;
$current_local_db_schema_version = 0;

if (db()->table_exists($tbl_variables))
{
  $current_db_schema_version = db()->query1("SELECT variable_content ".
                                          "FROM $tbl_variables ".
                                          "WHERE variable_name = 'db_version'");
  $current_local_db_schema_version = db()->query1("SELECT variable_content ".
                                                "FROM $tbl_variables ".
                                                "WHERE variable_name = 'local_db_version'");                                            
  if ($current_local_db_schema_version < 0)
  {
    $current_local_db_schema_version = 0;
  }
}

// If either of the database schema version numbers are out of date, then 
// upgrade the database - provided of course that the entry table exists.
if (($current_db_schema_version < $db_schema_version) || 
    ($current_local_db_schema_version < $local_db_schema_version))
{
  // Upgrade needed

  require_once "functions.inc";
  require_once "upgrade.inc";

  // Just use a simple header as the normal header may (a) use features
  // which are not available until after the database upgrade or (b) use
  // functions which are not available until after dbsys has run.
  print_simple_header();
 
  echo "<h1>" . get_vocab("mrbs") . "</h1>\n";
  echo "<p class=\"error\">" . get_vocab("upgrade_required") . "</p>\n"; 

  $admin_handle = null;

  // We need to open a connection to the database with a database
  // username that has admin rights.
  while (empty($admin_handle))
  {
    $db_admin_username = get_form_var('form_username', 'string');
    $db_admin_password = get_form_var('form_password', 'string');
    if (!isset($db_admin_username) || !isset($db_admin_password))
    {
      // Get a username and password if we haven't got them
      echo "<p>" . get_vocab("supply_userpass") . "</p>\n";
      echo "<p>" . get_vocab("contact_admin") . "</p>\n"; 
      db_get_userpass();
    }
    else
    {
      $admin_handle = DBFactory::create($dbsys, $db_host, $db_admin_username, $db_admin_password, $db_database, 0, $db_port);
    }
  }

  $ok = TRUE;

  // Do any MRBS upgrades first
  if ($current_db_schema_version < $db_schema_version)
  {
    $ok = upgrade_database(FALSE, $current_db_schema_version, $db_schema_version, $admin_handle);
  }
  // Then any local upgrades
  if ($ok && $current_local_db_schema_version < $local_db_schema_version)
  {
    $ok = upgrade_database(TRUE, $current_local_db_schema_version, $local_db_schema_version, $admin_handle);
  }
  
  // close the database connection that has admin rights
  unset($admin_handle);

  if ($ok)
  {
    echo "<p>" . get_vocab("upgrade_completed") . "</p>\n";
  }
  echo "<a href=\"./\">" . get_vocab("returncal") . "</a>.";

  print_footer(TRUE);
}


// Populate the $maxlength global.   (We need to do this after any upgrade in case the
// upgrade as altered the table structures).
foreach ($table_vars as $table => $var)
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
