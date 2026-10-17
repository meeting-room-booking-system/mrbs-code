<?php
declare(strict_types=1);
namespace MRBS;


include 'defaultincludes.inc';
require_once 'functions_test.inc';

error_reporting(-1);
ini_set('display_errors', '1');

$color_fail = 'pink';
$color_pass = 'palegreen';


function do_format(string $locale, string $pattern, array $values)
{
  global $color_fail, $color_pass;

  $php_formatter = new \MessageFormatter($locale, $pattern);
  $mrbs_formatter = new \MRBS\Intl\MessageFormatter($locale, $pattern);

  echo "<tr>";
  echo "<td>compare</td>";
  echo "<td>" . escape_html($locale) . "</td>";
  echo "<td>" . escape_html($pattern) . "</td>";
  echo "<td>" . escape_html(json_encode($values)) . "</td>";

  $php_format= $php_formatter->format($values);
  $mrbs_format = $mrbs_formatter->format($values);

  echo "<td>$php_format</td>";
  echo "<td>$mrbs_format</td>";

  // Compare the results
  $passed = ($php_format === $mrbs_format);
  $color = ($passed) ? $color_pass : $color_fail;
  echo '<td style="background-color: ' . $color . '">';
  echo ($passed) ? 'Pass' : 'Fail';
  echo "</td>";

  echo "</tr>\n";
}


function test_format()
{
  echo "<h1>Testing format()</h1>\n";

  echo "<table>\n";
  echo thead_html(['locale', 'pattern', 'values']);
  echo "<tbody>\n";

  do_format('en', "{0,number,integer} monkeys on {1,number,integer} trees", [4560, 123]);
  do_format('de', "{0,number,integer} Affen auf {1,number,integer} Bäumen", [4560, 123]);
  do_format('ar', "{0,number,integer} monkeys on {1,number,integer} trees", [4560, 123]);
  do_format('fa', "{0,number,integer} monkeys on {1,number,integer} trees", [4560, 123]);

  echo "</tbody>\n";
  echo "</table>\n";
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

test_format();
