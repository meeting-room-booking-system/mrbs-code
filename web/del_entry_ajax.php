<?php

// $Id$

// A version of del_entry.php designed to be used in Ajax POST calls.  It
// takes an array of ids to be deleted as input.   These are always assumed
// to be single entries.   Returns the number of entries deleted, or some
// kind of string on failure (most likely a login page)
//
// If deleting lots of entries you may need to split the Ajax requests into
// multiple smaller requests in order to avoid exceeding the system limit 
// for POST requests.
//
// Note that:
// (1) the code assumes that you are an admin with powers to delete anything.
//     It checks that you are an admin and so does not bother checking that
//     you have rights in that particular area or room, nor does it check that
//     the proposed deletion conforms to any policy in force.
// (2) email notifications are not sent, even if they are normally configured
//     to be sent.   Sending many thousands of emails in the space of a few
//     seconds could overwhelm many mail servers, or break the usage policies
//     on hosted systems.

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Check the user is authorised for this page
checkAuthorised();

// Check that the user has the highest level of admin rights
$user = getUserName();
$level = authGetUserLevel($user);
if ($level < $max_level)
{
  exit;
}

// Get non-standard form variables
$ids = get_form_var('ids', 'array');

// Check that $ids consists of an array of integers, to guard against SQL injection
foreach ($ids as $id)
{
  if (!is_numeric($id) || (intval($id) != $id) || ($id < 0))
  {
    exit;
  }
}

// Everything looks OK - go ahead and delete the entries
$sql = "DELETE FROM $tbl_entry WHERE id IN (" . implode(',', $ids) . ")";
$result = sql_command($sql);

echo $result;
?>