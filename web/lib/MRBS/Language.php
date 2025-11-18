<?php
declare(strict_types=1);
namespace MRBS;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use MRBS\Errors\Errors;
use MRBS\Intl\Locale;

class Language
{
  /**
   * The fall-back locale if nothing else is suitable.
   */
  private const DEFAULT_LOCALE = 'en';

  /**
   * A map of language aliases, indexed by the alias, mapping non-standard language tags
   * that are typically used by browsers onto their more common BCP 47 equivalents. These
   * should all be in lowercase.  If the config or browser settings specify the key (in
   * whatever case), it will be replaced by the value.
   *
   * BCP 47 is case-insensitive, although there are conventions for the various subtags.
   * However, keeping the strings in lowercase helps the array look-up.
   */
  private const LANG_ALIASES = [
    'no' => 'nb', // Not all operating systems will accept a locale of 'no', but most will support 'nb'
    'zh-cn' => 'zh-hans-cn', // Most operating systems support 'zh_Hans_CN' or 'zh_Hans', rather than 'zh_CN'
    'zh-tw' => 'zh-hant-tw', // Most operating systems support 'zh_Hant_TW' or 'zh_Hant', rather than 'zh_TW'
    'sh' => 'sr-latn-rs', // 'sh' is non-standard
  ];

  /**
   * Maps the non-standard language tag used in the filename onto the BCP 47 standard
   */
  private const LANG_MAP_FLATPICKR = [
    'at'      => 'de-at',
    'cat'     => 'ca',
    'ckb'     => 'ku',
    'gr'      => 'el',
    'kz'      => 'kk',
    'sr-cyr'  => 'sr-cyrl',
    'uz-latn' => 'uz-latn-uz',
    'vn'      => 'vi',
    'zh-tw'   => 'zh-hant-tw'
  ];

  /**
   * Maps the non-standard language tag used in the filename onto the BCP 47 standard
   */
  private const LANG_MAP_SELECT2 = [
    'zh-TW' => 'zh-hant-tw',
    'zh-CN' => 'zh-hans-cn',
  ];

  private const LANG_DIRS = [
    'mrbs' => [
      'dir' => 'lang',
      'prefix' => 'lang.'
    ],
    'datatables' => [
      'dir' => 'jquery/datatables/language',
      'suffix' => '.json',
      'defaults' => ['en']
    ],
    'flatpickr' => [
      'dir' => 'js/flatpickr/l10n',
      'suffix' => '.js',
      'lang_map' => self::LANG_MAP_FLATPICKR
    ],
    'select2' => [
      'dir' => 'jquery/select2/dist/js/i18n',
      'suffix' => '.js',
      'lang_map' => self::LANG_MAP_SELECT2,
    ]
  ];

  private static $best_locales = [];
  private static $default_language_tokens;

  /**
   * @param string|null $override_locale a locale in BCP 47 format, eg 'en-GB'
   */
  public static function init(
    ?string $cli_language,
    bool $disable_automatic_language_changing,
    ?string $default_language_tokens,
    ?string $override_locale
  ) : void
  {
    global $server;

    self::debug('$cli_language: ' . ($cli_language ?? 'NULL'));
    self::debug('$disable_automatic_language_changing: ' . (($disable_automatic_language_changing) ? 'true' : 'false'));
    self::debug('$default_language_tokens: ' . ($default_language_tokens ?? 'NULL'));
    self::debug('$override_locale: ' . ($override_locale ?? 'NULL'));
    self::debug('Accept-Language: ' . ($server['HTTP_ACCEPT_LANGUAGE'] ?? 'NULL'));

    self::$default_language_tokens = $default_language_tokens;

    // Set the default character encoding
    ini_set('default_charset', 'UTF-8');

    // Set up mb_string internal encoding
    if (function_exists('mb_internal_encoding'))
    {
      mb_internal_encoding('UTF-8');
    }

    // Work out the preferred order of locales.
    $preferences = self::getPreferences(
      $cli_language,
      $disable_automatic_language_changing,
      $default_language_tokens,
      $override_locale
    );
    self::debug('$preferences: ' . json_encode($preferences));

    // Get the best fit locales, given the preferences.
    if (null === ($best_fits = self::getBestFits($preferences, self::LANG_DIRS)))
    {
      trigger_error("Could not find a suitable locale; using '" . self::DEFAULT_LOCALE . "'", E_USER_WARNING);
    }

    // Store the best fits
    foreach (array_merge(['locale'], array_keys(self::LANG_DIRS)) as $key)
    {
      self::$best_locales[$key] = $best_fits[$key] ?? self::DEFAULT_LOCALE;
      self::debug("Best[$key]: '" . self::$best_locales[$key] . "'");
    }

    // Set the locale
    self::setLocale(self::$best_locales['locale']);
  }


