<?php
declare(strict_types=1);
namespace MRBS;

// A class that provides emulations of the PHP mbstring functions.  This class should
// normally only be used by the emulation functions themselves or else test software.
use IntlChar;
use InvalidArgumentException;
use MRBS\Utf8\Utf8String;
use ValueError;

class Mbstring
{
  public static function mb_stripos(string $haystack, string $needle, int $offset=0, ?string $encoding = null)
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $function = mb_substr(__FUNCTION__, mb_strlen('mrbs_')) . '()';
      $message = "This emulation of $function only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // We could just convert $haystack and $needle to the same case and then use mb_strpos.
    // However, that would involve converting the whole of both strings, whereas Utf8StrposGeneric()
    // only converts those characters that are necessary.
    return self::Utf8StrposGeneric($haystack, $needle, $offset, true);
  }


  public static function mb_strpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $function = mb_substr(__FUNCTION__, mb_strlen('mrbs_')) . '()';
      $message = "This emulation of $function only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return self::Utf8StrposGeneric($haystack, $needle, $offset);
  }


  public static function mb_strripos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $function = mb_substr(__FUNCTION__, mb_strlen('mrbs_')) . '()';
      $message = "This emulation of $function only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return self::Utf8StrposGeneric($haystack, $needle, $offset, true, true);
  }


  public static function mb_strrpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $function = mb_substr(__FUNCTION__, mb_strlen('mrbs_')) . '()';
      $message = "This emulation of $function only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return self::Utf8StrposGeneric($haystack, $needle, $offset, false, true);
  }


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


  public static function mb_strtolower(string $string, ?string $encoding=null) : string
  {
    if ($string === '')
    {
      return $string;
    }

    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    if (method_exists('IntlChar', 'tolower'))
    {
      $result = '';
      $utf8_string = new Utf8String($string);
      foreach ($utf8_string->toArray() as $char)
      {
        $result .= IntlChar::tolower($char);
      }
      return $result;
    }

    // Last resort - use the ordinary strtolower().
    // The ordinary strtolower() will give unexpected results when the locale is set to
    // Turkish and will not convert the letter 'I'.
    return strtolower($string);
  }


  public static function mb_strtoupper(string $string, ?string $encoding=null) : string
  {
    if ($string === '')
    {
      return $string;
    }

    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    if (method_exists('IntlChar', 'toupper'))
    {
      $result = '';
      $utf8_string = new Utf8String($string);
      foreach ($utf8_string->toArray() as $char)
      {
        $result .= IntlChar::toupper($char);
      }
      return $result;
    }

    // Last resort - use the ordinary strtoupper().
    // The ordinary strtoupper() will give unexpected results when the locale is set to
    // Turkish and will not convert the letter 'i'.
    return strtoupper($string);
  }


  public static function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return implode('', array_slice((new Utf8String($string))->toArray(), $start, $length));
  }


  // A generic emulation of mb_strpos(), mb_stripos(), mb_strrpos() and mb_strripos() for UTF-8
  // that has the case-sensitivity and direction as parameters.
  // This function can be tested by test_mb.php in the mrbs-tools repository.
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
        mb_is_string_equal($haystack_chars[$h], $needle_chars[$n], $case_insensitive))
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

}
