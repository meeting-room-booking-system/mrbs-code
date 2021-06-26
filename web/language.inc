<?php
namespace MRBS;

// Note that we are using \MRBS\Locale because \Locale has some bugs in it (and also
// isn't always installed).

require_once "functions.inc";


// A map of browser locale aliases
$lang_aliases = array
(
  'no' => 'nb',         // Not all operating systems will accept a locale of 'no'
  'sh' => 'sr-latn-rs',
);

// Map non-standard language codes used by flatpickr onto BCP 47 language tags
$flatpickr_lang_map = array(
  'at'     => 'de-at',
  'cat'    => 'ca',
  'gr'     => 'el',
  'kz'     => 'kk',
  'sr-cyr' => 'sr-cyrl',
  'vn'     => 'vi'
);

// Map flatpickr language file names onto flatpickr fp.l10ns properties
// so that they can be used with flatpickr.localize.
$flatpickr_property_map = array(
  'sr-cyr' => 'sr',
  'zh-tw'  => 'zh_tw'
);

// The DataTables language files are for the moment given descriptive names.   I have
// asked the DataTables team to use BCP 47 tags instead, but until that happens we have
// to map the descriptions onto tags.  (The other method would be to rename all the files,
// but then it would be less easy to take new releases of DataTables).
$datatables_lang_map = array(
  'Afrikaans'           => 'af',
  'Albanian'            => 'sq',
  'Amharic'             => 'am',
  'Arabic'              => 'ar',
  'Armenian'            => 'hy',
  'Azerbaijan'          => 'az',
  'Bangla'              => 'bn',
  'Basque'              => 'eu',
  'Belarusian'          => 'be',
  'Bulgarian'           => 'bg',
  'Catalan'             => 'ca',
  'Chinese-traditional' => 'zh-tw',
  'Chinese'             => 'zh',
  'Croatian'            => 'hr',
  'Czech'               => 'cs',
  'Danish'              => 'da',
  'Dutch'               => 'nl',
  'English'             => 'en',
  'Esperanto'           => 'eo',
  'Estonian'            => 'et',
  'Filipino'            => 'fil',
  'Finnish'             => 'fi',
  'French'              => 'fr',
  'Galician'            => 'gl',
  'Georgian'            => 'ka',
  'German'              => 'de',
  'Greek'               => 'el',
  'Gujarati'            => 'gu',
  'Hebrew'              => 'he',
  'Hindi'               => 'hi',
  'Hungarian'           => 'hu',
  'Icelandic'           => 'is',
  'Indonesian'          => 'id',
  'Irish'               => 'ga',
  'Italian'             => 'it',
  'Japanese'            => 'ja',
  'Kannada'             => 'kn',
  'Kazakh'              => 'kk',
  'Khmer'               => 'km',
  'Korean'              => 'ko',
  'Kurdish'             => 'ku',
  'Kyrgyz'              => 'ky',
  'Lao'                 => 'lo',
  'Latvian'             => 'lv',
  'Lithuanian'          => 'lt',
  'Macedonian'          => 'mk',
  'Malay'               => 'ms',
  'Mongolian'           => 'mn',
  'Nepali'              => 'ne',
  'Norwegian-Bokmal'    => 'nb',
  'Norwegian-Nynorsk'   => 'nn',
  'Pashto'              => 'ps',
  'Persian'             => 'fa',
  'Polish'              => 'pl',
  'Portuguese-Brasil'   => 'pt-br',
  'Portuguese'          => 'pt',
  'Punjabi'             => 'pa',
  'Romanian'            => 'ro',
  'Rumantsch'           => 'rm',
  'Russian'             => 'ru',
  'Serbian'             => 'sr',
  'Serbian_latin'       => 'sr-latn',
  'Sinhala'             => 'si',
  'Slovak'              => 'sk',
  'Slovenian'           => 'sl',
  'Spanish'             => 'es',
  'Swahili'             => 'sw',
  'Swedish'             => 'sv',
  'Tajik'               => 'tg',
  'Tamil'               => 'ta',
  'telugu'              => 'te',
  'Thai'                => 'th',
  'Turkish'             => 'tr',
  'Ukrainian'           => 'uk',
  'Urdu'                => 'ur',
  'Uzbek'               => 'uz',
  'Vietnamese'          => 'vi',
  'Welsh'               => 'cy'
);


