<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Check the user is authorised for this page
if (!checkAuthorised(this_page(), true))
{
  http_response_code(403);
  exit;
}

// TODO: check user can view this entry (make common with view_entry??)
// TODO: check file is not private


$column = get_form_var('column', 'string');
$id = get_form_var('id', 'int');

// Get the file (stream)
$data = get_entry_file($id, $column);

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
