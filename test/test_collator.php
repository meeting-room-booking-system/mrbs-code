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


function do_asort(
  string $locale,
  array &$array,
  int $flags=\Collator::SORT_REGULAR,
  int $numeric_collation = \Collator::DEFAULT_VALUE
)
{
  global $color_fail, $color_pass;

  $php_collator = new \Collator($locale);
  $php_collator->setAttribute(\Collator::NUMERIC_COLLATION, $numeric_collation);
  $mrbs_collator = new \MRBS\Intl\Collator($locale);
  $mrbs_collator->setAttribute(\MRBS\Intl\Collator::NUMERIC_COLLATION, $numeric_collation);

  echo "<tr>";
  echo "<td>asort</td>";
  echo "<td>" . escape_html($locale) . "</td>";
  echo "<td>" . implode(',', $array) . "</td>";
  echo "<td>" . escape_html($flags) . "</td>";
  echo "<td>" . $php_collator->getAttribute(\Collator::NUMERIC_COLLATION) . "</td>";

  $php_array = $array;
  $mrbs_array = $array;
  $php_collator->asort($php_array, $flags);
  $mrbs_collator->asort($mrbs_array, $flags);

  echo "<td>[" . implode(',', array_keys($php_array)) . '] [' .  implode(',', array_values($php_array)) . "]</td>";
  echo "<td>[" . implode(',', array_keys($mrbs_array)) . '] [' .  implode(',', array_values($mrbs_array)) . "]</td>";

  // Compare the results
  $passed = ($php_array === $mrbs_array);
  $color = ($passed) ? $color_pass : $color_fail;
  echo '<td style="background-color: ' . $color . '">';
  echo ($passed) ? 'Pass' : 'Fail';
  echo "</td>";

  echo "</tr>\n";
}

function test_asort()
{
  echo "<table>\n";
  echo thead_html(['locale', 'array', 'flags', 'numeric_collation']);
  echo "<tbody>\n";

  $locale = 'en-US';
  $array = ['aò', 'Ao', 'ao'];
  do_asort($locale, $array);

  $locale = 'en-US';
  $array = ['a', 'b', 'A', 'B'];
  do_asort($locale, $array);

  $locale = 'en-US';
  $array = ['aBc', 'abC', 'Abc', 'ABc'];
  do_asort($locale, $array);

  $locale = 'sv';
  $array = ['ö', 'ä', 'å'];
  do_asort($locale, $array);

  $locale = 'sv-SE';
  $array = ['ö', 'ä', 'å'];
  do_asort($locale, $array);

  $locale = 'sv';
  $array = ['ö', 'ä', 'å', 'o', 'a', 'e'];
  do_asort($locale, $array);

  $locale = 'en-US';
  $array = ['a10', 'b2', 'A2', 'B10'];
  do_asort($locale, $array);
  do_asort($locale, $array, \Collator::SORT_NUMERIC);
  $array = ['a1', 'a2', 'a10', 'b2', 'b10'];
  do_asort($locale, $array, \Collator::SORT_NUMERIC);

  $numeric_collation = \Collator::ON;
  $locale = 'fr';
  $array = ['1', '2', '10'];
  do_asort($locale, $array, \Collator::SORT_REGULAR, $numeric_collation);
  do_asort($locale, $array, \Collator::SORT_NUMERIC, $numeric_collation);

  $array = ['a', 'à', 'â', 'e', 'é'];
  do_asort($locale, $array);
  $array = array_reverse($array);
  do_asort($locale, $array);

  $array = ['a', 'A'];
  do_asort($locale, $array);
  $array = array_reverse($array);
  do_asort($locale, $array);

  echo "</tbody>\n";
  echo "</table>\n";
}


