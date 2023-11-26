<?php
declare(strict_types=1);
namespace MRBS;

require "defaultincludes.inc";

$column = get_form_var('column', 'string');
$id = get_form_var('id', 'int');

$sql = "SELECT $column
          FROM " . _tbl('entry') . "
         WHERE id=:id";
$data = db()->query_lob1($column, $sql, [':id' => $id]);

// TODO: handle case when mime_content_type doesn't exist
$type = mime_content_type($data);
header("Content-Type: $type");
fpassthru($data);
