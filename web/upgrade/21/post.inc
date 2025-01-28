<?php
namespace MRBS;

// Go through the area table and if any of the per-area settings are NULL then
// set them to the area defaults

global $area_defaults, $boolean_fields;

$fields = $upgrade_handle->field_info(_tbl('area'));
foreach ($fields as $field)
{
  $key = $field['name'];
  if (array_key_exists($key, $area_defaults))
  {
    if (in_array($key, $boolean_fields['area']))
    {
      $sql_val = ($area_defaults[$key]) ? 1 : 0;
    }
    else
    {
      $sql_val = $area_defaults[$key];
    }
    $sql = "UPDATE " . _tbl('area') . "
               SET $key=?
             WHERE $key IS NULL";
    $upgrade_handle->command($sql, array($sql_val));
  }
}

