<?php
declare(strict_types=1);
namespace MRBS;

// Program for testing the mbstring function emulations.  Run it in the MRBS directory on a
// system with the 'mbstring' extension enabled.

use IntlChar;
use MRBS\Mbstring\Mbstring;
use Throwable;

include 'defaultincludes.inc';

error_reporting(-1);
ini_set('display_errors', '1');
ini_set('max_execution_time', '120');

$color_fail = 'pink';
$color_pass = 'palegreen';

$intl_loaded = method_exists('\IntlChar', 'charName');
$max_codepoint = 0x10FFFF;


function test_chr() : void
{
  global $color_fail, $intl_loaded, $max_codepoint;

  $n_passed = 0;
  $failures = [];

  for ($i =0; $i<=$max_codepoint; $i++)
  {
    $mb = mb_chr($i, 'UTF-8');
    $mrbs = Mbstring::mb_chr($i, 'UTF-8');
    if ($mb === $mrbs)
    {
      $n_passed++;
    }
    else
    {
      $failures[] = [$i, $mb, $mrbs];
    }
  }

  echo "<p>$n_passed codepoints passed, " . count ($failures) . " failed.</p>\n";

  if (!empty($failures))
  {
    echo "<table>\n";
    echo "<thead>\n";
    echo '<tr>';
    echo '<th colspan="' . (($intl_loaded) ? 2 : 1) . '">Codepoint</th>';
    echo '<th>mbstring</th><th>mrbs</th><th>Summary</th>';
    echo "</tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";

    foreach ($failures as $failure)
    {
      echo '<tr>';
      if ($intl_loaded)
      {
        echo '<td>' . IntlChar::charName($failure[0]) . '</td>';
      }
      foreach ($failure as $value)
      {
        echo "<td>$value</td>";
      }
      echo '<td style="background-color: ' . $color_fail . '">Fail</td>' . "\n";
      echo "</tr>\n";
    }

    echo "</tbody>\n";
    echo "</table>\n";
  }
}


function test_ord() : void
{
  global $color_fail, $intl_loaded, $max_codepoint;

  $n_passed = 0;
  $failures = [];

  for ($i =0; $i<=$max_codepoint; $i++)
  {
    $str = mb_chr($i, 'UTF-8');
    if (($str !== false) && mb_check_encoding($str, 'UTF-8'))
    {
      $mrbs_ord = Mbstring::mb_ord($str);
      if ($mrbs_ord === mb_ord($str))
      {
        $n_passed++;
      }
      else
      {
        $failures[] = [$str, $i, $mrbs_ord];
      }
    }
  }

  echo "<p>$n_passed codepoints passed, " . count ($failures) . " failed.</p>\n";

  if (!empty($failures))
  {
    echo "<table>\n";
    echo "<thead>\n";
    echo '<tr>';
    echo '<th colspan="' . (($intl_loaded) ? 2 : 1) . '">Char</th>';
    echo '<th>mbstring</th><th>mrbs</th><th>Summary</th>';
    echo "</tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";

    foreach ($failures as $failure)
    {
      echo '<tr>';
      if ($intl_loaded)
      {
        echo '<td>' . IntlChar::charName(mb_ord($failure[0])) . '</td>';
      }
      foreach ($failure as $value)
      {
        echo "<td>$value</td>";
      }
      echo '<td style="background-color: ' . $color_fail . '">Fail</td>' . "\n";
      echo "</tr>\n";
    }

    echo "</tbody>\n";
    echo "</table>\n";
  }
}


function codepoint_notation(int $codepoint) : string
{
  // OK to user strtoupper here instead of mb_ because we're only looking at the hex characters
  return 'U+' . str_pad(strtoupper(dechex($codepoint)), 4, '0', STR_PAD_LEFT);
}


