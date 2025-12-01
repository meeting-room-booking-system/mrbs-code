<?php
declare(strict_types=1);
namespace MRBS\Intl;


/**
 * A partial emulation of the PHP \Collator class.
 */
class Collator
{
  public const DEFAULT_VALUE = -1;
  public const PRIMARY = 0;
  public const SECONDARY = 1;
  public const TERTIARY = 2;
  public const DEFAULT_STRENGTH = 2;
  public const QUATERNARY = 3;
  public const IDENTICAL = 15;
  public const OFF = 16;
  public const ON = 17;
  public const SHIFTED = 20;
  public const NON_IGNORABLE = 21;
  public const LOWER_FIRST = 24;
  public const UPPER_FIRST = 25;
  public const FRENCH_COLLATION = 0;
  public const ALTERNATE_HANDLING = 1;
  public const CASE_FIRST = 2;
  public const CASE_LEVEL = 3;
  public const NORMALIZATION_MODE = 4;
  public const STRENGTH = 5;
  public const HIRAGANA_QUATERNARY_MODE =6;
  public const NUMERIC_COLLATION = 7;
  public const SORT_REGULAR = 0;
  public const SORT_STRING = 1;
  public const SORT_NUMERIC = 2;

  private $locale;


  public function __construct(string $locale)
  {
    $this->locale = $locale;
  }


  /**
   * @see \Collator::asort
   */
  public function asort(array &$array, int $flags = self::SORT_REGULAR): bool
  {
    throw new \Exception("Not yet implemented");
  }
}
