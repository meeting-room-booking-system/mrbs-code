<?php
declare(strict_types=1);
namespace MRBS;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use MRBS\Intl\Locale;

class Language
{
  // A map of language aliases, indexed by the alias
  private const LANG_ALIASES = [
    'no' => 'nb', // Not all operating systems will accept a locale of 'no'
    'sh' => 'sr-latn-rs',
  ];

  private const LANG_MAP_FLATPICKR = [
    'at'      => 'de-at',
    'cat'     => 'ca',
    'ckb'     => 'ku',
    'gr'      => 'el',
    'kz'      => 'kk',
    'sr-cyr'  => 'sr-cyrl',
    'uz-latn' => 'uz-latn-uz',
    'vn'      => 'vi'
  ];

  private const LANG_DIRS = [
    'mrbs' => [
      'dir' => MRBS_ROOT . '/lang',
      'prefix' => 'lang.',
      'suffix' => ''
    ],
    'flatpickr' => [
      'dir' => MRBS_ROOT . '/js/flatpickr/l10n',
      'prefix' => '',
      'suffix' => '.js',
      'lang_map' => self::LANG_MAP_FLATPICKR
    ]
  ];


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

    // Set the default character encoding
    ini_set('default_charset', 'UTF-8');

    // Set up mb_string internal encoding
    if (function_exists('mb_internal_encoding'))
    {
      mb_internal_encoding('UTF-8');
    }

    // Work out the preferred order of locales
    $preferences = self::getPreferences(
      $cli_language,
      $disable_automatic_language_changing,
      $default_language_tokens,
      $override_locale
    );
    self::debug('$preferences: ' . json_encode($preferences));

    $locale = self::getBestFit($preferences);
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
   * Convert a language alias to the real language.
   */
  private static function unAlias(string $lang) : string
  {
    if (!empty(self::LANG_ALIASES) && array_key_exists($lang, self::LANG_ALIASES))
    {
      return self::LANG_ALIASES[$lang];
    }

    return $lang;
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
    $result[] = 'en';

    // Remove any aliases
    return array_map([__CLASS__, 'unAlias'], $result);
  }


  private static function getBestFit(array $preferences) : string
  {
    foreach (self::LANG_DIRS as $package => $details)
    {
      $available_languages[$package] = self::getLangtags(...$details);
      self::debug("Available_languages($package): " . json_encode($available_languages[$package]));
    }

    foreach ($preferences as $locale)
    {
      // Check whether setlocale() is going to work
      self::debug("Trying '$locale'");
      if (false === Locale::acceptFromHttp($locale))
      {
        self::debug('locale: failed');
        continue;
      }
      self::debug('locale: OK');

      // Then check each of the packages to see if there's a language file matching this locale.
      foreach ($available_languages as $package => $tags)
      {
        if ('' === Locale::lookup($tags, $locale))
        {
          self::debug("$package: failed");
          continue 2;
        }
        self::debug("$package: OK");
      }

      // Nothing failed, so this locale will work
      return $locale;
    }

    return '';
  }


  // Gets all the language tags in a directory where the filenames are of the format
// $prefix . $lang . $suffix.  Returns an array.
  private static function getLangtags(string $dir, string $prefix='', string $suffix='', array $lang_map=[]) : array
  {
    // TODO: Use $lang_map
    // TODO: Do something with default language (eg Datatables default is 'en')
    $result = [];

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
          array_splice($result,
            array_search($langtag, $result),
            1,
            $lang_map[$langtag]
          );
        }
      }
    }

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
