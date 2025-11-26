<?php
declare(strict_types=1);
namespace MRBS;

use IntlDateFormatter;
use MRBS\Intl\IntlDateFormatterFactory;
use MRBS\Intl\IntlDatePatternGenerator;


require_once "functions.inc";

// Get localized field name for a user defined table column
// Looks for a tag of the format tablename.columnname (where tablename is
// stripped of the table prefix) and if can't find a string for that tag will
// return the column name
function get_loc_field_name(string $table, string $name) : string
{
  global $vocab;

  $tag = get_table_short_name($table) . ".$name";

  // If there's a string in the vocab array for $tag use that,
  // otherwise just use the fieldname
  return (isset($vocab[$tag])) ? get_vocab($tag) : $name;
}


function datetime_format(array $format, ?int $timestamp=null, ?string $locale=null) : string
{
  global $icu_override, $timezone;

  if (!isset($timestamp))
  {
    $timestamp = time();
  }

  if (!isset($locale))
  {
    $locale = Language::getInstance()->getWebLocale();
  }

  if (isset($format['skeleton']))
  {
    $pattern_generator = new IntlDatePatternGenerator($locale);
    $pattern = $pattern_generator->getBestPattern($format['skeleton']);
  }

  if (!isset($pattern) || ($pattern === false) || ($pattern === ''))
  {
    $pattern = $format['pattern'] ?? null;
  }

  $formatter = IntlDateFormatterFactory::create(
    $locale,
    $format['date_type'] ?? IntlDateFormatter::FULL,
    $format['time_type'] ?? IntlDateFormatter::FULL,
    null, null, $pattern
  );

  // If we're overriding the ICU value(s) then use those, provided that we're able to make use
  // of them with the IntlCalendar and (standard, not emulated - hence the check for the loaded
  // extension, rather than whether the class exists, which it always will) IntlDateFormatter classes.
  if (isset($icu_override[$locale]) && extension_loaded('intl') && class_exists('IntlCalendar'))
  {
    $cal = \IntlCalendar::createInstance($timezone, $locale);
    {
      if (isset($icu_override[$locale]['first_day_of_week']))
      {
        $cal->setFirstDayOfWeek($icu_override[$locale]['first_day_of_week']);
      }
      if (isset($icu_override[$locale]['minimal_days_in_first_week']))
      {
        $cal->setMinimalDaysInFirstWeek($icu_override[$locale]['minimal_days_in_first_week']);
      }
    }
    $cal->setTime($timestamp * 1000);
    return $formatter->format($cal);
  }

  return $formatter->format($timestamp);
}


// Determines whether a locale (default the current locale) uses a 12-hour clock.
// Returns NULL if it can't be determined.
function is_ampm(?string $locale=null) : ?bool
{
  global $datetime_formats;

  if (!isset($locale))
  {
    $locale = Language::getInstance()->getWebLocale();
  }

  // Work out what pattern is being used for times.
  // If an explicit pattern has been set in the config file then use that.
  if (isset($datetime_formats['time']['pattern']))
  {
    $pattern = $datetime_formats['time']['pattern'];
  }
  // Otherwise get the pattern that would be used by default.
  else
  {
    $formatter = IntlDateFormatterFactory::create(
      $locale,
      $format['date_type'] ?? IntlDateFormatter::NONE,
      $format['time_type'] ?? IntlDateFormatter::SHORT
    );
    $pattern = $formatter->getPattern();
    if ($pattern === false)
    {
      trigger_error("Could not determine whether '$locale' uses a 12-hour clock");
      return null;
    }
  }

  // And see if it contains an 'a'
  return str_contains($pattern, 'a');
}
