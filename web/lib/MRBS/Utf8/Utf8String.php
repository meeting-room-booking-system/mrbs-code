<?php
declare(strict_types=1);
namespace MRBS\Utf8;

use Iterator;

// A class that allows iteration over the characters in a UTF-8 string.
// It also has the methods:
//   convertToUtf16() converts the string to UTF-16
//   explode()        explodes the string into an array of UTF-8 characters
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


  // Converts to UTF-16 without using iconv()
  public function toUtf16() : string
  {
    $result = '';
    $this->explode();

    foreach ($this->data as $char)
    {
      $result .= (new Utf8Char($char))->toUtf16();
    }

    return $result;
  }


  // Explodes the string into an array of UTF-8 characters
  public function explode() : array
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

    return $this->data;
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
