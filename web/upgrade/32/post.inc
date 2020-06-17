<?php
namespace MRBS;

require_once "standard_vars.inc.php"; // to ensure the timezone is set

global $admin_handle;

// Get all the monthly repeats
$sql = "SELECT T.id, T.start_time, A.timezone
          FROM " . _tbl('repeat') . " T, " . _tbl('area') . " A, " . _tbl('room') . " M
         WHERE T.rep_type=" . REP_MONTHLY . "
           AND T.room_id=M.id
           AND M.area_id=A.id";
 
$res = $admin_handle->query($sql);

// Save the current default timezone so that we can restore it at the end
$old_tz = mrbs_default_timezone_get();

$mods = array();

while (false !== ($row = $res->next_row_keyed()))
{
  // We need to make sure that we have the correct timezone set when we work out
  // the day of the month that this series repeats on
  if (!empty($row['timezone']))
  {
    mrbs_default_timezone_set($row['timezone']);
  }
  $mods[$row['id']] = date('j', $row['start_time']);
}

foreach ($mods as $id => $month_absolute)
{
  $sql = "UPDATE " . _tbl('repeat') . "
             SET month_absolute=$month_absolute
           WHERE id=$id";
           
  $admin_handle->command($sql);
}

// Restore the original timezone
mrbs_default_timezone_set($old_tz);

