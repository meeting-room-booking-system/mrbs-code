<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputSubmit;

// This file checks to see whether the database schema versions (both global and local)
// are up to date, and if not performs the necessary upgrades.

// Sanity check:  check that we can access the MRBS tables.  If we can't, it's
// either because they don't exist or we don't have permission.
if (!db()->table_exists(_tbl('entry')))
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

  if ($multisite)
  {
    // Upgrade each of the sub-sites
    $sites_dir = 'sites';
    // Use scandir() rather than the DirectoryIterator in order to ensure that
    // the sites are sorted.
    $dirs = array_diff(scandir($sites_dir), array('.', '..'));
    foreach ($dirs as $dir)
    {
      // Ignore anything that's not a directory
      if (!is_dir($sites_dir . '/' . $dir))
      {
        continue;
      }
      // And ignore any directory that doesn't have a site config file
      $site_config = $sites_dir . '/' . $dir . '/config.inc.php';
      if (!is_readable($site_config))
      {
        continue;
      }
      // Switch to the sub-site config settings and do the upgrade for the sub-site
      upgrade_echo("<h2>" . htmlspecialchars(get_vocab('upgrading_site', $dir)) . "</h2>\n");
      include 'systemdefaults.inc.php';
      include 'areadefaults.inc.php';
      include 'config.inc.php';
      include $site_config;
      $ok = $ok && do_upgrade($admin_handle);
    }
    // Then switch to the main config settings
    include 'systemdefaults.inc.php';
    include 'areadefaults.inc.php';
    include 'config.inc.php';
    // We only need this heading if we're in multi-site mode
    upgrade_echo("<h2>" . htmlspecialchars(get_vocab('upgrading_main_site')) . "</h2>\n");
  }

  // Upgrade the main site
  $ok = $ok && do_upgrade($admin_handle);

  // If we're in multisite mode then restore the config settings for the site we're supposed to be running
  if ($multisite)
  {
    include 'systemdefaults.inc.php';
    include 'areadefaults.inc.php';
    include 'config.inc.php';
    include 'site_config.inc';
  }

  if ($ok)
  {
    echo '<p>' . get_vocab('upgrade_completed') . "</p>\n";
  }

  // close the database connection that has admin rights
  unset($admin_handle);

  echo '<a href="' . htmlspecialchars(multisite('index.php')) . '">' . get_vocab('returncal') . '</a>.';

  print_footer(true);
}


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


// Upgrade between database schema versions.
// Returns FALSE on error, TRUE is successful
function upgrade_database($local, $from, $to, DB $upgrade_handle)
{
  // $local is a boolean specifying whether the upgrades are global MRBS ones ($local === false)
  // or local upgrades ($local === true);
  // $upgrade_handle is the database handle to use for the upgrade.   It will typically
  // have admin rights (eg CREATE and ALTER)
  global $dbsys;
  global $db_tbl_prefix, $db_schema;

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
      $upgrade_handle->command("UPDATE " . _tbl('variables') . " SET variable_content = ? ".
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


// Upgrades the database tables defined by the current config context
function do_upgrade($upgrade_handle)
{
  $result = true;

  if (!db()->table_exists(_tbl('entry')))
  {
    upgrade_echo("<p>" . htmlspecialchars(get_vocab(no_tables_found)) . "</p>\n");
  }
  else
  {
    $current_db_schema_version = db_schema_version();
    $current_db_schema_version_local = db_schema_version_local();

    // Do any MRBS upgrades first
    if ($result && ($current_db_schema_version < DB::DB_SCHEMA_VERSION))
    {
      $result = $result && upgrade_database(false, $current_db_schema_version, DB::DB_SCHEMA_VERSION, $upgrade_handle);
    }
    // Then any local upgrades
    if ($result && ($current_db_schema_version_local < DB::DB_SCHEMA_VERSION_LOCAL))
    {
      $result = $result && upgrade_database(true, $current_db_schema_version_local, DB::DB_SCHEMA_VERSION_LOCAL, $upgrade_handle);
    }
  }

  return $result;
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
