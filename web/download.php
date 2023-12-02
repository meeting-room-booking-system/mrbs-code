<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Get the form variables
$column = get_form_var('column', 'string');
$id = get_form_var('id', 'int');

// Check the user is authorised for this page
if (!checkAuthorised(this_page(), true))
{
  http_response_code(403);
  exit;
}

// Check the user is allowed to view this file.
// If this column is private and the entry is private and the current user doesn't
// have write access to the entry, then they are not allowed to view the file.
$booking = get_booking_info($id, false, true);
if (($booking === false) ||
    (!empty($is_private_field["entry.$column"]) &&
     is_private_event($booking['private']) &&
     !getWritable($booking['create_by'], $booking['room_id'])))
{
  http_response_code(403);
  exit;
}

// Everything's OK, so get the file (stream)
$data = get_entry_file($id, $column);

if (!isset($data))
{
  // This should not normally happen
  fatal_error(get_vocab('no_data', get_loc_field_name(_tbl('entry'), $column), $id));
}

// Get the MIME type
if (!function_exists('mime_content_type'))
{
  $message = "The function mime_content_type() does not exist. You should enable the fileinfo extension.";
  trigger_error($message, E_USER_WARNING);
  fatal_error(get_vocab("fatal_error"));
}
$type = mime_content_type($data);

if ($type === false)
{
  $type = 'application/octet-stream';  // a standard default
}

// Output the file
http_headers(["Content-Type: $type"]);
fpassthru($data);
