<?php
declare(strict_types=1);
namespace MRBS;


include 'defaultincludes.inc';
require_once 'functions_test.inc';

error_reporting(-1);
ini_set('display_errors', '1');

$color_fail = 'pink';
$color_pass = 'palegreen';

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

$loaded_extensions = get_loaded_extensions();

echo "PHP version: " . PHP_VERSION;
echo "<br>\n";
echo "mbstring enabled: " . var_export(in_array('mbstring', $loaded_extensions), true);
echo "<br>\n";
echo "intl enabled: " . var_export(in_array('intl', $loaded_extensions), true);
echo "<br>\n";
echo "<br>\n";

if (!in_array('intl', $loaded_extensions))
{
  die("This test needs the 'intl' PHP extension to be loaded.");
}

test_constants('NumberFormatter');
