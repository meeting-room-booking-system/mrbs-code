<?php
declare(strict_types=1);
namespace MRBS;

// Note that we are using MRBS\Intl\Locale because \Locale has some bugs in it (and also
// isn't always installed).

use IntlDateFormatter;
use MRBS\Errors\Errors;
use MRBS\Intl\IntlDateFormatterFactory;
use MRBS\Intl\IntlDatePatternGenerator;
use MRBS\Intl\Locale;
use PHPMailer\PHPMailer\PHPMailer;

require_once "functions.inc";



// A list of languages that use Right to Left text
$rtl_languages = array(
  'he'
);

// Determine locale and language to use.  MRBS's strategy is first to find the locale
// that best fits the config settings and the user's browser preferences.  Then it
// finds a language that best fits the locale, subject to any overriding config settings.
//
// Locales will also be needed for third party JavaScript libraries such as datepickers.
// MRBS tries to find the best match for these depending on the localisations available
// for the library.   This may sometimes mean that the locale for the library, eg the
// datepicker, is different from the main locale.   But this is probably better than
// trying to find a locale that is available on the server and has a translation available
// in MRBS and has localisations available in all the third party libraries.


// Translate languages in the header string to their aliases
if (isset($server['HTTP_ACCEPT_LANGUAGE']))
{
  $aliased_header = alias_header($server['HTTP_ACCEPT_LANGUAGE'], $lang_aliases ?? []);
}
else
{
  $aliased_header = null;
}
language_debug('$aliased_header: ' . $aliased_header);

// Get the locale that we should be using
$locale = get_mrbs_locale($aliased_header);

if (isset($locale))
{
  language_debug('$locale: ' . $locale);
}
else
{
  language_debug('None of the requested locales are available on this server');
}



// Find the best fit for the language that we should be using
$lang = get_mrbs_lang($locale);

if (isset($lang))
{
  language_debug('$lang: ' . $lang);
}
else
{
  language_debug('No suitable language file found');
}

// And then set that vocab
set_vocab($lang);


// Determines whether we are using a RTL language
function using_rtl_lang() : bool
{
  global $rtl_languages;

  $lang = get_mrbs_lang();
  return in_array(mb_strtolower($lang), $rtl_languages);
}


// Converts a locale to standard BCP 47 format
function convert_to_BCP47(?string $locale) : ?string
{
  if (!isset($locale))
  {
    return null;
  }

  // Parse it and then recompose it.  This will get the capitalisation correct, eg
  // "sr-Latn-RS".  Note though that BCP 47 language tags are case-insensitive and
  // the capitalisation is just a convention.
  $locale = Locale::composeLocale(Locale::parseLocale($locale));
  // Replace the underscores with hyphens.  The PHP Locale class will return underscores,
  // but the BCP 47 standard uses hyphens.
  return str_replace('_', '-', $locale);
}


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


// Work out which locale we want to use
// Note: the first time this function is called, it should be called with a $header string.
// Subsequent calls will just return the static $locale variable which was calculated on
// the first call
function get_mrbs_locale($header=null) : string
{
  global $override_locale, $disable_automatic_language_changing, $default_language_tokens;
  global $cli_language;

  static $locale = null;
  static $have_locale = false;   // $locale could be null, even when we've got it

  if (!$have_locale)
  {

    // Use the override locale if we've been given one
    if (!empty($override_locale))
    {
      $locale = $override_locale;
    }
    // Otherwise, if we're running from the CLI, use the config setting
    elseif (!empty($cli_language) && is_cli())
    {
      $locale = $cli_language;
    }
    // Otherwise, if we've been told to use a fixed language, then use that
    elseif ($disable_automatic_language_changing &&
            isset($default_language_tokens) &&
            ($default_language_tokens !== ''))
    {
      $locale = $default_language_tokens;
    }
    // Otherwise, use the best available language following browser preferences
    elseif (!empty($header))
    {
      $locale = Locale::acceptFromHttp($header);
    }

    // If everything fails, use English
    if (!isset($locale) || ($locale === false))
    {
      $locale = 'en';
    }

    // Convert it to BCP 47 format (eg convert 'en_US' to 'en-US')
    $locale = convert_to_BCP47($locale);
    $have_locale = true;
  }

  return $locale;
}