function thead_html(array $function_arg_names) : string
{
  $html = "<thead>\n";
  $html .= '<tr>';
  $html .= '<th>function</th>';
  foreach ($function_arg_names as $name)
  {
    $html .= '<th>$' . $name . '</th>';
  }
  $html .= '<th>result - mbstring</th><th>result - mrbs</th><th>Summary</th>';
  $html .= "<tr>\n";
  $html .= "</thead>\n";

  return $html;
}


function test(string $function, $args) : void
{
  global $color_fail, $color_pass;

  echo "<tr>";
  echo "<td>$function</td>";

  foreach ($args as $arg)
  {
    echo "<td>$arg</td>";
  }

  // Using the mbstring versions
  echo "<td>";
  try {
    $mbstring = call_user_func_array($function, $args);
  }
  catch (Throwable $t) {
    $mbstring = get_class($t);
  }
  echo var_export($mbstring, true);
  echo "</td>";

  // Using the MRBS emulations
  echo "<td>";
  try {
    $mrbs = call_user_func_array([__NAMESPACE__ . "\\Mbstring\\Mbstring", $function], $args);
  }
  catch (Throwable $t) {
    $mrbs = get_class($t);
  }
  echo var_export($mrbs, true);
  echo "</td>";

  // Compare the results
  $color = ($mbstring === $mrbs) ? $color_pass : $color_fail;
  echo '<td style="background-color: ' . $color . '">';
  echo ($mbstring === $mrbs) ? 'Pass' : 'Fail';
  echo "</td>";

  echo "</tr>\n";
}


function test_strlen() : void
{
  echo "<table>\n";
  echo thead_html(['string', 'encoding']);
  echo "<tbody>\n";

  // Simple case
  test('mb_strlen', ['abcd', 'UTF-8']);
  // Multibyte
  test('mb_strlen', ['會議室預約系統', 'UTF-8']);
  test('mb_strlen', ['emojis 😀😨🙁', 'UTF-8']);
  // Empty string
  test('mb_strlen', ['', 'UTF-8']);

  // 8bit testing
  test('mb_strlen', ['', '8bit']);
  test('mb_strlen', ['&', '8bit']);
  test('mb_strlen', ['å', '8bit']);
  test('mb_strlen', ['議', '8bit']);
  test('mb_strlen', ['👽', '8bit']);
  test('mb_strlen', ['z👽', '8bit']);
  test('mb_strlen', ['åäö', '8bit']);
  test('mb_strlen', ['👽統', '8bit']);
  test('mb_strlen', ['👿🤩', '8bit']);
  test('mb_strlen', ['系統åg', '8bit']);

  echo "</tbody>\n";
  echo "</table>\n";
}


function test_all_codepoints(string $function) : void
{
  global $color_fail, $intl_loaded, $max_codepoint;

  echo "<h3>Testing all codepoints</h3>\n";

  $n_passed = 0;
  $failures = [];

  for ($i =0; $i<=$max_codepoint; $i++)
  {
    $str = mb_chr($i, 'UTF-8');
    if (($str !== false) && mb_check_encoding($str, 'UTF-8'))
    {
      $mb = call_user_func($function, $str);
      $mrbs = call_user_func([__NAMESPACE__ . "\\Mbstring\\Mbstring", $function], $str);
      if ($mb === $mrbs)
      {
        $n_passed++;
      }
      else
      {
        $failures[] = [$str, $mb, $mrbs];
      }
    }
  }

  echo "<p>$n_passed codepoints passed, " . count ($failures) . " failed.</p>\n";

  if (!empty($failures))
  {
    echo "<table>\n";
    echo "<thead>\n";
    echo '<tr>';
    echo '<th colspan="' . (($intl_loaded) ? 3 : 2) . '">Codepoint</th>';
    echo '<th colspan="2">mbstring</th><th colspan="2">mrbs</th><th>Summary</th>';
    echo "</tr>\n";
    echo "</thead>\n";
    echo "<tbody>\n";

    foreach ($failures as $failure)
    {
      echo '<tr>';
      if ($intl_loaded)
      {
        echo '<td>' . IntlChar::charName(mb_ord($failure[0])) . '</td>';
      }
      foreach ($failure as $char)
      {
        echo "<td>$char</td><td>" . codepoint_notation(mb_ord($char)) . '</td>';
      }
      echo '<td style="background-color: ' . $color_fail . '">Fail</td>' . "\n";
      echo "</tr>\n";
    }

    echo "</tbody>\n";
    echo "</table>\n";
  }
}

