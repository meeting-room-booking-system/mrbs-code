<?php
declare(strict_types=1);

/**
 * This program generates a table of message formatter categories for different locales, allowing one to see which
 * categories are used for different numbers in different locales.
 */

error_reporting(-1);
ini_set('display_errors', '1');

function generate_head(array $values) : string
{
  $html = "<thead>\n<tr>";
  $html .= "<th>Locale</th>";
  foreach ($values as $value)
  {
    $html .= "<th>$value</th>";
  }
  $html .= "</tr>\n</thead>\n";
  return $html;
}


function generate_body(array $locales, array $values) : string
{
  $pattern = "{0, plural, zero {zero} one {one} two {two} few {few} many {many} other {other}}";
  $html = "<tbody>\n";
  foreach($locales as $locale)
  {
    $html .= "<tr>";
    $html .= "<td>$locale</td>";
    $formatter = new MessageFormatter($locale, $pattern);
    foreach ($values as $value)
    {
      $html .= "<td>" . $formatter->format([$value]) . "</td>";

    }
    $html .= "</tr>\n";
  }
  $html .= "</tbody>\n";
  return $html;
}

if (!extension_loaded('intl'))
{
  echo "The intl extension needs to be loaded.";
  exit;
}

$locales = ResourceBundle::getLocales('');
$values = array_merge(range(0, 25), [100, 1000, 10000, 50000]);

echo "<table>";
echo generate_head($values);
echo generate_body($locales, $values);
echo "</table>";
