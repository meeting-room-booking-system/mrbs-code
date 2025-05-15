<?php
declare(strict_types=1);
namespace MRBS\Utf8;

use Iterator;
use MRBS\Exception;
use MRBS\System;
use function MRBS\utf8_strpos;

// A class that allows iteration over the characters in a UTF-8 string.
// It also has the methods:
//   toUtf16()  converts the string to UTF-16
//   toArray()  converts the string into an array of UTF-8 characters
class Utf8String implements Iterator
{
  private $byte_index;
  private $char_index;
  private $next_char_length;
  private $have_all_chars = false;
  private $data = [];
  private $string;


  public function __construct(string $string)
  {
    $this->string = $string;
    $this->rewind();
  }


  // Return the current element
  public function current() : ?string
  {
    return $this->data[$this->char_index] ?? null;
  }


  // Move forward to next element
  public function next() : void
  {
    $this->byte_index += $this->next_char_length;
    $this->char_index++;
    $this->next_char_length = $this->nextCharLength();
    $this->setNextChar();
  }


  // Return the key of the current element
  public function key(): int
  {
    return $this->char_index;
  }


  // Checks if current position is valid
  public function valid() : bool
  {
    return isset($this->data[$this->char_index]);
  }


  // Rewind the Iterator to the first element
  public function rewind() : void
  {
    $this->byte_index = 0;
    $this->char_index = 0;
    $this->next_char_length = $this->nextCharLength();
    $this->setNextChar();
  }


  public function toArray() : array
  {
    if (strlen($this->string) > 1000)
    {
      if (false === preg_match_all("/./su", $string, $matches))
      {
        throw new Exception("preg_match_all() failed");
      }
      $this->data = $matches[0];
    }
    else
    {
      // Faster method of splitting the string for small strings?
      // TODO: this needs to be verified
      $this->getRemainingData();
    }
    return $this->data;
  }


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


  // Strip off anything that looks like a BOM
  private static function stripBom(string $string) : string
  {
    $result = $string;

    $boms = array("\xFE\xFF", "\xFF\xFE");
    foreach ($boms as $bom)
    {
      if (utf8_strpos($result, $bom) === 0)
      {
        $result = substr($result, strlen($bom));
      }
    }

    return $result;
  }

  // Converts to UTF-16 using iconv()
  private function toUtf16Iconv(int $endianness, bool $strip_bom=false) : string
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
        throw new \InvalidArgumentException("Unknown endianness '$endianness'");
        break;
    }

    $result = iconv($in_charset, $out_charset, $this->string);

    if ($result === false)
    {
      throw new Exception("iconv() failed converting from '$in_charset' to '$out_charset'");
    }

    return $result;
  }


  // Converts to UTF-16 without using iconv()
  private function toUtf16NoIconv(int $endianness) : string
  {
    $result = '';
    $this->getRemainingData();

    foreach ($this->data as $char)
    {
      $result .= (new Utf8Char($char))->toUtf16($endianness);
    }

    return $result;
  }


  // Get the rest of the UTF-8 characters, preserving the current key
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


  // Gets the length in bytes of the next UTF-8 char.  Returns
  // zero if there isn't one.
  private function nextCharLength() : ?int
  {
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