  /**
   * Returns a string of acceptable languages, sorted in decreasing order of preference.
   *
   * @param bool $translate_wildcard If set then the wildcard language identifier ('*') is
   *                                 translated to a standard language - we use 'en'
   * @return string[]
   */
  public static function getBrowserPreferences(?string $header, bool $translate_wildcard = false) : array
  {
    return array_keys(self::getQualifiers($header, $translate_wildcard));
  }


  /**
   * Returns a sorted associative array of acceptable language qualifiers, indexed
   * by language.
   *
   * @param string|null $header an Accept-Language header string
   * @param bool $translate_wildcard If set then the wildcard language identifier ('*') is
   *                                 translated to a standard language - we use 'en'
   * @return array<string, float>
   */
  public static function getQualifiers(?string $header, bool $translate_wildcard = false) : array
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
          $message = "Unexpected specifier format '$specifier' in the Accept-Language request header sent by the client.";
          trigger_error($message, E_USER_NOTICE);
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


  /**
   * Returns the pathname of the language file to use for the DataTables jQuery plugin.
   */
  public static function getDatatablesLangPath() : ?string
  {
    return self::getLangPath('datatables', self::$best_locales['datatables']);
  }


  /**
   * Returns the pathname of the language file to use for the Flatpickr datepickr.
   */
  public static function getFlatpickrLangPath() : ?string
  {
    return self::getLangPath('flatpickr', self::$best_locales['flatpickr']);
  }


  /**
   * Returns the pathname of the language file to use for the Select2 jQuery plugin.
   */
  public static function getSelect2LangPath() : ?string
  {
    return self::getLangPath('select2', self::$best_locales['select2']);
  }


  /**
   * Gets the vocab string for a given tag.
   *
   * @param mixed ...$values Optional values to be inserted into the string, as for sprintf()
   * @return string The vocab string, or the tag itself if there is no string.
   */
  public static function getVocab(string $tag, ...$values) : string
  {
    //  Maybe in the future we should switch to using the MessageFormatter
    //  class as it is more powerful.   However, the Intl extension isn't present
    //  in all PHP installations and so the class would have to be emulated.
    static $vocab;

    if (!isset($vocab))
    {
      $vocab = self::loadVocab(self::$best_locales['mrbs']);
    }

    // Return the tag itself if we can't find a vocab string
    if (!isset($vocab[$tag]))
    {
      return $tag;
    }

    return (count($values) === 0) ? $vocab[$tag] : sprintf($vocab[$tag], ...$values);
  }


