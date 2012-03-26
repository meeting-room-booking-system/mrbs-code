<?php

// $Id$

// Go through the area table and if any of the per-area settings are NULL then
// set them to the area defaults

global $tbl_area, $area_defaults, $boolean_fields;

$fields = sql_field_info($tbl_area);
foreach ($fields as $field)
{
  $key = $field['name'];
  if (array_key_exists($key, $area_defaults))
  {
    if (in_array($key, $boolean_fields['area']))
    {
      $sql_val = ($area_defaults[$key]) ? 1 : 0;
    }
    elseif ($field['nature'] == 'integer')
    {
      $sql_val = $area_defaults[$key];
    }
    else
    {
      $sql_val = "'" . sql_escape($area_defaults[$key]) . "'";
    }
    $sql = "UPDATE $tbl_area SET $key=$sql_val WHERE $key IS NULL";
    $res = sql_command($sql);
    if ($res == -1)
    {
      // No need to localise, should never happen
      print "<span class=\"error\">Failed to set default value for column '$key' in area table.</span><br>";
    }
  }
}

?>