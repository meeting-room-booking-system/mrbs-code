<?php
namespace MRBS;

// Go through the area table and if any of the per-area settings are NULL then
// set them to the area defaults

global $tbl_area, $area_defaults, $boolean_fields, $admin_handle;

$fields = $admin_handle->field_info($tbl_area);
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
    $sql = "UPDATE $tbl_area SET $key=? WHERE $key IS NULL";
    $admin_handle->command($sql, array($sql_val));
  }
}

