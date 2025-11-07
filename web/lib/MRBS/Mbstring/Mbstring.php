<?php
declare(strict_types=1);
namespace MRBS\Mbstring;

use IntlChar;
use InvalidArgumentException;
use MRBS\Utf8\Utf8String;
use Normalizer;
use RuntimeException;
use Transliterator;
use ValueError;

/**
 * A class that provides emulations of the PHP mbstring functions.  This class should
 * normally only be used by the emulation functions themselves or else test software.
 * Only the UTF-8 encoding is supported.
 */

class Mbstring
{
  /**
   * A list of codepoints where the Transliterator provides a different result to
   * the mbstring library when converting to lowercase.
   */
  private const TRANSLITERATOR_LOWER_EXCEPTIONS = [
    0x1C89 => 0x1C8A,
    0xA7CB => 0x0264,
    0xA7CC => 0xA7CD,
    0xA7DA => 0xA7DB,
    0xA7DC => 0x019B,
    0x10D50 => 0x10D70,
    0x10D51 => 0x10D71,
    0x10D52 => 0x10D72,
    0x10D53 => 0x10D73,
    0x10D54 => 0x10D74,
    0x10D55 => 0x10D75,
    0x10D56 => 0x10D76,
    0x10D57 => 0x10D77,
    0x10D58 => 0x10D78,
    0x10D59 => 0x10D79,
    0x10D5A => 0x10D7A,
    0x10D5B => 0x10D7B,
    0x10D5C => 0x10D7C,
    0x10D5D => 0x10D7D,
    0x10D5E => 0x10D7E,
    0x10D5F => 0x10D7F,
    0x10D60 => 0x10D80,
    0x10D61 => 0x10D81,
    0x10D62 => 0x10D82,
    0x10D63 => 0x10D83,
    0x10D64 => 0x10D84,
    0x10D65 => 0x10D85
  ];

  /**
   * A list of codepoints where the Transliterator provides a different result to
   * the mbstring library when converting to uppercase.
   */
  private const TRANSLITERATOR_UPPER_EXCEPTIONS = [
    0x019B => 0xA7DC,  // LATIN SMALL LETTER LAMBDA WITH STROKE
    0x0264 => 0xA7CB,  // LATIN SMALL LETTER RAMS HORN
    0x1C8A => 0x1C89,
    0xA7CD => 0xA7CC,
    0xA7DB => 0xA7DA,
    0x10D70 => 0x10D50,
    0x10D71 => 0x10D51,
    0x10D72 => 0x10D52,
    0x10D73 => 0x10D53,
    0x10D74 => 0x10D54,
    0x10D75 => 0x10D55,
    0x10D76 => 0x10D56,
    0x10D77 => 0x10D57,
    0x10D78 => 0x10D58,
    0x10D79 => 0x10D59,
    0x10D7A => 0x10D5A,
    0x10D7B => 0x10D5B,
    0x10D7C => 0x10D5C,
    0x10D7D => 0x10D5D,
    0x10D7E => 0x10D5E,
    0x10D7F => 0x10D5F,
    0x10D80 => 0x10D60,
    0x10D81 => 0x10D61,
    0x10D82 => 0x10D62,
    0x10D83 => 0x10D63,
    0x10D84 => 0x10D64,
    0x10D85 => 0x10D65
  ];


  /**
   * Emulates mb_chr().  Only UTF-8 is supported.
   *
   * @return false|string
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   */
  public static function mb_chr(int $codepoint, ?string $encoding = null)
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // Check if it's a codepoint reserved for surrogates.
    if ((0xD800 <= $codepoint) && ($codepoint <= 0xDFFF))
    {
      return false;
    }

    // Use IntlChar if possible - it's the fastest method.
    if (method_exists('IntlChar', 'chr'))
    {
      return IntlChar::chr($codepoint);
    }

    // If not, use iconv(), if we can, which takes about 3 times as long as IntlChar.
    if (function_exists('iconv'))
    {
      return iconv('UCS-4LE', 'UTF-8', pack('V', $codepoint));
    }

