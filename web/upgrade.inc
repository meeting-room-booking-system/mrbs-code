<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputSubmit;

// This file checks to see whether the database schema versions (both global and local)
// are up to date, and if not perfgorms the necessary upgrades.

// Sanity check:  check that we can access the MRBS tables.  If we can't, it's
// either because they don't exist or we don't have permission.
if (!db()->table_exists($tbl_entry))
{
  fatal_error(get_vocab('fatal_no_tables'));
}

$current_db_schema_version = db_schema_version();
$current_db_schema_version_local = db_schema_version_local();

// If either of the database schema version numbers are out of date, then 
// upgrade the database - provided of course that the entry table exists.
if (($current_db_schema_version < DB::DB_SCHEMA_VERSION) || 
    ($current_db_schema_version_local < DB::DB_SCHEMA_VERSION_LOCAL))
{
  // Upgrade needed

  // Just use a simple header as the normal header may (a) use features
  // which are not available until after the database upgrade or (b) use
  // functions which are not available until after dbsys has run.
  print_simple_header();
 
  echo '<h1>' . get_vocab('mrbs') . "</h1>\n";
  echo '<p><strong>' . get_vocab('upgrade_required') . "</strong></p>\n"; 

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
      echo '<p>' . get_vocab('supply_userpass') . "</p>\n";
      echo '<p>' . get_vocab('contact_admin', $mrbs_admin) . "</p>\n"; 
      db_get_userpass();
    }
    else
    {
      $admin_handle = DBFactory::create($dbsys, $db_host, $db_admin_username, $db_admin_password, $db_database, 0, $db_port);
    }
  }

  // Check the CSRF token before we make any changes
  Form::checkToken();
    
  $ok = true;

  // Do any MRBS upgrades first
  if ($current_db_schema_version < DB::DB_SCHEMA_VERSION)
  {
    $ok = upgrade_database(false, $current_db_schema_version, DB::DB_SCHEMA_VERSION, $admin_handle);
  }
  // Then any local upgrades
  if ($ok && $current_db_schema_version_local < DB::DB_SCHEMA_VERSION_LOCAL)
  {
    $ok = upgrade_database(true, $current_db_schema_version_local, DB::DB_SCHEMA_VERSION_LOCAL, $admin_handle);
  }
  
  // close the database connection that has admin rights
  unset($admin_handle);
  
  if ($ok)
  {
    echo '<p>' . get_vocab('upgrade_completed') . "</p>\n";
  }
  echo "<a href=\"./\">" . get_vocab('returncal') . '</a>.';
  
  print_footer(true);
}





// Upgrade between database schema versions.
// Returns FALSE on error, TRUE is successful
function upgrade_echo($message)
{
  echo $message;
  // Flush the message, so that there's some progress information
  // output to the browser even when the upgrade is taking a while
  if (ob_get_length() !== false)
  {
    ob_flush();
  }
  flush();
}


function upgrade_database($local, $from, $to, DB $upgrade_handle)
{
  // $local is a boolean specifying whether the upgrades are global MRBS ones ($local === false)
  // or local upgrades ($local === true);
  // $upgrade_handle is the database handle to use for the upgrade.   It will typically
  // have admin rights (eg CREATE and ALTER)
  global $dbsys;
  global $db_tbl_prefix, $db_schema;
  global $tbl_variables;

  $sql_type = $dbsys;
  if ($sql_type == 'mysqli')
  {
    $sql_type = 'mysql';
  }
    
  for ($ver = ($from+1); $ver <= $to; $ver++)
  {
    upgrade_echo('<p>' .
                 (($local) ? get_vocab('upgrade_to_local_version') : get_vocab('upgrade_to_version')) .
                 ": $ver");

    if ($local)
    {
      $filename = "upgrade/local/$ver/$sql_type.sql";
      $php_filename = "upgrade/local/$ver/post.inc";
    }
    else
    {
      $filename = "upgrade/$ver/$sql_type.sql";
      $php_filename = "upgrade/$ver/post.inc";
    }
    $handle = fopen($filename, 'r');
    if (!$handle)
    {
      // No need to localise, should never happen!
      upgrade_echo("Fatal error: Failed to open '$filename' for reading.\n");
      return false;
    }
    $file_size = filesize($filename);
    $sql = (!empty($file_size)) ? fread($handle, filesize($filename)) : '';
    fclose($handle);

    // PostgreSQL databases can have multiple schemas and so need a qualified
    // table name
    $prefix = $db_tbl_prefix;
    if (($sql_type != 'mysql') && isset($db_schema))
    {
      $prefix = $db_schema . '.' . $prefix;
    }
    $sql = str_replace('%DB_TBL_PREFIX%', $prefix, $sql);

    foreach (explode(";", $sql) as $query)
    {
      // Skip any empty query (so that last semi-colon doesn't run
      // an empty query)
      if (preg_match("/\S/", $query))
      {
        $res = $upgrade_handle->query($query);
      }
    }

    upgrade_echo('<br>' . get_vocab('ok'));
    if ($ver > 1)
    {
      $variable_name = ($local) ? "local_db_version" : "db_version";
      $upgrade_handle->command("UPDATE $tbl_variables SET variable_content = ? ".
                               "WHERE variable_name = ?", array($ver, $variable_name));
    }
    upgrade_echo("</p>\n");
    
    // Now execute the PHP file if there is one
    if (is_readable($php_filename))
    {
      include($php_filename);
    }
  }
  return true;
}


// Get a database username and password
function db_get_userpass()
{
  print_header();
  
  $form = new Form();
  $form->setAttributes(array('class'  => 'standard',
                             'id'     => 'db_logon',
                             'method' => 'post',
                             'action' => multisite(this_page())));
  
  $fieldset = new ElementFieldset();
  $fieldset->addLegend(get_vocab('database_login'));
  
  // The username field
  $field = new FieldInputText();
  $field->setLabel('Database username')
        ->setControlAttributes(array('id'       => 'form_username',
                                     'name'     => 'form_username',
                                     'required' => true));               
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
