<?php
declare(strict_types=1);

use MRBS\Mbstring\Mbstring;

// These functions are deliberately in the global namespace so that they
// can be used in classes.  They are either polyfills for PHP functions
// that only exist in later versions of PHP, or else emulations of the
// mb_ string functions that aren't always present.

if (!function_exists('json_validate'))
{
  function json_validate(string $json, int $depth = 512, int $flags = 0): bool
  {
    json_decode($json, null, $depth, $flags);
    return json_last_error() === JSON_ERROR_NONE;
  }
}


// Emulation of mb_ord()
if (!function_exists('mb_ord'))
{
  function mb_ord(string $string, ?string $encoding=null)
  {
    return Mbstring::mb_ord($string, $encoding);
  }
}


// Emulation of mb_stripos()
if (!function_exists('mb_stripos'))
{
  function mb_stripos(string $haystack, string $needle, int $offset=0, ?string $encoding = null)
  {
    return Mbstring::mb_stripos($haystack, $needle, $offset, $encoding);
  }
}


// Emulation of mb_strlen()
// Use mb_strlen() with '8bit' encoding to get a string's length in bytes
// because strlen() may have been overloaded by mb_strlen().
if (!function_exists('mb_strlen'))
{
  function mb_strlen(string $string, ?string $encoding=null) : int
  {
    return Mbstring::mb_strlen($string, $encoding);
  }
}


// Emulation of mb_strpos()
if (!function_exists('mb_strpos'))
{
  function mb_strpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    return Mbstring::mb_strpos($haystack, $needle, $offset, $encoding);
  }
}


// Emulation of mb_strripos()
if (!function_exists('mb_strripos'))
{
  function mb_strripos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    return Mbstring::mb_strripos($haystack, $needle, $offset, $encoding);
  }
}


// Emulation of mb_strrpos()
if (!function_exists('mb_strrpos'))
{
  function mb_strrpos(string $haystack, string $needle, int $offset=0, ?string $encoding=null)
  {
    return Mbstring::mb_strrpos($haystack, $needle, $offset, $encoding);
  }
}


// Emulation of mb_strtolower()
if (!function_exists('mb_strtolower'))
{
  function mb_strtolower(string $string, ?string $encoding=null) : string
  {
    return Mbstring::mb_strtolower($string, $encoding);
  }
}


// Emulation of mb_strtoupper()
if (!function_exists('mb_strtoupper'))
{
  function mb_strtoupper(string $string, ?string $encoding=null) : string
  {
    return Mbstring::mb_strtoupper($string, $encoding);
  }
}


// Emulation of mb_substr()
if (!function_exists('mb_substr'))
{
  function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
  {
    return Mbstring::mb_substr($string, $start, $length, $encoding);
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