function test_strtolower() : void
{
  echo "<table>\n";
  echo thead_html(['string']);
  echo "<tbody>\n";

  // Empty string
  test('mb_strtolower', ['']);
  // Simple string
  test('mb_strtolower', ['ABcDeFgHI']);
  // More complex
  test('mb_strtolower', ['AÅÄÖ']);
  // Turkish characters
  test('mb_strtolower', ['CÇGĞIİSŞ']);
  test('mb_strtolower', ['İ']);
  // Other
  test('mb_strtolower', ['Τάχιστη αλώπηξ βαφής']);
  test('mb_strtolower', ['👽系😨z😎éÉ']);

  echo "</tbody>\n";
  echo "</table>\n";

  test_all_codepoints('mb_strtolower');
}


function test_strtoupper() : void
{
  echo "<table>\n";
  echo thead_html(['string']);
  echo "<tbody>\n";

  // Empty string
  test('mb_strtoupper', ['']);
  // Simple string
  test('mb_strtoupper', ['ABcDeFgHI']);
  // More complex
  test('mb_strtoupper', ['aåäö']);
  // Turkish characters
  test('mb_strtoupper', ['cçgğiiı̇sş']);
  // Other
  test('mb_strtoupper', ['Τάχιστη αλώπηξ βαφής']);
  test('mb_strtoupper', ['👽系😨z😎éÉ']);
  // These fail with Transliterator
  test('mb_strtoupper', ['ƛɤ']);

  echo "</tbody>\n";
  echo "</table>\n";

  test_all_codepoints('mb_strtoupper');
}


function test_substr() : void
{
  echo "<table>\n";
  echo thead_html(['string', 'start', 'length']);
  echo "<tbody>\n";

  // Empty string
  test('mb_substr', ['', 0, null]);
  test('mb_substr', ['', 1, null]);
  test('mb_substr', ['', 0, 2]);
  test('mb_substr', ['', 1, 2]);

  // Multibyte
  test('mb_substr', ['👽系😨z😎é', 0, null]);
  test('mb_substr', ['👽系😨z😎é', 2, null]);
  test('mb_substr', ['👽系😨z😎é', 2, 1]);
  test('mb_substr', ['👽系😨z😎é', 2, -1]);
  test('mb_substr', ['👽系😨z😎é', 2, -5]);
  test('mb_substr', ['👽系😨z😎é', -1, null]);
  test('mb_substr', ['👽系😨z😎é', -3, -1]);
  test('mb_substr', ['👽系😨z😎é', -9, -1]);
  test('mb_substr', ['👽系😨z😎é', -9, -12]);

  echo "</tbody>\n";
  echo "</table>\n";
}


