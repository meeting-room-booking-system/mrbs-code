<?php

// These functions are deliberately in the global namespace so that they
// can be used in classes.  They are typically polyfills for PHP functions
// that only exist in later versions of PHP.


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
