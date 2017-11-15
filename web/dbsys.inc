<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputSubmit;

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


$db_schema_version = 53;
$local_db_schema_version = 1;

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


// Get a database username and password
function db_get_userpass()
{
  print_header();
  
  $form = new Form();
  $form->setAttributes(array('class'  => 'form_general',
                             'id'     => 'db_logon',
                             'method' => 'post',
                             'action' => this_page()));
  
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('database_login'));
  
  // The username field
  $field = new FieldInputText();
  $field->setLabel('Database username')
        ->setControlAttributes(array('id'       => 'form_username',
                                     'name'     => 'form_username',
                                     'required' => null));               
  $fieldset->addElement($field);
  
  // The password field
  $field = new FieldInputPassword();
  $field->setLabel('Database password')
        ->setControlAttributes(array('id'   => 'form_password',
                                     'name' => 'form_password'));               
  $fieldset->addElement($field);
  
  // The submit button
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('value' => get_vocab('login')));
  $fieldset->addElement($field);
  
  $form->addElement($fieldset);
  
  $form->render();
  
  // Print footer and exit
  print_footer(TRUE);
}

// Sanity check:  check that we can access the MRBS tables.  If we can't, it's
// either because they don't exist or we don't have permission.
if (!db()->table_exists($tbl_entry))
{
  fatal_error(get_vocab('fatal_no_tables'));
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
  if (isset($auth['session']))
  {
    // We need this to get any session initialisation out of the way before we start
    // sending content.
    require_once "session/session_$auth[session].inc";
  }

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

  // Check the CSRF token before we make any changes
  Form::checkToken();
    
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