    // Otherwise, use json_decode() which takes about 5 times as long as IntlChar.
    return json_decode('"' . self::FullJsonEscapedCodepoint($codepoint)  . '"') ?? false;
  }


  /**
   * Emulates mb_ord().  Only UTF-8 is supported.
   *
   * @return false|int
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   */
  public static function mb_ord(string $string, ?string $encoding=null)
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // Use IntlChar if possible - it's the fastest method.
    if (method_exists('IntlChar', 'ord'))
    {
      return IntlChar::ord($string) ?? false;
    }

    // Taken from https://stackoverflow.com/questions/9361303/can-i-get-the-unicode-value-of-a-character-or-vise-versa-with-php

    // If not, use iconv() if we can, which takes about 2.3 times as long as IntlChar.
    if (function_exists('iconv'))
    {
      return unpack('V', iconv('UTF-8', 'UCS-4LE', $string))[1];
    }

    // Otherwise do it the long way, which takes about 2.6 times as long as IntlChar.
    if (ord($string[0]) >= 0 && ord($string[0]) <= 127)
    {
      return ord($string[0]);
    }
    if (ord($string[0]) >= 192 && ord($string[0]) <= 223)
    {
      return (ord($string[0]) - 192) * 64 + (ord($string[1]) - 128);
    }
    if (ord($string[0]) >= 224 && ord($string[0]) <= 239)
    {
      return (ord($string[0]) - 224) * 4096 + (ord($string[1]) - 128) * 64 + (ord($string[2]) - 128);
    }
    if (ord($string[0]) >= 240 && ord($string[0]) <= 247)
    {
      return (ord($string[0]) - 240) * 262144 + (ord($string[1]) - 128) * 4096 + (ord($string[2]) - 128) * 64 + (ord($string[3]) - 128);
    }
    if (ord($string[0]) >= 248 && ord($string[0]) <= 251)
    {
      return (ord($string[0]) - 248) * 16777216 + (ord($string[1]) - 128) * 262144 + (ord($string[2]) - 128) * 4096 + (ord($string[3]) - 128) * 64 + (ord($string[4]) - 128);
    }
    if (ord($string[0]) >= 252 && ord($string[0]) <= 253)
    {
      return (ord($string[0]) - 252) * 1073741824 + (ord($string[1]) - 128) * 16777216 + (ord($string[2]) - 128) * 262144 + (ord($string[3]) - 128) * 4096 + (ord($string[4]) - 128) * 64 + (ord($string[5]) - 128);
    }
    if (ord($string[0]) >= 254 && ord($string[0]) <= 255)    //  error
    {
      return false;
    }

    return 0;
  }


  /**
   * Emulates mb_regex_encoding().  Only UTF-8 is supported.
   *
   * @return string|true
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   */
  public static function mb_regex_encoding(?string $encoding=null)
  {
    if (!isset($encoding))
    {
      return 'UTF-8';
    }

    if (strtoupper($encoding) === 'UTF-8')
    {
      return true;
    }

    $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
    throw new InvalidArgumentException($message);
  }


  /**
   * Emulates mb_split().  Only UTF-8 is supported.
   *
   * @return array|false|string[]
   */
  public static function mb_split(string $pattern, string $string, int $limit = -1)
  {
    return preg_split('/' . $pattern . '/u', $string, $limit);
  }


  /**
   * Emulates mb_stripos().  Only UTF-8 is supported.
   *
   * @return false|int
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   * @throws RuntimeException
   * @throws ValueError if **offset** is greater than the length of the **haystack** (PHP 8.0 onwards)
   */
  public static function mb_stripos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // We could just convert $haystack and $needle to the same case and then use mb_strpos.
    // However, that would involve converting the whole of both strings, whereas Utf8StrposGeneric()
    // only converts those characters that are necessary.
    return self::Utf8StrposGeneric($haystack, $needle, $offset, true);
  }


  /**
   * Emulates mb_strpos().  Only UTF-8 is supported.
   *
   * @return false|int
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   * @throws RuntimeException
   * @throws ValueError if **offset** is greater than the length of the **haystack** (PHP 8.0 onwards)
   */
  public static function mb_strpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return self::Utf8StrposGeneric($haystack, $needle, $offset);
  }


  /**
   * Emulates mb_strripos().  Only UTF-8 is supported.
   *
   * @return false|int
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   * @throws RuntimeException
   * @throws ValueError if **offset** is greater than the length of the **haystack** (PHP 8.0 onwards)
   */
  public static function mb_strripos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return self::Utf8StrposGeneric($haystack, $needle, $offset, true, true);
  }


  /**
   * Emulates mb_strrpos().  Only UTF-8 is supported.
   *
   * @return false|int
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   * @throws RuntimeException
   * @throws ValueError if **offset** is greater than the length of the **haystack** (PHP 8.0 onwards)
   */
  public static function mb_strrpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return self::Utf8StrposGeneric($haystack, $needle, $offset, false, true);
  }


  /**
   * Emulates mb_strrpos().  Only UTF-8, utf-8 and 8bit are supported.
   *
   * @throws InvalidArgumentException if **encoding** is not valid
   * @throws RuntimeException
   */
  public static function mb_strlen(string $string, ?string $encoding=null) : int
  {
    if ($string === '')
    {
      return 0;
    }

    switch ($encoding)
    {
      case null:
      case 'UTF-8':
      case 'utf-8':
        $result = count((new Utf8String($string))->toArray());
        break;
      case '8bit':
        $result = (new Utf8String($string))->byteCount();
        break;
      default:
        throw new InvalidArgumentException("Encoding '$encoding' is not supported.");
        break;
    }

    return $result;
  }


  /**
   * Emulates mb_strtolower().  Only UTF-8 is supported.
   *
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   */
  public static function mb_strtolower(string $string, ?string $encoding=null) : string
  {
    if ($string === '')
    {
      return $string;
    }

    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // If we can, use the Transliterator
    if (method_exists('Transliterator', 'transliterate'))
    {
      // Works better than IntlChar::toLower()
      // See https://stackoverflow.com/questions/79655507/what-is-the-difference-between-phps-mb-strolower-and-intlchartolower
      return self::TransliteratorToLower($string);
    }

    // Otherwise, use the (enhanced) ordinary strtolower().
    return Ordinary::strtolower($string);
  }


  /**
   * Emulates mb_strtoupper().  Only UTF-8 is supported.
   *
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   */
  public static function mb_strtoupper(string $string, ?string $encoding=null) : string
  {
    if ($string === '')
    {
      return $string;
    }

    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // If we can, use the Transliterator
    if (method_exists('Transliterator', 'transliterate'))
    {
      // Works better than IntlChar::toUpper()
      // See https://stackoverflow.com/questions/79655507/what-is-the-difference-between-phps-mb-strolower-and-intlchartolower
      return self::TransliteratorToUpper($string);
    }

    // Otherwise, use the (enhanced) ordinary strtoupper().
    return Ordinary::strtoupper($string);
  }


  /**
   * Emulates mb_substr().  Only UTF-8 is supported.
   *
   * @throws InvalidArgumentException if **encoding** is not UTF-8
   */
  public static function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
  {
    if (isset($encoding) && (strtoupper($encoding) !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return implode('', array_slice((new Utf8String($string))->toArray(), $start, $length));
  }


  /**
   * A generic emulation of mb_strpos(), mb_stripos(), mb_strrpos() and mb_strripos() for UTF-8
   * that has the case-sensitivity and direction as parameters.
   * This function can be tested by test_mb.php in the mrbs-tools repository.
   *
   * @return false|int
   * @throws RuntimeException
   * @throws ValueError if **offset** is greater than the length of the **haystack** (PHP 8.0 onwards)
   */
  private static function Utf8StrposGeneric(string $haystack, string $needle, int $offset=0, bool $case_insensitive=false, bool $reverse=false)
  {
    $haystack_chars = (new Utf8String($haystack))->toArray();
    $last_haystack_index = count($haystack_chars) - 1;
    $needle_chars = (new Utf8String($needle))->toArray();
    $last_needle_index = count($needle_chars) - 1;

    if ($reverse)
    {
      // We're looking for the last occurrence of the needle in the haystack, so we
      // start at the right-hand end of the haystack, going backwards through the
      // haystack looking for the right-hand end of the needle.  If we find the end
      // of the needle, we keep going backwards through the needle and haystack,
      // checking that all the characters match.
      $increment = -1;
      if ($offset < 0)
      {
        // The PHP mb_strrpos() documentation isn't very clear.  If there's a negative offset
        // then it looks for a needle that can *start* anywhere up to and including the offset
        // character.  It doesn't matter if part of the needle is to the right of the offset.
        $haystack_start = min($last_haystack_index, $last_haystack_index + count($needle_chars) + $offset);
        $haystack_end = 0;
      }
      else
      {
        $haystack_start = $last_haystack_index;
        $haystack_end = $offset;
      }
      $needle_start = $last_needle_index;
      $needle_end = 0;
    }
    else
    {
      // We're looking for the first occurrence of the needle in the haystack, so we
      // start at the left-hand end of the haystack, going forwards through the
      // haystack looking for the left-hand end of the needle.  If we find the end
      // of the needle, we keep going forwards through the needle and haystack,
      // checking that all the characters match.
      $increment = 1;
      // Note the +1 for negative offsets n the line below: it's the way the PHP implementation works
      $haystack_start = ($offset < 0) ? $last_haystack_index + $offset + 1 : $offset;
      $haystack_end = $last_haystack_index;
      $needle_start = 0;
      $needle_end = $last_needle_index;
    }

    // Check that $offset is sensible.  (The PHP implementation allows an offset of +/-N on a
    // string of length N. The error reporting in the PHP implementation depends on the PHP version.)
    if (abs($offset) > $last_haystack_index + 1)
    {
      $message = __FUNCTION__ . '(): Argument #3 ($offset) must be contained in argument #1 ($haystack)';

      if ((version_compare(PHP_VERSION, '8') >= 0))
      {
        throw new ValueError($message);
      }
      else
      {
        $message = __FUNCTION__ . '(): Offset not contained in string';
        trigger_error($message, E_USER_WARNING);
        return false;
      }
    }

    // Special case: an empty needle is found immediately
    if ($needle === '')
    {
      return $haystack_start;
    }

    // Quick test: if the needle is longer than the part of the haystack to
    // be searched, then we'll never find it.
    if ($reverse)
    {
      if ($offset >= 0)
      {
        if ((count($needle_chars) > $haystack_start + 2 - $haystack_end))
        {
          return false;
        }
      }
      elseif (count($needle_chars) > count($haystack_chars))
      {
        return false;
      }
    }
    elseif (count($needle_chars) > $haystack_end + 1 - $haystack_start)
    {
      return false;
    }

    // Otherwise, start searching through the haystack for the needle (going either backwards
    // or forwards depending on the value of $increment).
    $n = $needle_start;

    for ($h = $haystack_start; ($increment > 0) ? $h <= $haystack_end : $h >= $haystack_end; $h += $increment)
    {
      // If we get a match with a needle character, then keep cycling through
      // the haystack and needle. If we get to the end of the needle and all
      // characters have matched, then we've found the needle; otherwise we
      // reset and start looking for the needle again.
      while ((($increment > 0) ? $h <= $haystack_end : $h >= $haystack_end) && isset($needle_chars[$n]) &&
             self::isStringEqual($haystack_chars[$h], $needle_chars[$n], $case_insensitive))
      {
        if ($n === $needle_end)
        {
          // We've found the needle, as we've got to the end and all characters match
          return $h - $n; // to get the starting index of the needle.
        }
        $h += $increment;
        $n += $increment;
      }
      // No match, reset the needle index
      $n = $needle_start;
    }

    return false;
  }


  private static function TransliteratorToLower(string $string) : string
  {
    // There are some characters that the Transliterator treats differently from the mbstring
    // library, so we have to split the string into characters and deal with them one by one.
    // Remember, we are only trying to emulate the mbstring library, not provide the "correct"
    // result.
    $result = '';
    $utf8_string = new Utf8String($string);

    foreach ($utf8_string->toArray() as $char)
    {
      $codepoint = IntlChar::ord($char);
      if (array_key_exists($codepoint, self::TRANSLITERATOR_LOWER_EXCEPTIONS))
      {
        $result .= IntlChar::chr(self::TRANSLITERATOR_LOWER_EXCEPTIONS[$codepoint]);
      }
      else
      {
        $result .= Transliterator::create('Lower')->transliterate($char);
      }
    }

    return $result;
  }


  private static function TransliteratorToUpper(string $string) : string
  {
    // There are some characters that the Transliterator treats differently from the mbstring
    // library, so we have to split the string into characters and deal with them one by one.
    // Remember, we are only trying to emulate the mbstring library, not provide the "correct"
    // result.
    $result = '';
    $utf8_string = new Utf8String($string);

    foreach ($utf8_string->toArray() as $char)
    {
      $codepoint = IntlChar::ord($char);
      if (array_key_exists($codepoint, self::TRANSLITERATOR_UPPER_EXCEPTIONS))
      {
        $result .= IntlChar::chr(self::TRANSLITERATOR_UPPER_EXCEPTIONS[$codepoint]);
      }
      else
      {
        $result .= Transliterator::create('Upper')->transliterate($char);
      }
    }

    return $result;
  }


  private static function SingleJsonEscapedCodepoint(int $codepoint) : string
  {
    return '\u' . str_pad(strtoupper(dechex($codepoint)), 4, '0', STR_PAD_LEFT);
  }


  private static function FullJsonEscapedCodepoint(int $codepoint) : string
  {
    if ($codepoint > 0x10FFFF)
    {
      throw new InvalidArgumentException('Codepoint cannot be greater than 0x10FFFF');
    }

    // The simple case: codepoints in the Basic Multilingual Plane
    if ($codepoint < 0x10000)
    {
      return self::SingleJsonEscapedCodepoint($codepoint);
    }

    // Otherwise, we have to split the codepoint into surrogate pairs. See https://en.wikipedia.org/wiki/UTF-16
    // Subtract 0x10000, leaving a 20-bit number
    $codepoint -= 0x10000;

    // For the leading surrogate, take the high 10 bits and add 0xd800
    $leading = self::SingleJsonEscapedCodepoint(($codepoint >> 10) + 0xD800);

    // For the trailing surrogate, take the low 10 bits and add 0xdc00
    $trailing = self::SingleJsonEscapedCodepoint(($codepoint & 0x3FF) + 0xDC00);

    return $leading . $trailing;
  }


  /**
   * Test to see if two strings are equal, optionally case-insensitively.
   * See https://stackoverflow.com/questions/5473542/case-insensitive-string-comparison
   */
  private static function isStringEqual(string $string1, string $string2, bool $case_insensitive=false) : bool
  {
    // Case-sensitive test
    if (!$case_insensitive)
    {
      return ($string1 === $string2);
    }

    // Case-insensitive
    // Quick test to see if they are equal in a case-sensitive fashion.
    if ($string1 === $string2)
    {
      return true;
    }
    // Otherwise do a case-insensitive check
    if (method_exists('Normalizer', 'normalize'))
    {
      $string1 = Normalizer::normalize($string1, Normalizer::FORM_KC);
      $string2 = Normalizer::normalize($string2, Normalizer::FORM_KC);
    }
    return ((mb_strtolower($string1) === mb_strtolower($string2)) ||
            (mb_strtoupper($string1) === mb_strtoupper($string2)));
  }

}