// Note: the first time this function is called, it should be called with a $locale.
// Subsequent calls will just return the static $lang variable which was calculated on
// the first call
function get_mrbs_lang(?string $locale=null)
{
  global $disable_automatic_language_changing,
         $default_language_tokens,
         $cli_language;

  static $lang = null;

  if (!isset($lang))
  {
    // If we're running from the CLI, use the config setting
    if (!empty($cli_language) && is_cli())
    {
      $lang = $cli_language;
    }
    // Otherwise, if we've been told to use a fixed language, then use that
    elseif ($disable_automatic_language_changing &&
            isset($default_language_tokens) &&
            ($default_language_tokens !== ''))
    {
      $lang = $default_language_tokens;
    }
    // Otherwise, use the best available language following browser preferences
    elseif (isset($locale))
    {
      $available_languages = get_langtags(MRBS_ROOT . '/lang', 'lang.');
      $lang = Locale::lookup($available_languages, $locale, false, $default_language_tokens);
    }
  }

  return $lang;
}



function alias_header(string $header, array $aliases) : string
{
  if (!empty($aliases))
  {
    $patterns = array();
    $replacements = array();

    foreach ($aliases as $key => $value)
    {
      $patterns[] = "/(?<=^|,)($key)(?=,|;|$)/i";
      $replacements[] = $value;
    }

    $header = preg_replace($patterns, $replacements, $header);
  }

  return $header;
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


function get_charset() : string
{
  return 'utf-8';
}


function get_mail_charset() : string
{
  return PHPMailer::CHARSET_UTF8;
}


function get_csv_charset() : string
{
  global $csv_charset;

  return (empty($csv_charset)) ? get_charset() : $csv_charset;
}


// Returns the language that MRBS is using, in BCP-47 format
function get_lang() : string
{
  global $lang;

  return $lang;
}


function get_bom(string $charset) : string
{
  switch(mb_strtolower($charset))
  {
    case 'utf-8':
      return pack('CCC', 0xEF, 0xBB, 0xBF);
      break;
    case 'utf-16':
      return pack('S', 0xFEFF);
      break;
    default:
      return '';
      break;
  }
}


/**
 * A wrapper for Language::getVocab() to avoid (a) having to replace
 * get_vocab() everywhere, and (b) having to use the long method call.
 */
function get_vocab(string $tag, ...$values) : string
{
  return Language::getVocab($tag, ...$values);
}


function get_mail_lang() : ?string
{
  global $mail_settings, $default_language_tokens;

  static $mail_lang;

  if (!isset($mail_lang))
  {
    $available_languages = get_langtags(MRBS_ROOT . '/lang', 'lang.');
    $mail_lang = Locale::lookup($available_languages, $mail_settings['admin_lang'], false, $default_language_tokens ?? 'en');
  }

  return $mail_lang;
}


// Same as get_vocab(), but escapes the result for use in JavaScript
function get_js_vocab(string $tag, ...$values) : string
{
  return escape_js(get_vocab($tag, ...$values));
}


// Same as get_vocab(), but uses the mailing language
function get_mail_vocab(string $tag, ...$values) : string
{
  global $vocab;

  static $mail_vocab = null;

  if (!isset($mail_vocab))
  {
    $web_vocab = $vocab;   // Save $vocab before it gets overwritten
    // Get the best language available for mail
    set_vocab(get_mail_lang());
    $mail_vocab = $vocab;
    $vocab = $web_vocab;  // Restore $vocab
  }

  // Return the tag itself if we can't find a vocab string
  if (!isset($mail_vocab[$tag]))
  {
    return $tag;
  }

  $result = (count($values) === 0) ? $mail_vocab[$tag] : sprintf($mail_vocab[$tag], ...$values);

  return str_replace('&nbsp;', ' ', $result);
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
    $locale = get_mrbs_locale();
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
    $locale = get_mrbs_locale();
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


function language_debug(string $message) : void
{
  global $language_debug;

  if ($language_debug)
  {
    error_log("[MRBS DEBUG] $message");
  }
}
