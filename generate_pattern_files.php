<?php
namespace MRBS;

use IntlDateFormatter;
use IntlDatePatternGenerator;

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
    fwrite($fp, "$locale = \"$pattern\"\n");
    // Fix up for some locales
    if (($locale == 'zh-Hans-CN') && !in_array('zh-CN', $locales)) {
      fwrite($fp, "zh-CN = \"$pattern\"\n");
    }
    if (($locale == 'zh-Hant-TW') && !in_array('zh-TW', $locales)) {
      fwrite($fp, "zh-TW = \"$pattern\"\n");
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
      fwrite($fp, "$locale = \"$pattern\"\n");
      // Fix up for some locales
      if (($locale == 'zh-Hans-CN') && !in_array('zh-CN', $locales)) {
        fwrite($fp, "zh-CN = \"$pattern\"\n");
      }
      if (($locale == 'zh-Hant-TW') && !in_array('zh-TW', $locales)) {
        fwrite($fp, "zh-TW = \"$pattern\"\n");
      }
    }
    fclose($fp);
    echo " done<br>\n";
  }
}
