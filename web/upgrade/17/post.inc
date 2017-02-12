<?php
namespace MRBS;

global $tbl_entry, $tbl_repeat, $admin_handle;

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

// In these queries we set timestamp=timestamp to prevent it being automatically set
// to the current time (only applies to MySQL - PostgreSQL timestamps don't update,
// but we'll do it anyway)


// First of all update the entry table

// Acquire mutex to lock out others trying to update the entry table (unlikely because
// the only other people able to update the database at this stage will be site admins
// who know the database username and password, but just in case ...)
if (!$admin_handle->mutex_lock($tbl_entry))
{
  fatal_error(get_vocab("failed_to_acquire"));
}

$sql = "UPDATE $tbl_entry
           SET status=(((~status)&1)<<1)|(private&1),
               timestamp=timestamp";
$admin_handle->command($sql);

$admin_handle->mutex_unlock($tbl_entry);



// Then do the repeat table.   This is slightly different from the entry table because
// it did not previously have a status column.    (This was a mistake which we are
// going to correct in a momemt.)

// Acquire mutex to lock out others trying to update the repeat table (unlikely because
// the only other people able to update the database at this stage will be site admins
// who know the database username and password, but just in case ...)
if (!$admin_handle->mutex_lock($tbl_repeat))
{
  fatal_error(get_vocab("failed_to_acquire"));
}

$sql = "UPDATE $tbl_repeat
           SET status=private&1,
               timestamp=timestamp";
$admin_handle->command($sql);

// Now get the approval status for the repeat table.   A series is considered to be
// awaiting approval if any one of its individual members is awaiting approval.

// Find all the rows in the entry table that are members of a series and are awaiting approval
$sql = "SELECT DISTINCT repeat_id
          FROM $tbl_entry
         WHERE repeat_id!=0
           AND (status&" . STATUS_AWAITING_APPROVAL . " != 0)";
$res = $admin_handle->query($sql);

for ($i = 0; ($row = $res->row_keyed($i)); $i++)
{
  // Set the approval status for each one
  $sql = "UPDATE $tbl_repeat
             SET status=status|" . STATUS_AWAITING_APPROVAL . ",
                 timestamp=timestamp
           WHERE id=" . $row['repeat_id'];
  $result = $admin_handle->command($sql);

  if ($result != 1)
  {
    // Something's gone wrong.  No rows have been affected, which should not be the case
    fatal_error("Failed to update status column in repeat table with approval status.");
  }
}  

$admin_handle->mutex_unlock($tbl_repeat);

