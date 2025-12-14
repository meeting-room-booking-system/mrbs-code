<?php
declare(strict_types=1);
namespace MRBS;


function test_constants($class)
{
  echo "<h1>Testing constants</h1>\n";
  $passed = true;
  $php_constants = (new \ReflectionClass($class))->getConstants();
  $emulation_constants = (new \ReflectionClass("MRBS\Intl\\$class"))->getConstants();
  foreach ($php_constants as $name => $value)
  {
    // We are only interested in public constants
    if (!(new \ReflectionClassConstant($class, $name))->isPublic())
    {
      continue;
    }

    if (!isset($emulation_constants[$name]))
    {
      $passed = false;
      echo "Failed to find constant $name ($value)<br>\n";
    }
    else if ($value != $emulation_constants[$name])
    {
      $passed = false;
      echo "Constant $name has different value in PHP ($value) and MRBS ($emulation_constants[$name])<br>\n";
    }
  }
  if ($passed)
  {
    echo "Passed<br>\n";
  }
}


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
