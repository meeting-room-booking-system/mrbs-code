<?php
declare(strict_types=1);
namespace MRBS;

class Language
{
  private static $override_locale;


  /**
   * @param string|null $override_locale a locale in BCP 47 format, eg 'en-GB'
   */
  public static function init(?string $override_locale) : void
  {
    // Set the default character encoding
    ini_set('default_charset', 'UTF-8');

    // Set up mb_string internal encoding
    if (function_exists('mb_internal_encoding'))
    {
      mb_internal_encoding('UTF-8');
    }

    self::$override_locale = $override_locale;
  }

}