function do_compare(string $locale, string $string1, string $string2, int $strength=\Collator::DEFAULT_STRENGTH)
{
  global $color_fail, $color_pass;


  $php_collator = new \Collator($locale);
  $php_collator->setStrength($strength);
  $mrbs_collator = new \MRBS\Intl\Collator($locale);
  $mrbs_collator->setStrength($strength);

  echo "<tr>";
  echo "<td>compare</td>";
  echo "<td>" . escape_html($locale) . "</td>";
  echo "<td>" . escape_html($string1) . "</td>";
  echo "<td>" . escape_html($string2) . "</td>";
  echo "<td>" . escape_html($strength) . "</td>";

  $php_compare = $php_collator->compare($string1, $string2);
  $mrbs_compare = $mrbs_collator->compare($string1, $string2);

  echo "<td>$php_compare</td>";
  echo "<td>$mrbs_compare</td>";

  // Compare the results
  $passed = ($php_compare === $mrbs_compare);
  $color = ($passed) ? $color_pass : $color_fail;
  echo '<td style="background-color: ' . $color . '">';
  echo ($passed) ? 'Pass' : 'Fail';
  echo "</td>";

  echo "</tr>\n";
}


function test_compare()
{
  echo "<table>\n";
  echo thead_html(['locale', 'string1', 'string2', 'strength']);
  echo "<tbody>\n";

  $locale = 'fr';
  $string1 = 'é';
  $string2 = 'è';
  do_compare($locale, $string1, $string2, \Collator::PRIMARY);
  do_compare($locale, $string1, $string2, \Collator::SECONDARY);
  do_compare($locale, $string1, $string2, \Collator::TERTIARY);
  do_compare($locale, $string1, $string2, \Collator::QUATERNARY);
  do_compare($locale, $string1, $string2, \Collator::IDENTICAL);

  $locale = 'fr';
  $string1 = 'è';
  $string2 = 'é';
  do_compare($locale, $string1, $string2, \Collator::PRIMARY);
  do_compare($locale, $string1, $string2, \Collator::SECONDARY);
  do_compare($locale, $string1, $string2, \Collator::TERTIARY);
  do_compare($locale, $string1, $string2, \Collator::QUATERNARY);
  do_compare($locale, $string1, $string2, \Collator::IDENTICAL);

  $locale = 'en-GB';
  $string1 = 'é';
  $string2 = 'è';
  do_compare($locale, $string1, $string2, \Collator::PRIMARY);
  do_compare($locale, $string1, $string2, \Collator::SECONDARY);
  do_compare($locale, $string1, $string2, \Collator::TERTIARY);
  do_compare($locale, $string1, $string2, \Collator::QUATERNARY);
  do_compare($locale, $string1, $string2, \Collator::IDENTICAL);

  $locale = 'en';
  $string1 = 'Séan';
  $string2 = 'Sean';
  do_compare($locale, $string1, $string2, \Collator::PRIMARY);
  do_compare($locale, $string1, $string2, \Collator::SECONDARY);
  do_compare($locale, $string1, $string2, \Collator::TERTIARY);
  do_compare($locale, $string1, $string2, \Collator::QUATERNARY);
  do_compare($locale, $string1, $string2, \Collator::IDENTICAL);

  $locale = 'en';
  $string1 = 'a';
  $string2 = 'A';
  do_compare($locale, $string1, $string2, \Collator::PRIMARY);
  do_compare($locale, $string1, $string2, \Collator::SECONDARY);
  do_compare($locale, $string1, $string2, \Collator::TERTIARY);
  do_compare($locale, $string1, $string2, \Collator::QUATERNARY);
  do_compare($locale, $string1, $string2, \Collator::IDENTICAL);

  $locale = 'en';
  $string1 = 'A';
  $string2 = 'a';
  do_compare($locale, $string1, $string2, \Collator::PRIMARY);
  do_compare($locale, $string1, $string2, \Collator::SECONDARY);
  do_compare($locale, $string1, $string2, \Collator::TERTIARY);
  do_compare($locale, $string1, $string2, \Collator::QUATERNARY);
  do_compare($locale, $string1, $string2, \Collator::IDENTICAL);


  echo "</tbody>\n";
  echo "</table>\n";
}

test_constants();
test_asort();
test_compare();
