<?php
namespace MRBS;

use IntlDateFormatter;
use IntlDatePatternGenerator;

function write_line($fp, string $key, string $value) : void
{
  $keywords = ['no', 'yes', 'true', 'false', 'on', 'off', 'null', 'none'];
  if (in_arrayi($key, $keywords))
  {
    // If it's a keyword we put a space before the key because it is a keyword and otherwise
    // parse_ini_file() will fail.  The space is trimmed when it is parsed.
    // See https://stackoverflow.com/questions/6142980/using-a-keyword-as-variable-name-in-an-ini-file
    $key = " $key";
    $comment = "; The following line has a space before the key because it is a keyword\n";
    fwrite($fp, $comment);
  }
  fwrite($fp, "$key = \"$value\"\n");
}


if (!extension_loaded('intl'))
{
  die("The 'intl' extension needs to be loaded.");
}

require "defaultincludes.inc";

$dir = 'tmp';

$locales = \ResourceBundle::getLocales('');

echo "<h2>Skeleton files</h2>\n";

$skeletons = array(
  'd',
  'dEMMM',
  'dMMM',
  'dMMMM',
  'MMMMy'
);

foreach ($skeletons as $skeleton) {
  $filename = "$dir/skeletons/$skeleton.ini";
  echo "Generating $filename ...";
  $fp = fopen($filename, 'w');
  foreach ($locales as $locale)
  {
    $locale = convert_to_BCP47($locale);
    $pattern_generator = new IntlDatePatternGenerator($locale);
    $pattern = $pattern_generator->getBestPattern($skeleton);
    write_line($fp, $locale, $pattern);
    // Fix up for some locales
    if (($locale == 'zh-Hans-CN') && !in_array('zh-CN', $locales)) {
      write_line($fp, 'zh-CN', $pattern);
    }
    if (($locale == 'zh-Hant-TW') && !in_array('zh-TW', $locales)) {
      write_line($fp, 'zh-TW', $pattern);
    }
  }
  fclose($fp);
  echo " done<br>\n";
}

echo "<h2>Type files</h2>\n";

$types = array(
  'full' => IntlDateFormatter::FULL,
  'long' => IntlDateFormatter::LONG,
  'medium' => IntlDateFormatter::MEDIUM,
  'short' => IntlDateFormatter::SHORT,
  'none' => IntlDateFormatter::NONE
);

foreach ($types as $date_key => $date_value)
{
  foreach ($types as $time_key => $time_value)
  {
    if (($date_value === IntlDateFormatter::NONE) && ($time_value === IntlDateFormatter::NONE))
    {
      continue;
    }
    $filename = "$dir/types/{$date_key}_{$time_key}.ini";
    echo "Generating $filename ...";
    $fp = fopen($filename, 'w');
    foreach ($locales as $locale) {
      $locale = convert_to_BCP47($locale);
      $formatter = new IntlDateFormatter($locale, $date_value, $time_value);
      $pattern = $formatter->getPattern();
      write_line($fp, $locale, $pattern);
      // Fix up for some locales
      if (($locale == 'zh-Hans-CN') && !in_array('zh-CN', $locales)) {
        write_line($fp, 'zh-CN', $pattern);
      }
      if (($locale == 'zh-Hant-TW') && !in_array('zh-TW', $locales)) {
        write_line($fp, 'zh-TW', $pattern);
      }
    }
    fclose($fp);
    echo " done<br>\n";
  }
}
