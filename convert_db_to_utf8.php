<?php
namespace MRBS;

// This script converts text in the database from a particular encoding
// to UTF-8

require_once "defaultincludes.inc";

// Configuration for the database collation conversion code
$printonly=false; //change this to false to alter on the fly
$charset="utf8";
$collate="utf8_general_ci";
$altertablecharset=true;
$alterdatabasecharset=true;


$encoding = get_form_var('encoding', 'string');

header('Content-Type: text/html; charset="utf-8"');

?>

<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>MRBS database encoding fixing script</title>
  </head>
  <body>

  <h1>MRBS database encoding fixing script</h1>

<?php

if (!isset($encoding))
{
?>
    <p>
      This script will convert all the text in the database from a selected
      encoding to UTF-8, to use with MRBS's default encoding.
    </p>
    <p>
      If you are only running this script to change your MySQL
      database's collation to UTF-8, and the data in your database is
      already in UTF-8, you can specify the "from" encoding as UTF-8.
    </p>
    <p>
      <b>NOTE: Only run this script <u>once</u>. Running it more than once
      will make a right mess of any non-ASCII text in the database. I'd
      recommend you backup your database before running this script if
      you're at all worried.</b>
    </p>

    <form method="post" action="convert_db_to_utf8.php">
      Encoding to convert from:<br>
      <select name="encoding">
        <option value="iso-8859-1">Latin 1 (English/French/German/Italian/Norwegian etc.)</option>
        <option value="iso-8859-2">Latin 2 (Czech)</option>
        <option value="iso-8859-7">ISO-8859-7 (Greek)</option>
        <option value="iso-8859-15">Latin 9 (European)</option>
        <option value="Big-5">Big 5 (Chinese Traditional)</option>
        <option value="Shift-JIS">Shift-JIS (Japanese)</option>
        <option value="utf-8">UTF-8 (No conversion)</option>
      </select>

<?php
  if ($dbsys == 'mysql' || $dbsys == 'mysqli')
  {
    $ver = db()->version();
    // Sanitise the output to contain just the version number, hopefully
    $ver = preg_replace('/[^0-9.]/', '', $ver);
    // Pull out the floating point version number
    sscanf($ver, "%f", $version);
    if ($version >= 4.1)
    {
      $not_unicode = FALSE;
      $res = db()->query("SHOW FULL COLUMNS FROM $tbl_entry");
      for ($i = 0; ($row = $res->row_keyed($i)); $i++)
      {
        if (!is_null($row['Collation']) &&
            !preg_match('/utf8/', $row['Collation']))
        {
          $not_unicode = TRUE;
        }
      }
      if ($not_unicode)
      {
?>
      <div style="margin-top: 1em; padding: 0.5em; border: solid 2px; background-color: #FFF1A3">
        Your database appears to be MySQL &gt;= 4.1 but the table/column
        collations are not specified as UTF-8. You should tick the box
        below to change the database collation<br>
        <input type="checkbox" name="change_collation" value="1">
        Change database collation to UTF-8
      </div>

<?php
      } // end of if ($not_unicode)
    } // end of if ($version >= 4.1)
  } // end of if ($dbsys)
?>
      <div style="margin-top: 1em; padding: 0.5em; border: solid 2px; background-color: #75C7FF">
        Optionally, you may specify an alternate database username and
        password that has permissions to modify the MRBS table definitions.
        If these are not specified the script will use your normal MRBS
        database credentials:<br>
        Database admin username: <input type="text" name="admin_username"><br>
        Database admin password: <input type="password" name="admin_password"><br>
      </div>
      <br>
      <input type="submit" value="Do it">
    </form>

<?php
}
else
{
  # A 2D array listing the columns that need to be converted to UTF-8
  $update_columns = array
  (
    $tbl_area => array('area_name', 'custom_html'),
    $tbl_room => array('room_name', 'description', 'room_admin_email',
                       'custom_html'),
    $tbl_entry => array('create_by', 'name', 'description', 'info_user',
                        'info_text'),
    $tbl_repeat => array('create_by', 'name', 'description', 'info_user',
                         'info_text'),
    $tbl_users => array('name', 'password_hash', 'email')
  );

  $admin_username = get_form_var('admin_username', 'string');
  $admin_password = get_form_var('admin_password', 'string');
  $change_collation = get_form_var('change_collation', 'int');
  if (is_null($change_collation))
  {
    $change_collation = 0;
  }

  if (is_null($admin_username) || ($admin_username == ''))
  {
    $admin_username = $db_login;
    $admin_password = $db_password;
  }
  $db_handle = DBFactory::create($dbsys,
                                 $db_host,
                                 $admin_username,
                                 $admin_password,
                                 $db_database,
                                 0,
                                 $db_port);
  echo '
    <p>
      Starting update, this could take a while...
    </p>

';

  if ($encoding != 'utf-8')
  {
    foreach ($update_columns as $table => $columns)
    {
      print "
    <p>
      Updating '$table' table...
";
      $sql = "SELECT id,".implode(',',$columns)." FROM $table";
      $stmt = $db_handle->query($sql);

      for ($i = 0; ($row = $stmt->row_keyed($i)); $i++)
      {
        $sql_params = array();
        $updates = array();
        $id = $row['id'];
        foreach ($columns as $col)
        {
          $updates[] = "$col=?";
          $sql_params[] = iconv($encoding,"utf-8",$row[$col]);
        }
        $upd_sql = "UPDATE $table SET ".
          implode(',', $updates)." WHERE id=?";
        $sql_params[] = $id;

        $db_handle->query($upd_sql, $sql_params);
        print "<!-- $upd_sql -->\n";
      }
      print "
      done.
    </p>
";
    } // end of foreach
  }
  else
  {
    print '
    <p style="color: #2f80b5">
      Skipping text conversion, as UTF-8 was specified.
    </p>
';
  } // end of if ($encoding)

  if ($change_collation)
  {
    print '
    <p>
      Converting your database\'s collation to UTF-8...
    </p>
';
    convert_one_db($db_database);
  } // end of if ($change_collation)

  echo '
    <p>
      Database conversion finished.
    </p>
';
} // end of if (!isset($encoding))
?>
  </body>
