<?php
declare(strict_types=1);
namespace MRBS\Utf8;

class Utf8String implements \Iterator
{
  private $byte_index;
  private $char_index;
  private $next_char_length;
  private $data = [];
  private $string;


  public function __construct(string $string)
  {
    $this->string = $string;
    $this->rewind();
  }

  public function current() : ?string
  {
    return $this->data[$this->char_index] ?? null;
  }

  public function next() : void
  {
    $this->byte_index += $this->next_char_length;
    $this->char_index++;
    if ($this->next_char_length = $this->nextCharLength())
    {
      $this->data[$this->char_index] = $this->nextChar();
    }
  }

  public function key(): int
  {
    return $this->char_index;
  }

  public function valid() : bool
  {
    return isset($this->data[$this->char_index]);
  }

  public function rewind() : void
  {
    $this->byte_index = 0;
    $this->char_index = 0;
    $this->next_char_length = $this->nextCharLength();
    if ($this->next_char_length && !isset($this->data[$this->char_index]))
    {
      $this->data[$this->char_index] = $this->nextChar();
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
}
