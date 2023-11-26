<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";

$column = get_form_var('column', 'string');
$id = get_form_var('id', 'int');

$sql = "SELECT $column
          FROM " . _tbl('entry') . "
         WHERE id=:id";
$data = db()->query_lob1($sql, [':id' => $id]);

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