// A list of languages that use Right to Left text
$rtl_languages = array(
  'he'
);


// Set the default character encoding
ini_set('default_charset', 'UTF-8');

// Set up mb_string internal encoding
if (function_exists('mb_internal_encoding'))
{
  mb_internal_encoding('UTF-8');
}

language_debug('$disable_automatic_language_changing: ' . (($disable_automatic_language_changing) ? 'true' : 'false'));
language_debug('$default_language_tokens: ' . $default_language_tokens);
language_debug('$cli_language: ' . $cli_language);
language_debug('$override_locale: ' . $override_locale);

// Check and adjust if necessary the format of $override_locale which changed in versions
// of MRBS > 1.7.1
if (!empty($override_locale))
{
  $new_override_locale = System::getBCPlocale($override_locale);
  if (utf8_strtolower($override_locale) != utf8_strtolower($new_override_locale))
  {
    $message = 'The config variable $override_locale should now be in BCP 47 format. ' .
      "Please change '$override_locale' to '$new_override_locale'.";
    trigger_error($message, E_USER_NOTICE);
    $override_locale = $new_override_locale;
    language_debug('Adjusted $override_locale: ' . $override_locale);
  }
}

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

language_debug('HTTP_ACCEPT_LANGUAGE: ' . $server['HTTP_ACCEPT_LANGUAGE']);
// Translate languages in the header string to their aliases
if (isset($server['HTTP_ACCEPT_LANGUAGE']))
{
  $aliased_header = alias_header($server['HTTP_ACCEPT_LANGUAGE'], $lang_aliases);
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

// Then set the locale
set_mrbs_locale($locale);

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
function using_rtl_lang()
{
  global $rtl_languages;

  $lang = get_mrbs_lang();
  return in_array(utf8_strtolower($lang), $rtl_languages);
}


// Converts a locale to standard BCP 47 format
function convert_to_BCP47($locale)
{
  if (!isset($locale))
  {
    return null;
  }

  // Parse it and then recompose it.  This will get the capitalisation correct, eg
  // "sr-Latn-RS".  Note though that BCP 47 language tags are case insensitive and
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
               " for language tag '$locale'.";
    trigger_error($message, E_USER_NOTICE);

    if (false === setlocale(LC_ALL, 'C'))
    {
      fatal_error("Could not set locale at all, not even to 'C'");
    }
  }
}