</html>

<?php

// Code adapted from a script found on the web written by
// Shimon Doodkin shimon_d@hotmail.com

function PMA_getDbCollation($db)
{
  global $db_handle;

  $sq='SHOW CREATE DATABASE `'.$db.'`;';
  $stmt = $db_handle->query($sq);
  if(!$stmt)
  {
    echo "\n\n".$sq."\n".$db_handle->error()."\n\n";
  }
  else
  {
    for ($i = 0; ($row = $stmt->row_keyed($i)); $i++)
    {
      $tokenized = explode(' ', $row[1]);

      for ($i = 1; $i + 3 < count($tokenized); $i++)
      {
        if (($tokenized[$i] == 'DEFAULT') &&
            ($tokenized[$i + 1] == 'CHARACTER') &&
            ($tokenized[$i + 2] == 'SET'))
        {
          if (isset($tokenized[$i + 5]) &&
              ($tokenized[$i + 4] == 'COLLATE'))
          {
            return array($tokenized [$i + 3],
                         $tokenized[$i + 5]); // We found the collation!
          }
          else
          {
            return array($tokenized [$i + 3]);
          }
        }
      }
    }
  }
  return '';
}

//
function convert_one_db($db)
{
  global $alterdatabasecharset;
  global $altertablecharset;
  global $charset;
  global $collate;
  global $printonly;
  global $db_handle;

  $db_cha = PMA_getDbCollation($db);

  if ( substr($db_cha[0],0,4) == 'utf8' ) // only convert unconverted db
  {
    // This doesn't work for me, but isn't a big deal, as the table
    // check below works
    echo "Skipping utf8 database '$db'\n";
    return;
  }

  $db_handle->command("USE $db");
  $stmt = $db_handle->query("SHOW TABLES");
  if(!$stmt)
  {
    echo "\n\n".$db_handle->error()."\n\n";
  }
  else
  {
    for ($i = 0; ($data = $stmt->row($i)); $i++)
    {
      echo "Converting '$data[0]' table...\n";
      $stmt1 = $db_handle->query("show FULL columns from $data[0]");
      if(!$statement1)
      {
        echo "\n\n".$db_handle->error()."\n\n";
      }
      else
      {
        for ($j = 0; ($data1 = $stmt1->row_keyed($j)); $j++)
        {
          if (in_array(array_shift(split("\\(",
                                         $data1['Type'],2)),
                       array(
                             //'national char',
                             //'nchar',
                             //'national varchar',
                             //'nvarchar',
                             'char',
                             'varchar',
                             'tinytext',
                             'text',
                             'mediumtext',
                             'longtext',
                             'enum',
                             'set'
                            )
                ))
          {
            if (substr($data1['Collation'],0,4) != 'utf8') // limit to charset
            {
              $sq="ALTER TABLE `$data[0]` CHANGE `".
                $data1['Field'].'` `'.$data1['Field'].'` '.
                $data1['Type'].' CHARACTER SET binary '.
                (($data1['Default'] == '') ?
                 '' :
                 (($data1['Default'] == 'NULL') ?
                  ' DEFAULT NULL' :
                  ' DEFAULT \''.addslashes($data1['Default']).'\'')).
                (($data1['Null'] == 'YES') ? ' NULL ' : ' NOT NULL');

              if (!$printonly &&
                  !$db_handle->query($sq))
              {
                echo "\n\n".$sq."\n".$db_handle->error()."\n\n";
              }
              else
              {
                if ($printonly)
                {
                  echo ($sq."\n") ;
                }
                $sq="ALTER TABLE `$data[0]` CHANGE `".
                  $data1['Field'].'` `'.$data1['Field'].'` '.
                  $data1['Type']." CHARACTER SET $charset ".
                  (($collate == '') ? '' : "COLLATE $collate").
                  (($data1['Default'] == '') ?
                   '' :
                   (($data1['Default'] == 'NULL') ?
                    ' DEFAULT NULL' :
                    ' DEFAULT \''.addslashes($data1['Default']).'\'')).
                  (($data1['Null'] == 'YES') ?
                   ' NULL ' :
                   ' NOT NULL').
                  (($data1['Comment'] == '') ?
                    '' :
                    ' COMMENT \''.addslashes($data1['Comment']).'\'');

                if (!$printonly &&
                    !$db_handle->query($sq))
                {
                  echo "\n\n".$sq."\n".$db_handle->error()."\n\n";
                }
                else if ($printonly)
                {
                  echo ($sq."\n") ;
                }
              } // end of if (!$printonly)
            } // end of if (substr)
          } // end of if (in_array)
        } // end of inner for
      } // end of if ($stmt1)

      if ($altertablecharset)
      {
        $sq='ALTER TABLE `'.$data[0]."` ".
          "DEFAULT CHARACTER SET $charset ".
          (($collate == '') ? '' : "COLLATE $collate");

        if ($printonly)
        {
          echo ($sq."\n") ;
        }
        else
        {
          if (!$db_handle->query($sq))
          {
            echo "\n\n".$sq."\n".$db_handle->error()."\n\n";
          }
        }
      } // end of if ($altertablecharset)
	  print "done.<br>\n";
    } // end of outer for
  } // end of if (!$stmt)
  if ($alterdatabasecharset)
  {
    $sq='ALTER DATABASE `'.$db."` ".
      "DEFAULT CHARACTER SET $charset ".
      (($collate == '') ? '' : "COLLATE $collate");

    if ($printonly)
    {
      echo ($sq."\n") ;
    }
    else
    {
      if (!$db_handle->query($sq))
      {
        echo "\n\n".$sq."\n".$db_handle->error()."\n\n";
      }
    }
  } // end of if ($alterdatabasecharset)
} // end of function convert_one_db()

