<?php
declare(strict_types=1);

use MRBS\Utf8\Utf8String;

// These functions are deliberately in the global namespace so that they
// can be used in classes.  They are either polyfills for PHP functions
// that only exist in later versions of PHP, or else emulations of the
// mb_ string functions that aren't always present.


// Emulation of mb_stripos()
if (!function_exists('mb_stripos'))
{
  function mb_stripos(string $haystack, string $needle, int $offset=0, ?string $encoding = null)
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    // TODO: do something better
    return stripos($haystack, $needle, $offset);
  }
}


// Emulation of mb_strlen()
// Use mb_strlen() with '8bit' encoding to get a string's length in bytes
// because strlen() may have been overloaded by mb_strlen().
if (!function_exists('mb_strlen'))
{
  function mb_strlen(string $string, ?string $encoding=null) : int
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
}


// Emulation of mb_strpos()
if (!function_exists('mb_strpos'))
{
  function my_mb_strpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    $haystack_chars = (new Utf8String($haystack))->toArray();
    $last_haystack_index = count($haystack_chars) - 1;
    $needle_chars = (new Utf8String($needle))->toArray();
    $last_needle_index = count($needle_chars) - 1;
    // Note the +1 for negative offsets n the line below: it's the way the PHP implementation works
    $start = ($offset < 0) ? $last_haystack_index + $offset + 1 : $offset;
    $n = 0;

    // Check that $offset is sensible.  (The PHP implementation allows an offset of +/-N on a
    // string of length N. The error reporting in the PHP implementation depends on the PHP version.)
    if (abs($offset) > $last_haystack_index + 1)
    {
      $message = 'mb_strpos(): Argument #3 ($offset) must be contained in argument #1 ($haystack)';
      if ((version_compare(PHP_VERSION, '8') >= 0))
      {
        throw new ValueError($message);
      }
      else
      {
        $message = "mb_strpos(): Offset not contained in string";
        trigger_error($message, E_USER_WARNING);
        return false;
      }
    }

    // Special case: an empty needle is found immediately
    if ($needle === '')
    {
      return $start;
    }

    // Quick test: if the needle is longer than the part of the haystack to
    // be searched, then we'll never find it.
    if (count($needle_chars) > $last_haystack_index + 1 - $start)
    {
      return false;
    }

    // Otherwise, start searching through the haystack for the needle
    for ($h = $start; $h <= $last_haystack_index; $h++)
    {
      // If we get a match with a needle character, then keep cycling through
      // the haystack and needle. If we get to the end of the needle and all
      // characters have matched, then we've found the needle, otherwise we
      // reset and start looking for the needle again.
      while (isset($haystack_chars[$h]) && isset($needle_chars[$n]) &&
        ($haystack_chars[$h] === $needle_chars[$n]))
      {
        if ($n === $last_needle_index)
        {
          return $h - $n; // to get the starting index of the needle.
        }
        $h++;
        $n++;
      }
      $n = 0;
    }

    return false;
  }
}


// Emulation of mb_strtolower
if (!function_exists('mb_strtolower'))
{
  function mb_strtolower(string $string, ?string $encoding=null) : string
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
}


// Emulation of mb_strtoupper
if (!function_exists('mb_strtoupper'))
{
  function mb_strtoupper(string $string, ?string $encoding=null) : string
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
}


// Emulation of mb_substr()
if (!function_exists('mb_substr'))
{
  function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      $message = "This emulation of " . __FUNCTION__ . "() only supports the UTF-8 encoding.";
      throw new InvalidArgumentException($message);
    }

    return implode('', array_slice((new Utf8String($string))->toArray(), $start, $length));
  }
}


// Emulates the PHP 8 function str_contains()
if (!function_exists('str_contains'))
{
  function str_contains(string $haystack, string $needle) : bool
  {
    if (!isset($needle) || ($needle === ''))
    {
      // This is the way the PHP function behaves
      return true;
    }
    else
    {
      // This works with multibyte characters because
      // we are not worried about the actual position.
      return (strpos($haystack, $needle) !== false);
    }
  }
}


// Emulates the PHP 8 function str_starts_with()
if (!function_exists('str_starts_with'))
{
  function str_starts_with(string $haystack, string $needle) : bool
  {
    if (!isset($needle) || ($needle === ''))
    {
      // This is the way the PHP function behaves
      return true;
    }
    else
    {
      // This works with multibyte characters because
      // we are looking for position zero.
      return (strpos($haystack, $needle) === 0);
    }
  }
}


// Emulates the PHP 8 function str_ends_with()
if (!function_exists('str_ends_with'))
{
  function str_ends_with(string $haystack, string $needle) : bool
  {
    if (!isset($needle) || ($needle === ''))
    {
      // This is the way the PHP function behaves
      return true;
    }
    else
    {
      // This works with multibyte characters because strpos() and strlen()
      // will either both be overloaded with the mb_ versions or both not.
      return (strpos($haystack, $needle) === strlen($haystack) - strlen($needle));
    }
  }
}