// Work out which locale we want to use
// Note: the first time this function is called, it should be called with a $header string.
// Subsequent calls will just return the static $locale variable which was calculated on
// the first call
function get_mrbs_locale($header=null)
{
  global $override_locale, $disable_automatic_language_changing, $default_language_tokens;
  global $cli_language;

  static $locale = null;
  static $have_locale = false;   // $locale could be null, even when we've got it

  if (!$have_locale)
  {
    // If we're running from the CLI, use the config setting
    if (!empty($cli_language) && is_cli())
    {
      $locale = $cli_language;
    }
    // Otherwise use the override locale if we've been given one
    elseif (!empty($override_locale))
    {
      $locale = $override_locale;
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
    // If there aren't any browser preferences, use English
    else
    {
      $locale = 'en';
    }

    // Convert it to BCP 47 format (eg convert 'en_US' to 'en-US')
    $locale = convert_to_BCP47($locale);
  }

  return $locale;
}


// Returns a simple array of BCP 47 language tags in decreasing order of preference.  This
// is designed for use with third party libraries (eg datepickers, datatables) where the
// locale used by MRBS, and given by get_mrbs_locale(), may not be available.  So this function
// returns as wide a list of languages as possible, in the hope that one of them may be
// supported by the third party library.
function get_lang_preferences()
{
  global $cli_language, $disable_automatic_language_changing, $default_language_tokens;
  global $override_locale;
  global $aliased_header;

  static $lang_preferences = null;

  if (!isset($lang_preferences))
  {
    // Use the aliased browser preferences, if any, as a base.    Although we may add
    // higher priority languages later, these are a useful fall-back on the grounds
    // that it's better to have some language rather than none.
    $lang_preferences = get_browser_langs($aliased_header, true);

    // If we've been told not to use the browser languages then the default
    // language is more important
    if ($disable_automatic_language_changing &&
        isset($default_language_tokens) &&
        ($default_language_tokens) !== '')
    {
      array_unshift($lang_preferences, $default_language_tokens);
    }

    // If there's an override locale then that's more important still
    if (!empty($override_locale))
    {
      array_unshift($lang_preferences, $override_locale);
    }

    // If we're running from the CLI then put the CLI language at the top
    if (is_cli() && !empty($cli_language))
    {
      array_unshift($lang_preferences, $cli_language);
    }

    // Add the default language tokens as a fallback
    if (isset($default_language_tokens) && ($default_language_tokens !== ''))
    {
      array_push($lang_preferences, $default_language_tokens);
    }

    // Finally, add a backstop at the very bottom of the list
    array_push($lang_preferences, 'en');
  }

  return $lang_preferences;
}


// Note: the first time this function is called, it should be called with a $locale.
// Subsequent calls will just return the static $lang variable which was calculated on
// the first call
function get_mrbs_lang($locale=null)
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


// Returns a version of the Accept-Language request HTTP header with language
// strings substituted for their aliases
function alias_header($header, array $aliases)
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


// Returns a sorted associative array of acceptable language qualifiers, indexed
// by language, given an Accept-Language header string.
// If $translate_wildcard is set then the wildcard language identifier ('*') is
// translated to a standard language - we use 'en'.
function get_qualifiers($header, $translate_wildcard=false)
{
  $result = array();

  if (!empty($header))
  {
    $lang_specifiers = explode(',', $header);

    foreach ($lang_specifiers as $specifier)
    {
      unset($weight);
      $specifier = trim($specifier);

      // The regular expressions below are not tight definitions of permissible language tags.
      // They let through some tags which are not permissible, but they do allow permissible
      // tags such as 'es-419'.
      if (preg_match('/^([a-zA-Z0-9\-]+|\*);q=([0-9.]+)$/', $specifier, $matches))
      {
        $language = $matches[1];
        $weight = (float) $matches[2];
      }
      else if (preg_match('/^([a-zA-Z0-9\-]+|\*)$/', $specifier, $matches))
      {
        $language = $matches[1];
        $weight = 1.0;
      }
      else
      {
        trigger_error("Unexpected specifier format '$specifier'", E_USER_NOTICE);
      }

      if (isset($weight))
      {
        if ($translate_wildcard && ($language == '*'))
        {
          // Handle the wildcard language by using English
          $language = 'en';
        }
        // If a language occurs twice (possibly as a result of a wildcard or aliasing) then
        // only change the weight if it's greater than the one we've already got.
        if (!isset($result[$language]) || ($weight > $result[$language]))
        {
          $result[$language] = $weight;
        }
      }
    }
  }

  arsort($result, SORT_NUMERIC);

  return $result;
}

// Returns a string of acceptable languages, sorted in decreasing order of preference
// If $translate_wildcard is set then the wildcard language identifier ('*') is
// translated to a standard language - we use 'en'.
function get_browser_langs($header, $translate_wildcard=false)
{
  return array_keys(get_qualifiers($header, $translate_wildcard));
}


// Gets all the language tags in a directory where the filenames are of the format
// $prefix . $lang . $suffix.  Returns an array.
function get_langtags($dir, $prefix='', $suffix='')
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


// Get the best fit language file from $dir given the set of language preferences.  Returns NULL
// if none available.
//   $default_langtags is an array of languages that are already built into the system
//     and do not need an explicit regional setting.
//   $lang_map is an associative array that maps a filename (less prefix and suffix)
//     onto a language tag, for example array('English' => 'en').  Useful when the localisation
//     files do not follow a language tag naming scheme.
//
// The file names are assumed to be of the form $prefix . $lang . $suffix, eg
// 'datepicker-en-GB.js'
function get_best_lang_file($dir, $prefix='', $suffix='', array $default_langtags=null, array $lang_map=null)
{
  global $default_language_tokens;

  if (!is_dir($dir))
  {
    trigger_error("MRBS: directory $dir does not exist", E_USER_NOTICE);
    return null;
  }

  $lang_preferences = get_lang_preferences();

  // Get the available languages
  $available_langtags = get_langtags($dir, $prefix, $suffix);

  // Translate the non-standard names into BCP 47 tags
  if (isset($lang_map))
  {
    foreach($available_langtags as $langtag)
    {
      if (isset($lang_map[$langtag]))
      {
        // Replace langtag with its mapping
        array_splice($available_langtags,
                     array_search($langtag, $available_langtags),
                     1,
                     $lang_map[$langtag]);
      }
    }
  }

  // Merge in the default languages
  if (isset($default_langtags))
  {
    $available_langtags = array_unique(array_merge($available_langtags, $default_langtags));
  }

  // Find the best fit.
  foreach ($lang_preferences as $lang)
  {
    // For some reason the third parameter to Locale::lookup() is mandatory in 5.3.0.
    // Can't see anything about it in the manual - must be a bug that was fixed in
    // later versions.
    $locale = Locale::lookup($available_langtags, $lang, $canonicalize = false);

    if (!empty($locale))
    {
      if (isset($default_langtags) && in_array($locale, $default_langtags))
      {
        return null;
      }
      $filename_body = (isset($lang_map)) ? array_search($locale, $lang_map) : false;
      $filename_body = ($filename_body === false) ? $locale : $filename_body;
      return $prefix . $filename_body . $suffix;
    }
  }

  return null;  // We failed to find anything acceptable
}


// Returns the pathname of the language file to use for the flatpickr
// datepicker.    If no suitable language file exists then returns NULL.
function get_flatpickr_lang_path()
{
  global $flatpickr_lang_map;

  static $have_result = false;
  static $result = null;

  if (!$have_result)
  {
    $dir = 'js/flatpickr/l10n';
    $file = get_best_lang_file(__DIR__ . "/$dir", '', '.js', null, $flatpickr_lang_map);
    $result = (isset($file)) ? "$dir/$file" : null;
    $have_result = true;
  }

  return $result;
}


// Given a flatpickr localisation, find the corresponding property for
// use with flatpickr.localize
function get_flatpickr_property($lang_file)
{
  global $flatpickr_property_map;

  $basename = basename($lang_file, '.js');

  return (isset($flatpickr_property_map[$basename])) ? $flatpickr_property_map[$basename] : $basename;
}


// Returns the pathname of the language file to use for the dataTables
// jQuery plugin.    If no suitable language file exists then returns NULL.
function get_datatable_lang_path()
{
  global $datatables_lang_map;

  static $have_result = false;
  static $result = null;

  if (!$have_result)
  {
    $dir = 'jquery/datatables/language';
    $file = get_best_lang_file(__DIR__ . "/$dir", '', '.lang', null, $datatables_lang_map);
    $result = (isset($file)) ? "$dir/$file" : null;
    $have_result = true;
  }

  return $result;
}

// Returns the pathname of the language file to use for the Select2
// plugin.    If no suitable language file exists then returns NULL.
function get_select2_lang_path()
{
  static $have_result = false;
  static $result = null;

  if (!$have_result)
  {
    $dir = 'jquery/select2/dist/js/i18n';
    $file = get_best_lang_file(__DIR__ . "/$dir", '', '.js');
    $result = (isset($file)) ? "$dir/$file" : null;
    $have_result = true;
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

  $lang = utf8_strtolower($lang);
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


function get_charset()
{
  return 'utf-8';
}


function get_mail_charset()
{
  return 'utf-8';
}


function get_csv_charset()
{
  global $csv_charset;
  if (empty($csv_charset))
  {
    return get_charset();
  }
  else
  {
    return $csv_charset;
  }
}


// Returns the language that MRBS is using, in BCP-47 format
function get_lang()
{
  global $lang;

  return $lang;
}


function get_bom($charset)
{
  switch(utf8_strtolower($charset))
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


// Get a vocab item, in UTF-8
// Takes additional parameters as for sprintf()
//
// [Maybe in the future we should switch to using the MessageFormatter
//  class as it is more powerful.   However the Intl extension isn't present
//  in all PHP installations and so the class would have to be emulated]
function get_vocab($tag)
{
  global $vocab;

  // Return the tag itself if we can't find a vocab string
  if (!isset($vocab[$tag]))
  {
    return $tag;
  }

  $args = func_get_args();
  $args[0] = $vocab[$tag];

  return call_user_func_array('sprintf', $args);
}


// Same as get_vocab(), but uses the mailing language
function get_mail_vocab($tag)
{
  global $vocab, $mail_settings;

  static $mail_vocab = null;

  if (!isset($mail_vocab))
  {
    $web_vocab = $vocab;   // Save $vocab before it gets overwritten
    set_vocab($mail_settings['admin_lang']);
    $mail_vocab = $vocab;
    $vocab = $web_vocab;  // Restore $vocab
  }

  // Return the tag itself if we can't find a vocab string
  if (!isset($mail_vocab[$tag]))
  {
    return $tag;
  }

  $args = func_get_args();
  $args[0] = $mail_vocab[$tag];
  $result = call_user_func_array('sprintf', $args);

  return str_replace('&nbsp;', ' ', $result);
}


// Get localised booking type name
function get_type_vocab($type)
{
  return get_vocab("type.$type");
}


// Get localized field name for a user defined table column
// Looks for a tag of the format tablename.columnname (where tablename is
// stripped of the table prefix) and if can't find a string for that tag will
// return the column name
function get_loc_field_name($table, $name)
{
  global $vocab;

  // Get the qualified prefix
  $full_prefix = _tbl('');
  // Strip the prefix, if any, off the table name
  if (utf8_strpos($table, $full_prefix) === 0)
  {
    $tag = utf8_substr($table, utf8_strlen($full_prefix));
  }
  else
  {
    $tag = $table;
  }
  // Add on the field name
  $tag .= "." . $name;

  // Then if there's a string in the vocab array for $tag use that,
  // otherwise just use the fieldname
  return (isset($vocab[$tag])) ? get_vocab($tag) : $name;
}


// optionally switch to a new locale, switching back at the end.
// $temp_locale can either be a string or an array of locales.
function utf8_strftime($format, $time, $temp_locale=null)
{
  $server_os = System::getServerOS();

  // Set the temporary locale.  Note that $temp_locale could be an array of locales,
  // so we need to find out which locale actually worked.
  if (!empty($temp_locale))
  {
    $old_locale = setlocale(LC_TIME, '0');
    $new_locale = setlocale(LC_TIME, $temp_locale);
  }
  elseif ($server_os == "windows")
  {
    // If we are running Windows we have to set the locale again in case another script
    // running in the same process has changed the locale since we first set it.  See the
    // warning on the PHP manual page for setlocale():
    //
    // "The locale information is maintained per process, not per thread. If you are
    // running PHP on a multithreaded server API like IIS or Apache on Windows, you may
    // experience sudden changes in locale settings while a script is running, though
    // the script itself never called setlocale(). This happens due to other scripts
    // running in different threads of the same process at the same time, changing the
    // process-wide locale using setlocale()."
    $new_locale = get_mrbs_locale();
    set_mrbs_locale($new_locale);
  }
  else
  {
    $new_locale = null;
  }

  if ($server_os == "windows")
  {
    // Some formats not supported on Windows.   Replace with suitable alternatives
    $format = str_replace("%R", "%H:%M", $format);
    $format = str_replace("%P", "%p", $format);
    $format = str_replace("%l", "%I", $format);
    $format = str_replace("%e", "%#d", $format);
  }

  // %p doesn't actually work in some locales, we have to patch it up ourselves
  if (preg_match('/%p/', $format))
  {
    $ampm = strftime('%p', $time);  // Don't replace the %p with the $strftime_format variable!!
    if ($ampm == '')
    {
      $ampm = date('a', $time);
    }

    $format = preg_replace('/%p/', $ampm, $format);
  }

  $result = strftime($format, $time);
  $result = System::utf8ConvertFromLocale($result, $new_locale);

  // Restore the original locale
  if (!empty($temp_locale))
  {
    setlocale(LC_TIME, $old_locale);
  }

  return $result;
}


// UTF-8 compatible substr function obtained from a contribution by
// "frank at jkelloggs dot dk" in the PHP online manual for substr()
function utf8_substr_old($str,$start)
{
  preg_match_all("/./su", $str, $ar);

  if(func_num_args() >= 3) {
    $length = func_get_arg(2);
    return join("", array_slice($ar[0], $start, $length));
  } else {
    return join("", array_slice($ar[0], $start));
  }
}


// UTF-8 compatible substr function
function utf8_substr($str, $start, $length=null)
{
  if (!isset($length))
  {
    // We cannot set $length to PHP_INT_MAX because there is a bug (#42101)
    // in mb_substr() when using PHP_INT_MAX on 64 bit Linux systems.  See
    // https://bugs.php.net/bug.php?id=42101.    Note also that passing NULL
    // for the length in mb_substr() causes mb_substr to use a length of 0.
    // See the user contributed notes on the PHP mb_substr() manual page.
    $length = utf8_strlen($str);
  }

  if (function_exists('mb_substr'))
  {
    // If we have mb_substr, use it - it's much quicker than our
    // routines, as it's native code

    $encoding = mb_detect_encoding($str);

    return mb_substr($str, $start, $length, $encoding);
  }

  if (strlen($str) > 1000)
  {
    // If the string is long, the old routine is quicker. :(

    return utf8_substr_old($str, $start, $length);
  }

  // Deal with the trivial cases first, which won't be dealt with properly otherwise
  // below, due to the dummy string we have to use to convince PHP to treat the result as
  // a string rather than an array.
  if (($str === '') || ($length === 0))
  {
    return '';
  }

  $i = 0;
  $index = 0;
  while (isset($index) && isset($str[$index]) && (ord($str[$index]) != 0) && ($i < $start))
  {
    $index = utf8_next_index($str, $index);
    $i++;
  }

  if (!isset($index))
  {
    return '';
  }

  if (func_num_args() >= 3)
  {
    $end_index = $index;

    $j = 0;
    while (isset($end_index) && ($j < $length))
    {
      $end_index = utf8_next_index($str, $end_index);
      $j++;
    }
    $j = 0;
    $ret = '.';  // Dummy to fool PHP. If we us an empty string it thinks it's an array.
    for ($i = $index;
         isset($str[$i]) && (ord($str[$i]) != 0) && (!isset($end_index) || ($i < $end_index));
         $i++)
    {
      $ret[$j++] = $str[$i];
    }
    return $ret;
  }
  else
  {
    $j = 0;
    $ret = '.';  // Dummy to fool PHP. If we us an empty string it thinks it's an array.
    for ($i = $index; isset($str[$i]) && (ord($str[$i]) != 0); $i++)
    {
      $ret[$j++] = $str[$i];
    }
    return $ret;
  }
}


// Takes a string (which may be UTF-8) and returns how long it is in
// _bytes_
function utf8_bytecount($str)
{
  // We cannot rely on strlen() to return the number of bytes because it might
  // have been overloaded by mb_strlen() which returns characters
  if (function_exists('mb_strlen'))
  {
    return mb_strlen($str, '8bit');
  }
  else
  {
    return strlen($str);
  }
}


// Takes a UTF-8 string and returns the string with one Unicode character
// removed from the front
function utf8_next($str)
{
  $ret = null;

  if (isset($str))
  {
    $index = utf8_next_index($str, 0);

    if ($index)
    {
      $i = 0;
      $ret = '.';  // Dummy to fool PHP. If we us an empty string it thinks it's an array.
      while (isset($str[$index]) && ord($str[$index]) != 0)
      {
        $ret[$i++] = $str[$index++];
      }
    }
  }
  return $ret;
}


// Takes a UTF-8 string and a byte index into that string, and
// returns the byte index of the next UTF-8 sequence. When the end
// of the string is encountered, the function returns NULL
function utf8_next_index($str, $start)
{
  $ret = null;

  $i = $start;

  if (isset($str) && isset($str[$i]))
  {
    if (ord($str[$i]) < 0xc0)
    {
      $i++;
    }
    else
    {
      $i++;
      while (isset($str[$i]) && ((ord($str[$i]) & 0xc0) == 0x80))
      {
        $i++;
      }
    }
    if (isset($str[$i]) && (ord($str[$i]) != 0))
    {
      $ret = $i;
    }
  }
  return $ret;
}


// Given a UTF-8 string and a byte index, return the UTF-8 sequence
// at this index as a string, and update the byte index to point to
// the next sequence. When the end of the string is encountered, the
// last sequence is returned, and the byte index set to NULL
function utf8_seq($str, &$byte_index)
{
  $ret = '.';  // Dummy to fool PHP. If we us an empty string it thinks it's an array.

  $next = utf8_next_index($str, $byte_index);

  if (isset($next))
  {
    $j = 0;
    for ($i = $byte_index; $i < $next; $i++)
    {
      $ret[$j] = $str[$i];
      $j++;
    }
  }
  else
  {
    $j = 0;
    for ($i = $byte_index; isset($str[$i]) && (ord($str[$i]) != 0); $i++)
    {
      $ret[$j] = $str[$i];
      $j++;
    }
  }
  $byte_index = $next;
  return $ret;
}


// Takes a UTF-8 string and converts it to UTF-16 without using iconv
function utf8_to_utf16($string)
{
  $ucs2 = array();
  $byte_index = 0;

  while (!is_null($byte_index))
  {
    $next = utf8_seq($string, $byte_index);

    $c0 = ord($next[0]);

    // Easy case, code is 0xxxxxxx - just use it as is
    if ($c0 < 0x80)
    {
      array_push($ucs2, $c0);
      continue;
    }
    $cn = ord($next[1]) ^ 0x80;
    $ucs = ($c0 << 6) | $cn;

    // Two byte codes: 110xxxxx 10xxxxxx
    if ($c0 < 0xE0)
    {
      $ucs &= ~0x3000;
      array_push($ucs2, $ucs);
      continue;
    }

    $cn = ord($next[2]) ^ 0x80;
    $ucs = ($ucs << 6) | $cn;

    // Three byte codes: 1110xxxx 10xxxxxx 10xxxxxx
    if ($c0 < 0xF0)
    {
      $ucs &= ~0xE0000;
      array_push($ucs2, $ucs);
      continue;
    }

    $cn = ord($next[3]) ^ 0x80;
    $ucs = ($ucs << 6) | $cn;

    // Four byte codes: 11110xxx 10xxxxxxx 10xxxxxx 10xxxxxx
    if ($c0 < 0xF8)
    {
      $ucs &= ~0x3C00000;
      array_push($ucs2, $ucs);
      continue;
    }
    die("Shouldn't get here!");
  }

  $out = "";
  foreach ($ucs2 as $char)
  {
    $ucs_string = pack("v", $char);
    //error_log(sprintf("UCS %04x -> %02x,%02x",$char,ord($ucs_string[0]),ord($ucs_string[1])));
    $out .= $ucs_string;
  }
  return $out;
}


// Takes a UTF-8 string, and returns the number of _characters_ in the
// string
function utf8_strlen($str)
{
  if (!isset($str) || ($str == ""))
  {
    return 0;
  }
  if (function_exists('mb_strlen'))
  {
    // If we have mb_strlen(), use it - it'll be quicker
    return mb_strlen($str);
  }
  $len = 1;
  $next = 0;
  while ($next = utf8_next_index($str, $next))
  {
    $len++;
  }
  return $len;
}


// Wrapper round mb_strtolower() to make sure it's used if present and mb_string
// function overloading is not enabled.
// The ordinary strtolower() will give unexpected results when the locale is set to
// Turkish and will not convert the letter 'I'.
function utf8_strtolower($str)
{
  if (function_exists('mb_strtolower'))
  {
    return mb_strtolower($str);
  }

  return strtolower($str);
}


// Wrapper round mb_strtoupper() to make sure it's used if present and mb_string
// function overloading is not enabled.
// The ordinary strtoupper() will give unexpected results when the locale is set to
// Turkish and will not convert the letter 'i'.
function utf8_strtoupper($str)
{
  if (function_exists('mb_strtoupper'))
  {
    return mb_strtoupper($str);
  }
  return strtoupper($str);
}


// Wrapper round mb_strpos()
function utf8_strpos($haystack, $needle, $offset=0)
{
  if (function_exists('mb_strpos'))
  {
    return mb_strpos($haystack, $needle, $offset);
  }
  return strpos($haystack, $needle, $offset);
}


function language_debug($message)
{
  global $language_debug;

  if ($language_debug)
  {
    error_log("[DEBUG] $message");
  }
}
