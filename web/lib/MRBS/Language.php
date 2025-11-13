<?php
declare(strict_types=1);
namespace MRBS;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use MRBS\Errors\Formatter\ErrorLogFormatter;

class Language
{
  // A map of language aliases
  private const LANG_ALIASES= [
    'no' => 'nb', // Not all operating systems will accept a locale of 'no'
    'sh' => 'sr-latn-rs',
  ];

  private static $aliased_header;
  private static $override_locale;


  /**
   * @param string|null $override_locale a locale in BCP 47 format, eg 'en-GB'
   */
  public static function init(?string $override_locale) : void
  {
    global $server;

    // Set the default character encoding
    ini_set('default_charset', 'UTF-8');

    // Set up mb_string internal encoding
    if (function_exists('mb_internal_encoding'))
    {
      mb_internal_encoding('UTF-8');
    }

    self::$override_locale = $override_locale;
    self::debug('$override_locale: ' . ($override_locale ?? 'NULL'));

    // Construct the aliased header
    self::$aliased_header = (isset($server['HTTP_ACCEPT_LANGUAGE'])) ? self::aliasHeader($server['HTTP_ACCEPT_LANGUAGE']) : null;
    self::debug('HTTP_ACCEPT_LANGUAGE: ' . ($server['HTTP_ACCEPT_LANGUAGE'] ?? 'NULL'));
    self::debug('Aliased header: ' . (self::$aliased_header ?? 'NULL'));
  }


  /**
   * Returns a version of the Accept-Language request HTTP header with language
   * strings substituted for their aliases.
   */
  private static function aliasHeader(string $header) : string
  {
    if (!empty(self::LANG_ALIASES))
    {
      $patterns = array();
      $replacements = array();

      foreach (self::LANG_ALIASES as $key => $value)
      {
        $patterns[] = "/(?<=^|,)($key)(?=,|;|$)/i";
        $replacements[] = $value;
      }

      $header = preg_replace($patterns, $replacements, $header);
    }

    return $header;
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
