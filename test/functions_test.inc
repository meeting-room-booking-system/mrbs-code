<?php
declare(strict_types=1);
namespace MRBS;

function thead_html(array $arg_names) : string
{
  $html = "<thead>\n";
  $html .= '<tr>';
  $html .= '<th>function</th>';
  foreach ($arg_names as $arg_name)
  {
    $html .= '<th>$' . escape_html($arg_name) . '</th>';
  }
  $html .= '<th>Result - PHP</th><th>Result - MRBS</th><th>Summary</th>';
  $html .= "<tr>\n";
  $html .= "</thead>\n";

  return $html;
}
