<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Form\ElementFieldset;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\Form;

// This file checks to see whether the database schema versions (both global and local)
// are up-to-date, and if not performs the necessary upgrades.


// Get all the sub-site configuration files in this installation.  Returns an array of site
// names indexed by configuration file.  (We do it this way round in case the site names are
// integer-like, eg "1", "2", "3", etc., which will cause difficulties with PHP associative arrays>)
function get_site_config_files() : array
{
  global $multisite;

  $result = [];

  if ($multisite)
  {
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
      $result[$site_config] = $dir;
    }
  }

  return $result;
}


// Get the database config settings for a given $site_config.
// A NULL argument gets the main site config settings.
// Returns an array of settings.
function get_db_config(?string $site_config=null) : array
{
  $result = [];
  $vars = ['dbsys', 'db_host', 'db_database', 'db_port', 'db_schema', 'db_tbl_prefix', 'db_options'];

  // Include the site's config files.  No need to include areadefaults.inc.php as it doesn't contain DB settings.
  include 'systemdefaults.inc.php';
  include 'config.inc.php';
  if (isset($site_config))
  {
    include $site_config;
  }

  foreach ($vars as $var)
  {
    $result[$var] = $$var ?? null;
  }

  return $result;
}


// Upgrade between database schema versions.
// Returns FALSE on error, TRUE is successful
function upgrade_tables(bool $local, int $from, int $to, DB $upgrade_handle) : bool
{
  // $local is a boolean specifying whether the upgrades are global MRBS ones ($local === false)
  // or local upgrades ($local === true);
  // $upgrade_handle is the database handle to use for the upgrade.   It will typically
  // have admin rights (eg CREATE and ALTER)
  global $dbsys;

  $sql_type = $dbsys;
  if ($sql_type == 'mysqli')
  {
    $sql_type = 'mysql';
  }

  for ($ver = ($from+1); $ver <= $to; $ver++)
  {
    echo "<p>\n";
    $tag = ($local) ? get_vocab('upgrade_to_local_version') : get_vocab('upgrade_to_version');
    echo get_vocab($tag) . ": $ver<br>\n";

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
      echo "Fatal error: Failed to open '$filename' for reading.\n";
      return false;
    }
    $file_size = filesize($filename);
    $sql = (!empty($file_size)) ? fread($handle, filesize($filename)) : '';
    fclose($handle);

    // PostgreSQL databases can have multiple schemas and so need a qualified
    // table name when referring to the table name.  However the table prefix is also
    // used to make, for example, constraint, index and trigger names unique.  These
    // are unique within the schema and do not need to be schema-qualified.  Also, when
    // the tables were created from tables.pg.sql they would just have had "mrbs_"
    // replaced by the unqualified table prefix.
    $sql = str_replace('%DB_TBL_PREFIX%', _tbl('', true), $sql);
    $sql = str_replace('%DB_TBL_PREFIX_SHORT%', _tbl('', false), $sql);

    foreach (explode(";", $sql) as $query)
    {
      // Skip any empty query (so that last semicolon doesn't run
      // an empty query)
      if (preg_match("/\S/", $query))
      {
        $res = $upgrade_handle->query($query);
      }
    }

    if ($ver > 1)
    {
      // The table was renamed in Upgrade 83
      $short_name = ($ver >= 83) ? 'variable' : 'variables';
      $variable_name = ($local) ? "local_db_version" : "db_version";
      $sql = "UPDATE " . _tbl($short_name) . "
                 SET variable_content = ?
               WHERE variable_name = ?";
      $upgrade_handle->command($sql, array($ver, $variable_name));
    }

    // Now execute the PHP file if there is one
    if (is_readable($php_filename))
    {
      include($php_filename);
    }

    echo get_vocab('ok');
    echo "</p>\n";
  }
  return true;
}


