<?php

// $Id$

// This script converts text in the database from a particular encoding
// to UTF-8

require_once "defaultincludes.inc";

$encoding = get_form_var('encoding', 'string');

header("Content-Type: text/html; charset=\"utf-8\"");

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
echo '

    <p>
      This script will convert all the text in the database from a selected encoding to
      UTF-8, to use with MRBS\'s default encoding.<br>
      <b>NOTE: Only run this script <u>once</u>. Running it more than once will
      make a right mess of any non-ASCII text in the database. I\'d recommend you
      backup your database before running this script if you\'re at all worried.</b>
    </p>

    <form method="get" action="'.$PHP_SELF.'">
      Encoding to convert from:<br>
      <select name="encoding">
        <option value="iso-8859-1">Latin 1 (English/French/German/Italian/Norwegian etc.)</option>
        <option value="iso-8859-2">Latin 2 (Czech)</option>
        <option value="iso-8859-7">ISO-8859-7 (Greek)</option>
        <option value="iso-8859-15">Latin 9 (European)</option>
        <option value="Big-5">Big 5 (Chinese Traditional)</option>
        <option value="Shift-JIS">Shift-JIS (Japanese)</option>
      </select>
      <br>
      <input type="submit" value="Do it">
    </form>
';
}
else
{
  echo '
    <p>
      Starting update, this could take a while...
    </p>

    <p>
      Updating areas:
';

  $sql = "SELECT id,area_name FROM mrbs_area";
  $areas_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($areas_res, $i)); $i++)
  {
    $id = $row[0];
    $name = addslashes(iconv($encoding,"utf-8",$row[1]));

    $upd_sql = "UPDATE mrbs_area SET area_name='$name' WHERE id=$id";
    sql_query($upd_sql);

    echo ".";
  }
  echo " done.<br>Updating rooms: ";

  $sql = "SELECT id,room_name,description,capacity FROM mrbs_room";
  $rooms_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($rooms_res, $i)); $i++)
  {
    $id = $row[0];
    $name = addslashes(iconv($encoding,"utf-8",$row[1]));
    $desc = addslashes(iconv($encoding,"utf-8",$row[2]));
    $capa = addslashes(iconv($encoding,"utf-8",$row[3]));

    $upd_sql = "UPDATE mrbs_room SET room_name='$name',description='$desc',capacity='$capa' WHERE id=$id";
    sql_command($upd_sql);

    echo ".";
  }
  echo " done.<br>Updating repeating entries: ";

  $sql = "SELECT id,name,description FROM mrbs_repeat";
  $repeats_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($repeats_res, $i)); $i++)
  {
    $id = $row[0];
    $name = addslashes(iconv($encoding,"utf-8",$row[1]));
    $desc = addslashes(iconv($encoding,"utf-8",$row[2]));

    $upd_sql = "UPDATE mrbs_repeat SET name='$name',description='$desc' WHERE id=$id";
    sql_command($upd_sql);

    echo ".";
  }
  echo " done.<br>Updating normal entries: ";

  $sql = "SELECT id,name,description FROM mrbs_entry";
  $entries_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($entries_res, $i)); $i++)
  {
    $id = $row[0];
    $name = addslashes(iconv($encoding,"utf-8",$row[1]));
    $desc = addslashes(iconv($encoding,"utf-8",$row[2]));

    $upd_sql = "UPDATE mrbs_entry SET name='$name',description='$desc' WHERE id=$id";
    sql_command($upd_sql);

    echo ".";
  }
  echo '
      done.
    </p>

    <p>
      Finished everything that I can do.
    </p>
    
    <p>
      If you are using MySQL >= 4 you may still you need to modify
      the database charset for textual columns. e.g.:
    </p>
    <pre>
ALTER TABLE mrbs_area
CHANGE area_name VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL
</pre>
';
}
?>
  </body>
</html>
 