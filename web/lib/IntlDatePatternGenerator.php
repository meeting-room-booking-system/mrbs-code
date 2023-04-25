<?php

// A basic emulation of the PHP IntlDatePatternGenerator.
// Will only be loaded if the PHP class doesn't exist.

use function MRBS\convert_to_BCP47;

class IntlDatePatternGenerator
{
  private const DEFAULT_LOCALE = 'en';

  private $locale;

  // $locale  The locale. If null is passed, uses the ini setting intl.default_locale.
  public function __construct(?string $locale = null)
  {
    if (!isset($locale))
    {
      $locale = ini_get('intl.default_locale');
      if (($locale === false) || ($locale === ''))
      {
        throw new Exception("Could not get locale");
      }
    }

    $this->locale = $locale;
  }


  public function getBestPattern(string $skeleton)
  {
    $file = MRBS_ROOT . "/intl/skeletons/$skeleton.ini";

    if (is_readable($file))
    {
      $patterns = parse_ini_file($file);
      if (!empty($patterns))
      {
        return $patterns[convert_to_BCP47($this->locale)] ?? $patterns[self::DEFAULT_LOCALE] ?? false;
      }
    }

    return false;
  }
}
