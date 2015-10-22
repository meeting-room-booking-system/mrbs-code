<?php

// $Id$


// Upgrade between database schema versions.
//
// Returns FALSE on error, TRUE is successful

function upgrade_echo($message)
{
  echo $message;
  // Flush the message, so that there's some progress information
  // output to the browser even when the upgrade is taking a while
  if (ob_get_length() !== FALSE)
  {
    ob_flush();
  }
  flush();
}

function upgrade_database($local, $from, $to, $upgrade_handle)
{
  // $local is a boolean specifying whether the upgrades are global MRBS ones ($local == FALSE)
  // or local upgrades ($local == TRUE);
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
    upgrade_echo("<p>" .
                 (($local) ? get_vocab("upgrade_to_local_version") : get_vocab("upgrade_to_version")) .
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
    $handle = fopen($filename, "r");
    if (!$handle)
    {
      // No need to localise, should never happen!
      upgrade_echo("Fatal error: Failed to open '$filename' for reading.\n");
      return FALSE;
    }
    $file_size = filesize($filename);
    $sql = (!empty($file_size)) ? fread($handle, filesize($filename)) : "";
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
        $res = sql_query($query, $upgrade_handle);
        if ($res === FALSE)
        {
          // No need to localise, should hopefully never happen
          upgrade_echo("<br>Tried:<pre>" . htmlspecialchars($query) . "</pre> and got error:" .
                       "<pre>" . sql_error($upgrade_handle) . "</pre>\n");
          return FALSE;
        }
      }
    }

    upgrade_echo("<br>" . get_vocab("ok"));
    if ($ver > 1)
    {
      $variable_name = ($local) ? "local_db_version" : "db_version";
      $res = sql_command("UPDATE $tbl_variables SET variable_content = '$ver' ".
                         "WHERE variable_name = '$variable_name'");
      if ($res == -1)
      {
        // No need to localise, should never happen
        upgrade_echo("<span class=\"error\">Failed to update database version variable.</span>");
        return FALSE;
      }
    }
    upgrade_echo("</p>\n");
    
    // Now execute the PHP file if there is one
    if (file_exists($php_filename))
    {
      include($php_filename);
    }
  }
  return TRUE;
}

