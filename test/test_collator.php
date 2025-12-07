<?php
declare(strict_types=1);
namespace MRBS;

include 'defaultincludes.inc';
require_once 'functions_test.inc';

error_reporting(-1);
ini_set('display_errors', '1');

$color_fail = 'pink';
$color_pass = 'palegreen';

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


function do_asort(string $locale, array &$array, int $flags)
{
  global $color_fail, $color_pass;

  echo "<tr>";
  echo "<td>asort</td>";
  echo "<td>" . escape_html($locale) . "</td>";
  echo "<td>" . implode(',', $array) . "</td>";
  echo "<td>" . escape_html($flags) . "</td>";

  $php_array = $array;
  $mrbs_array = $array;
  $php_collator = new \Collator($locale);
  $mrbs_collator = new \MRBS\Intl\Collator($locale);
  $php_collator->asort($php_array, $flags);
  $mrbs_collator->asort($mrbs_array, $flags);

  echo "<td>" . implode(',', $php_array) . "</td>";
  echo "<td>" . implode(',', $mrbs_array) . "</td>";
  // Compare the results
  $passed = ($php_array === $mrbs_array);;
  $color = ($passed) ? $color_pass : $color_fail;
  echo '<td style="background-color: ' . $color . '">';
  echo ($passed) ? 'Pass' : 'Fail';
  echo "</td>";

  echo "</tr>\n";
}

function test_asort()
{
  echo "<table>\n";
  echo thead_html(['locale', 'array', 'flags']);
  echo "<tbody>\n";

  $locale = 'en-US';
  $array = ['aò', 'Ao', 'ao'];
  $flags = \Collator::SORT_REGULAR;
  do_asort($locale, $array, $flags);

  echo "</tbody>\n";
  echo "</table>\n";
}


test_constants();
test_asort();
