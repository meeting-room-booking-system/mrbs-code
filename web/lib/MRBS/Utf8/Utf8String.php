<?php
declare(strict_types=1);
namespace MRBS\Utf8;

use InvalidArgumentException;
use Iterator;
use MRBS\System;
use RuntimeException;

/**
 * A class that allows iteration over the characters in a UTF-8 string.
 * It also has the methods:
 * * toArray()  converts the string into an array of UTF-8 characters
 * * toUtf16()  converts the string to UTF-16
 */

class Utf8String implements Iterator
{
  private $byte_index;
  private $char_index;
  private $next_char_length;
  private $have_all_chars = false;
  private $data = [];
  private $string;

  // Note that we cannot use the PHP string functions, eg strlen() and strpos(),
  // or their mb_ equivalents, in this class, because it is used by MRBS's
  // emulations of the mb_ string functions.

  public function __construct(string $string)
  {
    $this->string = $string;
    $this->rewind();
  }


  /**
   * Return the current element
   */
  public function current() : ?string
  {
    return $this->data[$this->char_index] ?? null;
  }


  /**
   * Move forward to next element
   */
  public function next() : void
  {
    $this->byte_index += $this->next_char_length;
    $this->char_index++;
    $this->next_char_length = $this->nextCharLength();
    $this->setNextChar();
  }


  /**
   * Return the key of the current element
   */
  public function key(): int
  {
    return $this->char_index;
  }


  /**
   * Checks if current position is valid
   */
  public function valid() : bool
  {
    return isset($this->data[$this->char_index]);
  }


  /**
   * Rewind the Iterator to the first element
   */
  public function rewind() : void
  {
    $this->byte_index = 0;
    $this->char_index = 0;
    $this->next_char_length = $this->nextCharLength();
    $this->setNextChar();
  }


  /**
   * Returns the string's length in bytes
   */
  public function byteCount() : int
  {
    // Trivial case
    if (!isset($this->string[0]))
    {
      return 0;
    }

    // Use a binary chop
    $lower = 0;
    $upper = 2;

    while (($upper - $lower) > 1)
    {
      if (isset($this->string[$upper]))
      {
        $lower = $upper;
        $upper = $upper * 2;
      }
      else
      {
        $upper = $lower + intval(($upper - $lower)/2);
      }
    }

    $index = (isset($this->string[$upper])) ? $upper : $lower;

    return $index + 1;
  }


  /**
   * Converts the string to an array
   *
   * @param int $break_point provided for testing purposes only
   */
  public function toArray(int $break_point=0) : array
  {
    // The $break_point parameter is there for testing purposes only.  Historically
    // MRBS provided utf8_substr() and utf8_substr_old(), using the preg_match_all()
    // approach for strings longer than 1000 bytes and doing it manually otherwise,
    // using an algorithm obtained from a contribution by "frank at jkelloggs dot dk"
    // in the PHP online manual for substr() which testing had shown was faster.
    //
    // However, testing of the method below shows that the preg_match_all() approach
    // is faster for all string lengths (maybe because the performance of preg_match_all()
    // has been improved?).  The code for the manual method is left in place just in case
    // it is needed in the future.
    if (!$this->have_all_chars)
    {
      if (strlen($this->string) > $break_point)
      {
        if (false === preg_match_all("/./su", $this->string, $matches))
        {
          throw new RuntimeException("preg_match_all() failed");
        }
        $this->data = $matches[0];
      }
      else
      {
        // Alternative method of splitting the string for small strings
        $this->getRemainingData();
      }
      $this->have_all_chars = true;
    }

    return $this->data;
  }


  /**
   * Convert to UTF-16
   */
  public function toUtf16(?int $endianness=null, bool $strip_bom=false) : string
  {
    // If the endian-ness hasn't been specified, then state it explicitly, because
    // Windows and Unix will use different defaults on the same architecture.
    if (!isset($endianness))
    {
      $endianness = System::getEndianness();
    }

    if (function_exists('iconv'))
    {
      $result = $this->toUtf16Iconv($endianness);
    }
    else
    {
      $result = $this->toUtf16NoIconv($endianness);
    }

    if ($strip_bom)
    {
      $result = self::stripBom($result);
    }

    return $result;
  }


  /**
   * Strip off the BOM if there is one
   */
  private static function stripBom(string $string) : string
  {
    $bom = pack('H*','FEFF');
    return preg_replace("/^$bom/", '', $string);
  }


  /**
   * Converts to UTF-16 using iconv()
   */
  private function toUtf16Iconv(int $endianness) : string
  {
    $in_charset = 'UTF-8';
    $out_charset = 'UTF-16';

    switch ($endianness)
    {
      case System::BIG_ENDIAN:
        $out_charset .= 'BE';
        break;
      case System::LITTLE_ENDIAN:
        $out_charset .= 'LE';
        break;
      default:
        throw new InvalidArgumentException("Unknown endianness '$endianness'");
        break;
    }

    $result = iconv($in_charset, $out_charset, $this->string);

    if ($result === false)
    {
      throw new RuntimeException("iconv() failed converting from '$in_charset' to '$out_charset'");
    }

    return $result;
  }


  /**
   * Converts to UTF-16 without using iconv()
   */
  private function toUtf16NoIconv(int $endianness) : string
  {
    $result = '';
    $chars = $this->toArray();

    foreach ($chars as $char)
    {
      $result .= (new Utf8Char($char))->toUtf16($endianness);
    }

    return $result;
  }


  /**
   * Get the rest of the UTF-8 characters, preserving the current key
   */
  private function getRemainingData() : void
  {
    // Get the rest of the characters if we haven't already got them
    if (!$this->have_all_chars)
    {
      // Preserve the variables
      $vars = ['byte_index', 'char_index', 'next_char_length'];
      $old = [];
      foreach ($vars as $var)
      {
        $old[$var] = $this->$var;
      }

      while ($this->valid())
      {
        $this->next();
      }

      // Restore the indices
      foreach ($vars as $var)
      {
        $this->$var = $old[$var];
      }
    }
  }


  /**
   * Gets the length in bytes of the next UTF-8 char.
   *
   * @return int|null returns zero if there isn't a next char
   */
  private function nextCharLength() : ?int
  {
    // TODO: can this ever return null, or does it always return an int?
    $i = $this->byte_index;

    if (isset($this->string[$i]))
    {
      if (ord($this->string[$i]) < 0xc0)
      {
        $i++;
      }
      else
      {
        $i++;
        while (isset($this->string[$i]) && ((ord($this->string[$i]) & 0xc0) == 0x80))
        {
          $i++;
        }
      }
    }

    return $i - $this->byte_index;
  }


  private function nextChar() : string
  {
    $result = '';

    for ($i=0, $byte_index = $this->byte_index; $i<$this->next_char_length; $i++, $byte_index++)
    {
      $result[$i] = $this->string[$byte_index];
    }

    return $result;
  }


  private function setNextChar() : void
  {
    if (!$this->next_char_length)
    {
      $this->have_all_chars = true;
    }
    elseif (!isset($this->data[$this->char_index]))
    {
      $this->data[$this->char_index] = $this->nextChar();
    }
  }
}
