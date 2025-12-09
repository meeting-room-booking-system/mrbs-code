<?php
declare(strict_types=1);
namespace MRBS\Intl;


use Exception;

/**
 * A partial emulation of the PHP \Collator class.  Some methods are not implemented, and
 * some attributes are not supported.
 * @see \Collator
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

  /**
   * Default values for attributes.
   * @see \Collator
   */
  private const ATTRIBUTES_DEFAULT_VALUES = [
    self::FRENCH_COLLATION => self::OFF,
    self::ALTERNATE_HANDLING => self::NON_IGNORABLE,
    self::CASE_FIRST => self::OFF,
    self::CASE_LEVEL => self::OFF,
    self::NORMALIZATION_MODE => self::OFF,
    self::STRENGTH => self::DEFAULT_STRENGTH,
    self::HIRAGANA_QUATERNARY_MODE => self::OFF,
    self::NUMERIC_COLLATION => self::OFF
  ];

  /**
  * Possible values for attributes.
  * @see \Collator
  */
  private const ATTRIBUTES_POSSIBLE_VALUES = [
    self::FRENCH_COLLATION => [self::ON, self::OFF, self::DEFAULT_VALUE],
    self::ALTERNATE_HANDLING => [self::NON_IGNORABLE, self::SHIFTED, self::DEFAULT_VALUE],
    self::CASE_FIRST => [self::OFF, self::LOWER_FIRST, self::UPPER_FIRST, self::DEFAULT_VALUE],
    self::CASE_LEVEL => [self::OFF, self::ON, self::DEFAULT_VALUE],
    self::NORMALIZATION_MODE => [self::OFF, self::ON, self::DEFAULT_VALUE],
    self::STRENGTH => [self::PRIMARY, self::SECONDARY, self::TERTIARY, self::QUATERNARY, self::IDENTICAL, self::DEFAULT_STRENGTH],
    self::HIRAGANA_QUATERNARY_MODE => [self::OFF, self::ON, self::DEFAULT_VALUE],
    self::NUMERIC_COLLATION => [self::OFF, self::ON, self::DEFAULT_VALUE],
  ];

  private $attributes = [];
  private $locale;


  /**
   * @see \Collator::__construct()
   */
  public function __construct(string $locale)
  {
    $this->locale = $locale;
    // Set the default values for the attributes
    foreach(self::ATTRIBUTES_DEFAULT_VALUES as $attribute => $default_value)
    {
      $this->setAttribute($attribute, $default_value);
    }
  }


  /**
   * @see \Collator::asort()
   */
  public function asort(array &$array, int $flags = self::SORT_REGULAR): bool
  {
    $locale_switcher = new LocaleSwitcher(LC_COLLATE, $this->locale);
    $locale_switcher->switch();
    // Do the sort in the current locale
    // Convert the flags to the equivalent value for the ordinary function asort().
    switch ($flags)
    {
      case self::SORT_REGULAR:
        $ordinary_flags = SORT_REGULAR | SORT_LOCALE_STRING;
        break;
      case self::SORT_STRING:
        $ordinary_flags = SORT_STRING | SORT_LOCALE_STRING;
        break;
      case self::SORT_NUMERIC:
        $ordinary_flags = SORT_NUMERIC;
        break;
      default:
        throw new \InvalidArgumentException("Invalid flags value '$flags'");
        break;
    }

    // If NUMERIC_COLLATION is on, then use SORT_NATURAL.
    if (in_array($flags, [self::SORT_REGULAR, self::SORT_STRING], true) &&
        $this->getAttribute(self::NUMERIC_COLLATION) === self::ON)
    {
      $ordinary_flags |= SORT_NATURAL;
    }

    asort($array, $ordinary_flags);
    $locale_switcher->restore();
    return true;
  }


  /**
   * @return int<-1,1>|false
   * @see \Collator::compare()
   */
  public function compare(string $string1, string $string2)
  {
    // Trivial case
    if ($string1 === $string2)
    {
      return 0;
    }

    // Sort the array.  If the order is reversed, then $string1 > $string2.
    // (When this function is being used as a callback for usort, and if the original array
    // is sorted in ascending order - which it well might be if it's the result of
    // an SQL query with an ORDER BY - then it's fastest to test for $string1 > $string2
    // first, as below.)
    $original_array = [$string1, $string2];
    $array = $original_array;
    $this->asort($array);
    if ($array !== $original_array)
    {
      return 1;
    }

    // Otherwise, flip the array and try again.  If the order is reversed, then $string2 > $string1.
    $original_array = [$string2, $string1];
    $array = $original_array;
    $this->asort($array);
    if ($array !== $original_array)
    {
      return -1;
    }

    // Otherwise they must be equal
    return 0;
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
    if (!array_key_exists($attribute, $this->attributes))
    {
      return false;
    }

    return $this->attributes[$attribute];
  }


  /**
   * @return int|false
   * @see \Collator::getErrorCode()
   */
  public function getErrorCode()
  {
    throw new Exception("Not yet implemented");
  }


  /**
   * @return string|false
   * @see \Collator::getErrorMessage()
   */
  public function getErrorMessage()
  {
    throw new Exception("Not yet implemented");
  }


  /**
   * @return string|false
   * @see \Collator::getLocale()
   */
  public function getLocale()
  {
    throw new Exception("Not yet implemented");
  }


  /**
   * @return string|false
   * @see \Collator::getSortKey()
   */
  public function getSortKey(string $string)
  {
    throw new Exception("Not yet implemented");
  }


  /**
   * @see \Collator::getStrength()
   */
  public function getStrength(): int
  {
    return $this->getAttribute(self::STRENGTH);
  }


  /**
   * @see \Collator::setAttribute()
   */
  public function setAttribute(int $attribute, int $value): bool
  {
    if (!in_array($value, self::ATTRIBUTES_POSSIBLE_VALUES[$attribute]))
    {
      return false;
    }

    // TODO: The manual (https://www.php.net/manual/en/class.collator.php#collator.constants.french-collation)
    // TODO: says that FRENCH_COLLATION "is automatically set to On for the French locales and a few others".
    // TODO: However, this doesn't seem to be the case in testing: it's always Off.  Probably doesn't matter
    // TODO: in practice though as this emulator won't be able to do anything about it anyway.
    $this->attributes[$attribute] = ($value === self::DEFAULT_VALUE) ? self::ATTRIBUTES_DEFAULT_VALUES[$attribute] : $value;

    return true;
  }


  /**
   * @see \Collator::setStrength()
   * @return true
   */
  public function setStrength(int $strength)
  {
    $this->setAttribute(self::STRENGTH, $strength);
    return true;
  }


  /**
   * @see \Collator::sort()
   */
  public function sort(array &$array, int $flags = self::SORT_REGULAR): bool
  {
    throw new Exception("Not yet implemented");
  }


  /**
   * @see \Collator::sortWithSortKeys()
   */
  public function sortWithSortKeys(array &$array): bool
  {
    throw new Exception("Not yet implemented");
  }

}