function test_pos() : void
{
  echo "<table>\n";
  echo thead_html(['haystack', 'needle', 'offset']);
  echo "<tbody>\n";

  // mb_strpos()
  // -----------

  test('mb_strpos', ['0123456789a0123456789b0123456789c', 'c', 0]);
  test('mb_strpos', ['0123456789a0123456789b0123456789c', 'd', 0]);
  test('mb_strpos', ['0123456789a0123456789b0123456789c', 'c', -1]);
  test('mb_strpos', ['0123456789a0123456789b0123456789c', 'c', -2]);

  test('mb_strpos', ['TRUE', 'E_', 0]);

  // Equivalence
  test('mb_strpos', ['Jour précédent', 'e', 0]);
  $old_locale = setlocale(LC_ALL, '0');
  setlocale(LC_ALL, ['fr_FR', 'fr']);
  test('mb_strpos', ['Jour précédent', 'e', 0]);
  setlocale(LC_ALL, $old_locale);


  // mb_stripos()
  // -----------

  test('mb_stripos', ['0123456789a0123456789b0123456789c', 'c', -1]);
  test('mb_stripos', ['0123456789a0123456789b0123456789c', 'C', -1]);

  // Multibyte
  test('mb_stripos', ['會C議室預約系統', 'C', 2]);
  test('mb_stripos', ['會議C室預約系統', 'C', 2]);
  test('mb_stripos', ['會議C室預約系統', '預約', 2]);


  // mb_strrpos()
  // ------------

  // Positive offsets, needle at start
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '0123456789a', 0]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '0123456789a', 1]);

  // Positive offsets, needle partial match at the end
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '89cd', 10]);

  // Positive offsets, needle longer than search area
  test('mb_strrpos', ['abcde', 'cde', 2]);
  test('mb_strrpos', ['abcde', 'cde', 3]);

  // Negative offsets, needle longer than search area
  test('mb_strrpos', ['abcdefg', 'cdefghi', -2]);
  test('mb_strrpos', ['abcdefg', 'cdefghij', -2]);

  // Negative offsets, needle in the middle of haystack
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '234', 0]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '234', -1]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '234', -2]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '234', -3]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '234', -9]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '234', -10]);

  // Negative offsets, needle at the end of haystack
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789c', 0]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789c', -1]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789c', -2]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789c', -3]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789c', -4]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789c', -5]);

  // Negative offsets, needle partial match at the end
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789cd', 0]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789cd', -1]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789cd', -2]);
  test('mb_strrpos', ['0123456789a0123456789b0123456789c', '789cd', -3]);

  // Multibyte
  test('mb_strrpos', ['會🙂議C室🙂預約系統', '🙂', -1]);

  // Empty haystack
  test('mb_strrpos', ['', 'A', 0]);

  // Case sensitivity
  test('mb_strrpos', ['AaBb', 'a', 0]);
  test('mb_strrpos', ['AaBb', 'A', 0]);

  // Offset outside haystack
  test('mb_strrpos', ['', 'A', 1]);
  test('mb_strrpos', ['A', 'A', 2]);
  test('mb_strrpos', ['A', 'A', -2]);


  // mb_strripos()
  // ------------

  // Case sensitivity
  test('mb_strripos', ['AaBb', 'a', 0]);
  test('mb_strripos', ['AaBb', 'A', 0]);


  echo "</tbody>\n";
  echo "</table>\n";
}

echo "<h1>mbstring emulation tests</h1>\n";

$loaded_extensions = get_loaded_extensions();

echo "PHP version: " . PHP_VERSION;
echo "<br>\n";
echo "mbstring enabled: " . var_export(in_array('mbstring', $loaded_extensions), true);
echo "<br>\n";
echo "intl enabled: " . var_export(in_array('intl', $loaded_extensions), true);
echo "<br>\n";
echo "iconv enabled: " . var_export(in_array('iconv', $loaded_extensions), true);
echo "<br>\n";
echo "<br>\n";

if (!in_array('mbstring', $loaded_extensions))
{
  die("This test needs the 'mbstring' PHP extension to be loaded.");
}

echo "<h2>mb_chr()</h2>\n";
test_chr();

echo "<h2>mb_ord()</h2>\n";
test_ord();

echo "<h2>mb_strlen()</h2>\n";
test_strlen();

echo "<h2>mb_strtolower()</h2>\n";
test_strtolower();

echo "<h2>mb_strtoupper()</h2>\n";
test_strtoupper();

echo "<h2>mb_substr()</h2>\n";
test_substr();

echo "<h2>mb_*pos()</h2>\n";
test_pos();
