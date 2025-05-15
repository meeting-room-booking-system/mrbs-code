<?php
declare(strict_types=1);

use MRBS\Utf8\Utf8String;

// These functions are deliberately in the global namespace so that they
// can be used in classes.  They are typically polyfills for PHP functions
// that only exist in later versions of PHP.


// Emulation of mb_strlen()
if (!function_exists('mb_strlen'))
{
  function mb_strlen(string $string, ?string $encoding=null) : int
  {
    if ($string === '')
    {
      return 0;
    }

    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      throw new InvalidArgumentException("This emulation of mb_strlen() only supports the UTF-8 encoding.");
    }

    return count((new Utf8String($string))->toArray());
  }
}


// Emulation of mb_substr()
if (!function_exists('mb_substr'))
{
  function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
  {
    if (isset($encoding) && ($encoding !== 'UTF-8'))
    {
      throw new InvalidArgumentException("This emulation of mb_substr() only supports the UTF-8 encoding.");
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