  /**
   * Sets the locale, trying the possible variants appropriate to the OS.
   */
  public static function setLocale(string $locale) : void
  {
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


  private static function getLangPath(string $component, string $lang) : ?string
  {
    $details = self::LANG_DIRS[$component];

    // If it's a default language then we don't need a language file.
    if (isset($details['defaults']) && in_array($lang, $details['defaults']))
    {
      return null;
    }

    // Reverse any mapping, so that we've got the actual name
    if (isset($details['lang_map']) && (false !== ($key = array_search($lang, $details['lang_map']))))
    {
      $lang = $key;
    }

    return $details['dir'] . '/' . ($details['prefix'] ?? '') . $lang . ($details['suffix'] ?? '');
  }


  private static function loadVocab(string $lang) : array
  {
    global $vocab_override;

    $vocab = [];

    // Set the final fallback language as some of the translations are incomplete.
    $langs = [self::DEFAULT_LOCALE];
    // Then set the default language as the fallback before that.
    if (isset(self::$default_language_tokens) && (self::$default_language_tokens !== ''))
    {
      $langs[] = mb_strtolower(self::$default_language_tokens);
    }
    // Then set the language we want
    $langs[] = $lang;  // This is the language we want

    // Eliminate any duplicates.
    $langs = array_unique($langs);

    // Then load the files in turn, each one overwriting the previous ones.
    foreach ($langs as $lang)
    {
      $lang_file = MRBS_ROOT . '/' . self::getLangPath('mrbs', $lang);

      if (!is_readable($lang_file))
      {
        trigger_error("MRBS: could not set language to '$lang'", E_USER_WARNING);
      }

      // Load the language tokens
      include "$lang_file";
    }

    // And apply any site overrides for this language
    if (isset($vocab_override[$lang]))
    {
      foreach ($vocab_override[$lang] as $tag => $str)
      {
        $vocab[$tag] = $str;
      }
    }

    return $vocab;
  }


  /**
   * Convert a language alias to the real language.
   */
  private static function unAlias(string $langtag) : string
  {
    // Convert to lowercase, because all the keys in the alias map should be lowercase.
    $lc_langtag = mb_strtolower($langtag);
    if (!empty(self::LANG_ALIASES) && array_key_exists($lc_langtag, self::LANG_ALIASES))
    {
      return self::LANG_ALIASES[$lc_langtag];
    }

    return $lc_langtag;
  }


  /**
   * Get the preferred set of locales in descending order.
   *
   * @return string[]
   */
  private static function getPreferences(
    ?string $cli_language,
    bool $disable_automatic_language_changing,
    ?string $default_language_tokens,
    ?string $override_locale
  ) : array
  {
    global $server;

    $result = [];

    // The preference order is determined by the config settings and the browser language
    // preferences, if appropriate.  The order below is used for backward compatibility
    // with older versions of MRBS.  However, it's a bit messy.  In particular $override_locale
    // and $default_language tokens have some overlap.
    // TODO: Review this and come up with something more logical?

    // If we're running from the CLI then use the CLI language, if set, as first preference.
    if (is_cli() && isset($cli_language) && ($cli_language !== ''))
    {
      $result[] = $cli_language;
    }

    // Otherwise if we're not using automatic language changing, then use the default language, if set.
    elseif ($disable_automatic_language_changing && isset($default_language_tokens) && ($default_language_tokens !== ''))
    {
      $result[] = $default_language_tokens;
    }

    // Otherwise use the override locale, if set, and then the browser preferences.
    else
    {
      if (isset($override_locale))
      {
        $result[] = $override_locale;
      }
      if (isset($server['HTTP_ACCEPT_LANGUAGE']))
      {
        $result = array_merge($result, self::getBrowserPreferences($server['HTTP_ACCEPT_LANGUAGE'], true));
      }
    }

    // Add a backstop at the very bottom of the list
    $result[] = self::DEFAULT_LOCALE;

    // Remove any aliases
    return array_map([__CLASS__, 'unAlias'], $result);
  }


  /**
   * Given a set of preferences, get the best locales that can be used that will work
   * throughout MRBS.
   *
   * The locales have to be supported by setlocale(), MRBS's language files and the language
   * files used by third-party packages.  It uses RFC 4647's lookup algorithm for determining
   * whether a set of language files is suitable.  The results for each component could be slightly
   * different: for example 'pt-BR' for one and 'pt' for another, but never 'pt' and 'es'.
   *
   * @param string[] $preferences Locales in decreasing order of preference.
   * @param array{string, array} $components An array of component details, indexed by component name (e.g. 'mrbs').
   * @return string[]|null An array of best fits, indexed by 'locale' or component name, or NULL if none could be found.
   */
  private static function getBestFits(array $preferences, array $components) : ?array
  {
    $result = [];

    // Get the languages supported by each of the software components.
    foreach ($components as $component => $details)
    {
      $available_languages[$component] = self::getLangtags(...$details);
      self::debug("Available_languages($component): " . json_encode($available_languages[$component]));
    }

    // Test each locale in decreasing order of preference to see if it is supported throughout MRBS.
    // Record the best locale for each component.
    // (An alternative algorithm, used by earlier versions of MRBS, used the best locale for each
    // component.  However, while this might result in a more preferred locale for some components,
    // it could result in inconsistency overall: for example the mini-calendars in one language, but
    // the main MRBS text in another.)
    foreach ($preferences as $locale)
    {
      // Check whether setlocale() is going to work
      self::debug("Trying '$locale'");
      if (false === Locale::acceptFromHttp($locale))
      {
        self::debug('locale: failed');
        continue;
      }
      self::debug("locale: '$locale'");
      $result['locale'] = $locale;

      // Then check each of the components to see if there's a language file matching this locale.
      foreach ($available_languages as $component => $tags)
      {
        if ('' === ($result[$component] = Locale::lookup($tags, $locale)))
        {
          self::debug("$component: failed");
          continue 2;
        }
        self::debug("$component: '" . $result[$component] . "'");
      }

      // Nothing failed, so this locale will work
      return $result;
    }

    return null;
  }


  // Gets all the language tags in a directory where the filenames are of the format
// $prefix . $lang . $suffix.  Returns an array.
  private static function getLangtags(string $dir, string $prefix='', string $suffix='', array $lang_map=[], array $defaults=[]) : array
  {
    // TODO: Use $lang_map when getting the langpath
    // TODO: Does defaults need to be an array?  (Maybe, eg to cater for en-* ??)
    // TODO: Sort out uz-latn / uz_latn
    // TODO: comments
    // TODO: turn into a singleton?
    $result = [];

    $dir = MRBS_ROOT . "/$dir";

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

    // Translate the non-standard names into BCP 47 tags
    if (!empty($lang_map))
    {
      foreach($result as $langtag)
      {
        if (isset($lang_map[$langtag]))
        {
          // Replace langtag with its mapping
          array_splice($result, array_search($langtag, $result), 1, $lang_map[$langtag]);
        }
      }
    }

    // Merge in the default languages
    $result = array_unique(array_merge($result, $defaults));

    return $result;
  }


  private static function debug(string $message) : void
  {
    global $language_debug;

    static $logger;

    if ($language_debug)
    {
      if (!isset($logger))
      {
        // Set up the logger to send output to the browser console and the error log
        $logger = new Logger('MRBS.Language');
        $logger->pushHandler(new BrowserConsoleHandler());
        $handler = new ErrorLogHandler();
        $handler->setFormatter(new LineFormatter('%channel%.%level_name%: %message%'));
        $logger->pushHandler($handler);
      }
      $logger->debug($message);
    }
  }

}
