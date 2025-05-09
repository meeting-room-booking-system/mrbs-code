<?php
declare(strict_types=1);

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


if (!function_exists('mb_strlen'))
{
  // TODO: extra parameters for mb_strlen()?
  function mb_strlen(string $string) : int
  {
    if ($string === '')
    {
      return 0;
    }

    $length = 1;
    $next = 0;
    while ($next = utf8_next_index($string, $next))
    {
      $length++;
    }

    return $length;
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


// Takes a UTF-8 string and a byte index into that string, and
// returns the byte index of the next UTF-8 sequence. When the end
// of the string is encountered, the function returns NULL
function utf8_next_index(string $str, int $start) : ?int
{
  $ret = null;

  $i = $start;

  if (isset($str[$i]))
  {
    if (ord($str[$i]) < 0xc0)
    {
      $i++;
    }
    else
    {
      $i++;
      while (isset($str[$i]) && ((ord($str[$i]) & 0xc0) == 0x80))
      {
        $i++;
      }
    }
    if (isset($str[$i]) && (ord($str[$i]) != 0))
    {
      $ret = $i;
    }
  }

  return $ret;
}
