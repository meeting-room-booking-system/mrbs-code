<?php

// $Id$


//
function upgrade_database($local, $from, $to)
{
  // $local is a boolean specifying whether the upgrades are global MRBS ones ($local == FALSE)
  // or local upgrades ($local == TRUE);
  global $dbsys;
  global $db_tbl_prefix;
  global $tbl_variables;

  $sql_type = $dbsys;
  if ($sql_type == 'mysqli')
  {
    $sql_type = 'mysql';
  }
    
  for ($ver = ($from+1); $ver <= $to; $ver++)
  {
    print "<p>" .
          (($local) ? get_vocab("upgrade_to_local_version") : get_vocab("upgrade_to_version")) .
          ": $ver";

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
      print "Fatal error: Failed to open '$filename' for reading.\n";
      return;
    }
    $sql = fread($handle, filesize($filename));
    fclose($handle);

    $sql = str_replace('%DB_TBL_PREFIX%', $db_tbl_prefix, $sql);

    foreach (explode(";", $sql) as $command)
    {
      // Skip any empty command (so that last semi-colon doesn't run
      // an empty command)
      if (preg_match("/\S/", $command))
      {
        $res = sql_command($command);
        if ($res == -1)
        {
          // No need to localise, should hopefully never happen
          print "<br>Tried:<pre>
".htmlspecialchars($command)."
</pre> and got error:<pre>
".sql_error()."
</pre>\n";
        }
      }
    }

    print "<br>".get_vocab("ok");
    if ($ver > 1)
    {
      $variable_name = ($local) ? "local_db_version" : "db_version";
      $res = sql_command("UPDATE $tbl_variables SET variable_content = '$ver' ".
                         "WHERE variable_name = '$variable_name'");
      if ($res == -1)
      {
        // No need to localise, should never happen
        print "<span class=\"error\">Failed to update database version variable.</span>";
      }
    }
    print "</p>\n";
    
    // Now execute the PHP file if there is one
    if (file_exists($php_filename))
    {
      include($php_filename);
    }
  }
}

?>
