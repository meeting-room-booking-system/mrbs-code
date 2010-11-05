<?php

// $Id$

// Go through the area table and if any of the per-area settings are NULL then
// set them to the area defaults

require_once "standard_vars.inc.php";  // won't have been included yet and we need it for $area_defaults

global $tbl_area, $area_defaults, $boolean_fields;
global $provisional_enabled, $approval_enabled;

// $provisional_enabled has now been renamed $approval_enabled, to avoid confusion with
// tentative bookings.   If $provisional_enabled is still being used in the config file
// then we need to warn them.   Furthermore, if $provisional_enabled is TRUE and 
// $approval_enabled is still FALSE, ie the system default, then we assume that they really
// meant to set $approval_enabled to TRUE and we will use that.   Otherwise they will find
// that areas that have still got the field set to NULL will have approval disabled when
// previously it was enabled.
if (isset($provisional_enabled))
{
  echo "<p class=\"warning\">";
  echo 'Warning:  you are still using <code>$provisional_enabled</code> in your config file.
        This has now been replaced by <code>$approval_enabled</code>.   Please ';
  if ($area_defaults['approval_enabled'])
  {
    // As they have changed the default setting they must already have $approval_enabled
    // in their config file, so they just need to delete the old variable
    echo 'delete <code>$provisional_enabled</code> from your config file';
  }
  else
  {
    // otherwise they should rename it
    echo 'rename <code>$provisional_enabled</code> to <code>$approval_enabled</code>';
  }
  echo ' once this upgrade is complete.';
  if (!$area_defaults['approval_enabled']  && $provisional_enabled)
  {
    echo '<p class="warning">As you have <code>$provisional_enabled = TRUE;</code> MRBS is assuming
          <code>$approval_enabled = TRUE;</code> as the default setting during this upgrade.</p>';
    $area_defaults['approval_enabled'] = TRUE;
  }
  echo "</p>\n";
}

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
      $sql_val = "'" . addslashes($area_defaults[$key]) . "'";
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