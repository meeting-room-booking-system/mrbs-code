<?php
declare(strict_types=1);
namespace MRBS\Intl;

use MRBS\Exception;
use MRBS\Language;

/**
 * A class providing a basic emulation of PHP's IntlDatePatternGenerator class.
 */
class IntlDatePatternGenerator
{
  private const DEFAULT_LOCALE = 'en';

  private $locale;

  // $locale  The locale. If null is passed, uses the ini setting intl.default_locale.
  public function __construct(?string $locale = null)
  {
    if (!isset($locale)) {
      $locale = ini_get('intl.default_locale');
      if (($locale === false) || ($locale === '')) {
        throw new Exception("Could not get locale");
      }
    }

    $this->locale = $locale;
  }


  public function getBestPattern(string $skeleton)
  {
    $file = MRBS_ROOT . "/intl/skeletons/$skeleton.ini";

    if (is_readable($file)) {
      $patterns = parse_ini_file($file);
      if (!empty($patterns)) {
        return $patterns[Language::convertToBcp47($this->locale)] ?? $patterns[self::DEFAULT_LOCALE] ?? false;
      }
    }

    return false;
  }
}
