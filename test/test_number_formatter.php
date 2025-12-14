<?php
declare(strict_types=1);
namespace MRBS;


include 'defaultincludes.inc';
require_once 'functions_test.inc';

error_reporting(-1);
ini_set('display_errors', '1');

$color_fail = 'pink';
$color_pass = 'palegreen';


function do_format(string $locale, $num, int $style)
{
  global $color_fail, $color_pass;

  $php_formatter = new \NumberFormatter($locale, $style);
  $mrbs_formatter = new \MRBS\Intl\NumberFormatter($locale, $style);

  echo "<tr>";
  echo "<td>compare</td>";
  echo "<td>" . escape_html($locale) . "</td>";
  echo "<td>" . escape_html($num) . "</td>";
  echo "<td>" . escape_html($style) . "</td>";

  $php_format= $php_formatter->format($num);
  $mrbs_format = $mrbs_formatter->format($num);

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
  echo thead_html(['locale', 'num', 'style']);
  echo "<tbody>\n";

  do_format('en', 1000000, \NumberFormatter::DEFAULT_STYLE);
  do_format('fr', 1000000, \NumberFormatter::DEFAULT_STYLE);
  do_format('de', 1000000, \NumberFormatter::DEFAULT_STYLE);

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

test_constants('NumberFormatter');
test_format();
