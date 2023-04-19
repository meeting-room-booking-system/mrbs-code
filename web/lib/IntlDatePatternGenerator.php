<?php

// A basic emulation of the PHP IntlDatePatternGenerator.
// Will only be loaded if the PHP class doesn't exist.

class IntlDatePatternGenerator
{
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
    $file = MRBS_ROOT . "/lib/IntlDatePatternGenerator/skeletons/$skeleton.ini";

    if (is_readable($file))
    {
      $patterns = parse_ini_file($file);
      if (!empty($patterns))
      {
        return $patterns[$this->locale] ?? $patterns['default'] ?? false;
      }
    }

    return false;
  }
}
