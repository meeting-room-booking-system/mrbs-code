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
  public const HIRAGANA_QUATERNARY_MODE = 6;
  public const NUMERIC_COLLATION = 7;
  public const SORT_REGULAR = 0;
  public const SORT_STRING = 1;
  public const SORT_NUMERIC = 2;

  private $locale;


  /**
   * @see \Collator::__construct()
   */
  public function __construct(string $locale)
  {
    $this->locale = $locale;
  }


  /**
   * @see \Collator::asort()
   */
  public function asort(array &$array, int $flags = self::SORT_REGULAR): bool
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @return int<-1,1>|false
   * @see \Collator::compare()
   */
  public function compare(string $string1, string $string2)
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @see \Collator::create()
   */
  public static function create(string $locale): ?self
  {
    return new self($locale);
  }


  /**
   * @return int|false
   * @see \Collator::getAttribute()
   */
  public function getAttribute(int $attribute)
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @return int|false
   * @see \Collator::getErrorCode()
   */
  public function getErrorCode()
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @return string|false
   * @see \Collator::getErrorMessage()
   */
  public function getErrorMessage()
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @return string|false
   * @see \Collator::getLocale()
   */
  public function getLocale()
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @return string|false
   * @see \Collator::getSortKey()
   */
  public function getSortKey(string $string)
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @see \Collator::getStrength()
   */
  public function getStrength(): int
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @see \Collator::setAttribute()
   */
  public function setAttribute(int $attribute, int $value): bool
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @see \Collator::setStrength()
   * @return true
   */
  public function setStrength(int $strength)
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @see \Collator::sort()
   */
  public function sort(array &$array, int $flags = self::SORT_REGULAR): bool
  {
    throw new \Exception("Not yet implemented");
  }


  /**
   * @see \Collator::sortWithSortKeys()
   */
  public function sortWithSortKeys(array &$array): bool
  {
    throw new \Exception("Not yet implemented");
  }

}
