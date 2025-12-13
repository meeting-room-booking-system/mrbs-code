<?php
declare(strict_types=1);
namespace MRBS\Intl;


/**
 * A partial, very basic, emulation of the PHP \NumberFormatter class.  Some methods are not implemented,
 * and some attributes are not supported.  This class is only intended as a very basic fallback if the
 * intl extension is not available. For best results use the intl extension.
 * @see \NumberFormatter
 */
class NumberFormatter
{
  public const PATTERN_DECIMAL = 0;
  public const DECIMAL = 1;
  public const CURRENCY = 2;
  public const PERCENT = 3;
  public const SCIENTIFIC = 4;
  public const SPELLOUT = 5;
  public const ORDINAL = 6;
  public const DURATION = 7;
  public const PATTERN_RULEBASED = 9;
  public const IGNORE = 0;
  public const CURRENCY_ACCOUNTING = 12;
  public const DECIMAL_COMPACT_SHORT = 14; // PHP 8.5 onwards
  public const DECIMAL_COMPACT_LONG= 15;  // PHP 8.5 onwards
  public const DEFAULT_STYLE = 1;
  public const ROUND_CEILING = 0;
  public const ROUND_FLOOR = 1;
  public const ROUND_DOWN = 2;
  public const ROUND_UP = 3;
  public const ROUND_TOWARD_ZERO = 2;
  public const ROUND_AWAY_FROM_ZERO = 3;
  public const ROUND_HALFEVEN = 4;
  public const ROUND_HALFODD = 8;
  public const ROUND_HALFDOWN = 5;
  public const ROUND_HALFUP = 6;
  public const PAD_BEFORE_PREFIX = 0;
  public const PAD_AFTER_PREFIX = 1;
  public const PAD_BEFORE_SUFFIX = 2;
  public const PAD_AFTER_SUFFIX = 3;
  public const PARSE_INT_ONLY = 0;
  public const GROUPING_USED = 1;
  public const DECIMAL_ALWAYS_SHOWN = 2;
  public const MAX_INTEGER_DIGITS = 3;
  public const MIN_INTEGER_DIGITS = 4;
  public const INTEGER_DIGITS = 5;
  public const MAX_FRACTION_DIGITS = 6;
  public const MIN_FRACTION_DIGITS = 7;
  public const FRACTION_DIGITS = 8;
  public const MULTIPLIER = 9;
  public const GROUPING_SIZE = 10;
  public const ROUNDING_MODE = 11;
  public const ROUNDING_INCREMENT = 12;
  public const FORMAT_WIDTH = 13;
  public const PADDING_POSITION = 14;
  public const SECONDARY_GROUPING_SIZE = 15;
  public const SIGNIFICANT_DIGITS_USED = 16;
  public const MIN_SIGNIFICANT_DIGITS = 17;
  public const MAX_SIGNIFICANT_DIGITS = 18;
  public const LENIENT_PARSE = 19;
  public const POSITIVE_PREFIX = 0;
  public const POSITIVE_SUFFIX = 1;
  public const NEGATIVE_PREFIX = 2;
  public const NEGATIVE_SUFFIX = 3;
  public const PADDING_CHARACTER = 4;
  public const CURRENCY_CODE = 5;
  public const DEFAULT_RULESET = 6;
  public const PUBLIC_RULESETS = 7;
  public const DECIMAL_SEPARATOR_SYMBOL = 0;
  public const GROUPING_SEPARATOR_SYMBOL = 1;
  public const PATTERN_SEPARATOR_SYMBOL = 2;
  public const PERCENT_SYMBOL = 3;
  public const ZERO_DIGIT_SYMBOL = 4;
  public const DIGIT_SYMBOL = 5;
  public const MINUS_SIGN_SYMBOL = 6;
  public const PLUS_SIGN_SYMBOL = 7;
  public const CURRENCY_SYMBOL = 8;
  public const INTL_CURRENCY_SYMBOL = 9;
  public const MONETARY_SEPARATOR_SYMBOL = 10;
  public const EXPONENTIAL_SYMBOL = 11;
  public const PERMILL_SYMBOL = 12;
  public const PAD_ESCAPE_SYMBOL = 13;
  public const INFINITY_SYMBOL = 14;
  public const NAN_SYMBOL = 15;
  public const SIGNIFICANT_DIGIT_SYMBOL = 16;
  public const MONETARY_GROUPING_SEPARATOR_SYMBOL = 17;
  public const TYPE_DEFAULT = 0;
  public const TYPE_INT32 = 1;
  public const TYPE_INT64 = 2;
  public const TYPE_DOUBLE = 3;
  public const TYPE_CURRENCY = 4;
  public const CURRENCY_ISO = 10; // PHP 8.5 onwards
  public const CURRENCY_PLURAL = 11; // PHP 8.5 onwards
  public const CASH_CURRENCY = 13; // PHP 8.5 onwards
  public const CURRENCY_STANDARD = 16; // PHP 8.5 onwards

}