// Upgrades the database tables defined by the current config context
function do_upgrade(DB $upgrade_handle) : bool
{
  $result = true;

  if (!$upgrade_handle->table_exists(_tbl('entry')))
  {
    echo "<p>" . htmlspecialchars(get_vocab('no_tables_found')) . "</p>\n";
  }
  else
  {
    $current_db_schema_version = db_schema_version($upgrade_handle);
    $current_db_schema_version_local = db_schema_version($upgrade_handle, true);

    // Do any MRBS upgrades first
    if ($result && ($current_db_schema_version < DB::DB_SCHEMA_VERSION))
    {
      $result = $result && upgrade_tables(false, $current_db_schema_version, DB::DB_SCHEMA_VERSION, $upgrade_handle);
    }
    else
    {
      echo "<p>" . htmlspecialchars(get_vocab('already_at_version', $current_db_schema_version)) . "</p>\n";
    }
    // Then any local upgrades
    if ($result && ($current_db_schema_version_local < DB::DB_SCHEMA_VERSION_LOCAL))
    {
      $result = $result && upgrade_tables(true, $current_db_schema_version_local, DB::DB_SCHEMA_VERSION_LOCAL, $upgrade_handle);
    }
  }

  return $result;
}


function upgrade_site(array &$admin_handles, ?string $config_file=null) : bool
{
  global $dbsys, $db_host, $db_database, $db_port, $db_schema, $db_tbl_prefix, $db_options;
  global $db_admin_username, $db_admin_password;

  // Get the site's DB config settings
  $settings = get_db_config($config_file);
  foreach ($settings as $var => $value)
  {
    $$var = $value;
  }

  // Form the new DSN
  $dsn = DBFactory::createDsn($dbsys, $db_host, $db_database, $db_port ?? null);
  // Try and get a handle for the site
  if (!isset($admin_handles[$dsn]))
  {
    // Try and get a new connection using the login details we've already been given
    try {
      $admin_handle = DBFactory::create(
        $dbsys,
        $db_host,
        $db_admin_username,
        $db_admin_password,
        $db_database,
        false,
        $db_port,
        $db_options
      );
      $admin_handles[$dsn] = $admin_handle;
    }
    catch (DBException $e) {
      // Don't do anything: we were only trying to see if we could connect using the same details
    }
  }
  if (isset($admin_handles[$dsn]))
  {
    return do_upgrade($admin_handles[$dsn]);
  }

  echo "<p>" . htmlspecialchars(get_vocab('no_connection')) . "</p>\n";

  return false;
}


function upgrade_report(bool $main_site_result, array $failed_sites) : void
{
  global $multisite;

  // Summarise
  if (!$multisite)
  {
    // Just report on success.  Failure will be obvious.
    if ($main_site_result) {
      echo "<p>" . htmlspecialchars(get_vocab('upgrade_completed')) . "</p>\n";
    }
  }
  else
  {
    echo "<h2>" . htmlspecialchars(get_vocab('upgrade_summary')) . "</h2>\n";

    if ($main_site_result && empty($failed_sites)) {
      echo "<p>" . htmlspecialchars(get_vocab('upgrade_completed')) . "</p>\n";
    }
    else {
      // Report on the main site
      if (!$main_site_result) {
        echo "<p>" . htmlspecialchars(get_vocab('main_site_failed')) . "</p>\n";
      }

      // Report on failed sub-sites
      if (!empty($failed_sites)) {
        echo "<p>" . htmlspecialchars(get_vocab('failed_sites')) . "</p>\n";
        echo "<ul>\n";
        foreach ($failed_sites as $failed_site) {
          echo "<li>" . htmlspecialchars($failed_site) . "</li>\n";
        }
        echo "</ul>\n";
      }

      // Advice on what to do next
      echo "<p>" . htmlspecialchars(get_vocab('retry_from_failing')) . "</p>\n";
    }
  }
}


