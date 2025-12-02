<?php
declare(strict_types=1);
namespace MRBS;

include 'defaultincludes.inc';

error_reporting(-1);
ini_set('display_errors', '1');

function test_constants()
{
  echo "Testing constants...";
  $passed = true;
  $php_constants = (new \ReflectionClass('Collator'))->getConstants();
  $emulation_constants = (new \ReflectionClass('MRBS\Intl\Collator'))->getConstants();
  foreach ($php_constants as $name => $value)
  {
    if (!isset($emulation_constants[$name]))
    {
      $passed = false;
      echo "Failed to find constant $name<br>\n";
    }
    else if ($value != $emulation_constants[$name])
    {
      $passed = false;
      echo "Constant $name has different value in PHP and MRBS<br>\n";
    }
  }
  if ($passed)
  {
    echo "Passed<br>\n";
  }
}

test_constants();
