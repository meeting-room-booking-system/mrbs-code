<?php

# $Id$

# This script converts text in the database from a particular encoding
# to UTF-8

include "grab_globals.inc.php";

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
      UTF-8, to use with MRBS\'s new Unicode feature ($unicode_encoding in config.inc.php
      set to 1).<br>
      <b>NOTE: Only run this script <u>once</u>. Running it more than once will
      make a right mess of any non-ASCII text in the database. I\'d recommend you
      backup your database before running this script if you\'re at all worried.</b>
    </p>

    <form method=get action="'.$PHP_SELF.'">
      Encoding to convert from:<br>
      <select name="encoding">
        <option value="iso-8859-1">Latin 1 (English/French/German/Italian/Norwegian etc.)
        <option value="iso-8859-2">Latin 2 (Czech)
        <option value="Big-5">Big 5 (Chinese Traditional)
        <option value="Shift-JIS">Shift-JIS (Japanese)
      </select>
      <p>
      <input type=submit value="Do it">
    </form>
';
}
else
{
  include "config.inc.php";
  include "$dbsys.inc";
  include "functions.inc";

  echo '
    Starting update, this could take a while...<p>

    Updating areas:
';

  $sql = "select id,area_name from mrbs_area";
  $areas_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($areas_res, $i)); $i++)
  {
    $id = $row[0];
    $name = slashes(iconv($encoding,"utf-8",$row[1]));

    $upd_sql = "update mrbs_area set area_name='$name' where id=$id";
    sql_query($upd_sql);

    echo ".";
  }
  echo " done.<br>Updating rooms: ";

  $sql = "select id,room_name,description,capacity from mrbs_room";
  $rooms_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($rooms_res, $i)); $i++)
  {
    $id = $row[0];
    $name = slashes(iconv($encoding,"utf-8",$row[1]));
    $desc = slashes(iconv($encoding,"utf-8",$row[2]));
    $capa = slashes(iconv($encoding,"utf-8",$row[3]));

    $upd_sql = "update mrbs_room set room_name='$name',description='$desc',capacity='$capa' where id=$id";
    sql_command($upd_sql);

    echo ".";
  }
  echo " done.<br>Updating repeating entries: ";

  $sql = "select id,name,description from mrbs_repeat";
  $repeats_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($repeats_res, $i)); $i++)
  {
    $id = $row[0];
    $name = slashes(iconv($encoding,"utf-8",$row[1]));
    $desc = slashes(iconv($encoding,"utf-8",$row[2]));

    $upd_sql = "update mrbs_repeat set name='$name',description='$desc' where id=$id";
    sql_command($upd_sql);

    echo ".";
  }
  echo " done.<br>Updating normal entries: ";

  $sql = "select id,name,description from mrbs_entry";
  $entries_res = sql_query($sql);

  for ($i = 0; ($row = sql_row($entries_res, $i)); $i++)
  {
    $id = $row[0];
    $name = slashes(iconv($encoding,"utf-8",$row[1]));
    $desc = slashes(iconv($encoding,"utf-8",$row[2]));

    $upd_sql = "update mrbs_entry set name='$name',description='$desc' where id=$id";
    sql_command($upd_sql);

    echo ".";
  }
  echo 'done.<p>

    Finished everything, byebye!
';
}
?>
  </body>
</html>