// Get a database username and password
function db_get_userpass() : void
{
  print_header();

  $form = new Form(Form::METHOD_POST);
  $form->setAttributes(array('class'  => 'standard',
    'id'     => 'db_logon',
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
  print_footer(true);
}


// Sanity check:  check that we can access the MRBS tables.  If we can't, it's
// either because they don't exist or we don't have permission.
if (!db()->table_exists(_tbl('entry')))
{
  fatal_error(get_vocab('fatal_no_tables'));
}

// Check the database schema versions
$current_db_schema_version = db_schema_version(db());
$current_db_schema_version_local = db_schema_version(db(), true);

// Check whether the database schema versions are higher than expected
// by this version of MRBS.  Database downgrades are not supported.
if (($current_db_schema_version > DB::DB_SCHEMA_VERSION) ||
    ($current_db_schema_version_local > DB::DB_SCHEMA_VERSION_LOCAL))
{
  fatal_error(get_vocab('mrbs_upgrade_needed'));
}

// If either of the database schema version numbers are out of date, then
// upgrade the database - provided of course that the entry table exists.
if (($current_db_schema_version < DB::DB_SCHEMA_VERSION) ||
    ($current_db_schema_version_local < DB::DB_SCHEMA_VERSION_LOCAL))
{
  // Upgrade needed

  // Upgrades can take some time, so turn on implicit flushing so that status
  // updates appear as they happen.  Also turn off output compression because
  // that will involve some buffering.
  // Note that this won't always result in streamed output as (a) the web server
  // (b) the browser may implement their own buffering.  Solution: use Ajax calls?
  ini_set('zlib.output_compression', '0');
  ob_end_flush();
  ob_implicit_flush();

  // Just use a simple header as the normal header may (a) use features
  // which are not available until after the database upgrade or (b) use
  // functions which are not available until after dbsys has run.
  print_simple_header();

  echo '<h1>' . get_vocab('mrbs') . "</h1>\n";
  echo '<p><strong>' . get_vocab('upgrade_required') . "</strong></p>\n";

  $admin_handle = null;

  // We need to open a connection to the database with a database
  // username that has admin rights.
  // TODO: The while loop isn't doing anything.  Ideally we want to offer the user a
  // TODO: chance to try a new username and password after a connection failure.
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
      try {
        $admin_handle = DBFactory::create(
          $dbsys,
          $db_host,
          $db_admin_username,
          $db_admin_password,
          $db_database,
          false,
          $db_port,
          $db_options
        );
      }
      catch (DBException $e) {
        trigger_error($e->getMessage(), E_USER_NOTICE);
        fatal_error(get_vocab('no_connection'));
      }
    }
  }

  // Check the CSRF token before we make any changes
  Form::checkToken();

  // If we're using multisite then the various sites could be using different databases, or even
  // different database systems (MySQL or PostgreSQL).  In that case we'll need different
  // handles to access them, so we store the handles indexed by DSN (which will give enough detail
  // to distinguish them).
  $dsn = DBFactory::createDsn($dbsys, $db_host, $db_database, $db_port);
  $admin_handles = [$dsn => $admin_handle];

  // Get all the sub-site config files in this installation
  $site_config_files = get_site_config_files();

  // Upgrade each of the sub-sites
  $failed_sites = [];
  foreach ($site_config_files as $site_config_file => $dir)
  {
    // Do the upgrade for the sub-site
    echo "<h2>" . htmlspecialchars(get_vocab('upgrading_site', $dir)) . "</h2>\n";
    if (!upgrade_site($admin_handles, $site_config_file))
    {
      $failed_sites[] = $dir;
    }
  }

  // Then upgrade the main site
  // We only need this heading if we're in multi-site mode
  if ($multisite)
  {
    echo "<h2>" . htmlspecialchars(get_vocab('upgrading_main_site')) . "</h2>\n";
  }
  $main_site_result = upgrade_site($admin_handles);

  // Report on the upgrade
  upgrade_report($main_site_result, $failed_sites);

  // Close the database connections that have admin rights
  foreach ($admin_handles as $admin_handle)
  {
    unset($admin_handle);
  }

  // Restore the DB config settings for the site we're supposed to be running
  $settings = get_db_config('site_config.inc');
  foreach ($settings as $var => $value)
  {
    $$var = $value;
  }

  echo '<p class="upgrade_nav"><a href="' . htmlspecialchars(multisite('index.php')) . '">' . get_vocab('returncal') . '</a>.</p>';
  print_footer(true);
}
