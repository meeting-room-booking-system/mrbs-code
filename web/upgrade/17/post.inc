<?php

// $Id$

global $tbl_entry, $tbl_repeat;

// We are about to introduce a new status, the confirmation status, for bookings
// that are not 100% certain to happen.   Instead of introducing yet another column
// in the database, we will use the existing status column to record any status information
// as follows:
//
// Bit 0:  Privacy status (set = private)
// Bit 1:  Approval status (set = not yet approved)
// Bit 2:  Confirmation status (set = not yet confirmed)
//
// Thus a booking with status 0x00 is a "standard" booking, ie confirmed, approved
// and open for all to see.
//
// This means that the existing status field has to be inverted (previously 1 or TRUE
// meant confirmed) and shifted one place to the left.



// First of all update the entry table

// Acquire mutex to lock out others trying to update the entry table (unlikely because
// the only other people able to update the database at this stage will be site admins
// who know the database username and password, but just in case ...)
if (!sql_mutex_lock("$tbl_entry"))
{
  fatal_error(1, get_vocab("failed_to_acquire"));
}

$sql = "UPDATE $tbl_entry SET status=(((~status)&1)<<1)|(private&1)";
$res = sql_command($sql);
if ($res == -1)
{
  trigger_error(sql_error(), E_USER_WARNING);
  // No need to localise, should never happen
  print "<span class=\"error\">Failed to update status column in entry table.</span>";
}

sql_mutex_unlock("$tbl_entry");



// Then do the repeat table.   This is slightly different from the entry table because
// it did not previously have a status column.    (This was a mistake which we are
// going to correct in a momemt.)

// Acquire mutex to lock out others trying to update the repeat table (unlikely because
// the only other people able to update the database at this stage will be site admins
// who know the database username and password, but just in case ...)
if (!sql_mutex_lock("$tbl_repeat"))
{
  fatal_error(1, get_vocab("failed_to_acquire"));
}

$sql = "UPDATE $tbl_repeat SET status=private&1";
$res = sql_command($sql);

if ($res == -1)
{
  trigger_error(sql_error(), E_USER_WARNING);
  // No need to localise, should never happen
  print "<span class=\"error\">Failed to update status column in repeat table with privacy status.</span>";
}

// Now get the approval status for the repeat table.   A series is considered to be
// awaiting approval if any one of its individual members is awaiting approval.

// Find all the rows in the entry table that are members of a series and are awaiting approval
$sql = "SELECT DISTINCT repeat_id FROM $tbl_entry WHERE repeat_id!=" . REP_NONE . " AND status=0";
$res = sql_query($sql);
if ($res === FALSE)
{
  trigger_error(sql_error(), E_USER_WARNING);
  fatal_error(FALSE, "Failed to update status column in repeat table with approval status.");
}

for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
{
  // Set the approval status for each one
  $sql = "UPDATE $tbl_repeat
             SET status=status|" . STATUS_AWAITING_APPROVAL . "
           WHERE id=" . $row['repeat_id'];
  $result = sql_command($sql);
  if ($result == -1)
  {
    // We've got an SQL error, so report it
    trigger_error(sql_error(), E_USER_WARNING);
  }
  if ($result != 1)
  {
    // Something's gone wrong.   There's either been an SQL error, or else no rows have been
    // affected, which should not be the case
    fatal_error(FALSE, "Failed to update status column in repeat table with approval status.");
  }
}  

sql_mutex_unlock("$tbl_repeat");

?>