<?php
declare(strict_types=1);
namespace MRBS;

use IntlDateFormatter;
use MRBS\Errors\Errors;
use MRBS\Intl\IntlDateFormatterFactory;
use MRBS\Intl\IntlDatePatternGenerator;
use MRBS\Intl\Locale;

require_once "functions.inc";


function set_mrbs_locale($locale)
{
  if (!isset($locale))
  {
    return;
  }

  $os_locale = System::getOSlocale($locale);

  if (false === setlocale(LC_ALL, $os_locale))
  {
    // $os_locale will be an array
    $message = "Server failed to set locale to " . json_encode($os_locale) .
               " for language tag '$locale'.  Either install the missing locale" .
               ' or set $override_locale in your MRBS config.inc.php file to a' .
               ' locale that is available on your server.';
    trigger_error($message, E_USER_NOTICE);

    if (false === setlocale(LC_ALL, array('C.UTF-8', 'C.utf-8', 'C.utf8', 'C')))
    {
      Errors::fatalError("Could not set locale at all, not even to 'C'");
    }
  }
}


// Gets all the language tags in a directory where the filenames are of the format
// $prefix . $lang . $suffix.  Returns an array.
function get_langtags(string $dir, string $prefix='', string $suffix='') : array
{
  $result = array();

  if (!is_dir($dir))
  {
    trigger_error("MRBS: directory '$dir' does not exist", E_USER_NOTICE);
    return $result;
  }

  $files = scandir($dir);

  foreach ($files as $file)
  {
    $path = $dir . '/' . $file;
    // . and .. will be included in the output of scandir(), so
    // we need to exclude them.  We also want to exclude files
    // that we can't read.
    if (!is_dir($path) && is_readable($path))
    {
      // Then strip out the language tag from the file name
      $pattern = sprintf('/%s(.+)%s/i', $prefix, $suffix);
      if (preg_match($pattern, $file, $matches))
      {
        if (isset($matches[1]))
        {
          $result[] = $matches[1];
        }
      }
    }
  }

  return $result;
}


function load_vocab($lang)
{
  global $vocab, $vocab_override;

  if (!isset($lang))
  {
    return;
  }

  $lang = mb_strtolower($lang);
  $lang_file = MRBS_ROOT . "/lang/lang.$lang";

  if (!is_readable($lang_file))
  {
    trigger_error("MRBS: could not set language to '$lang'", E_USER_WARNING);
  }
  else
  {
    // Load the language tokens
    include "$lang_file";
    // And apply any site overrides for this language
    if (isset($vocab_override[$lang]))
    {
      foreach ($vocab_override[$lang] as $tag => $str)
      {
        $vocab[$tag] = $str;
      }
    }
  }
}


// Set $vocab to $lang.  As some translations are incomplete, 'en' is set as a final
// fallback language, with $default_language_tokens as a fallback before that.
function set_vocab($lang)
{
  global $default_language_tokens;

  // Set English as a final fallback language as some of the translations are incomplete.
  load_vocab('en');

  // Then set the default language as the fallback before that.
  if (isset($default_language_tokens) && ($default_language_tokens !== 'en'))
  {
    load_vocab($default_language_tokens);
  }

  // Finally set the language we want
  if (isset($lang) && !in_array($lang, array('en', $default_language_tokens)))
  {
    load_vocab($lang);
  }
}


/**
 * A wrapper for Language::getVocab() to avoid (a) having to replace
 * get_vocab() everywhere, and (b) having to use the long method call.
 */
function get_vocab(string $tag, ...$values) : string
{
  return Language::getInstance()->getVocab($tag, Language::getInstance()->getWebLang(), ...$values);
}


/**
 * A wrapper for Language::getVocab() to avoid (a) having to replace
 * get_vocab() everywhere, and (b) having to use the long method call.
 */
function get_mail_vocab(string $tag, ...$values) : string
{
  return Language::getInstance()->getVocab($tag, Language::getInstance()->getMailLang(), ...$values);
}


// Same as get_vocab(), but escapes the result for use in JavaScript
function get_js_vocab(string $tag, ...$values) : string
{
  return escape_js(get_vocab($tag, ...$values));
}


// Get localised booking type name
function get_type_vocab(string $type) : string
{
  return get_vocab("type.$type");
}


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
