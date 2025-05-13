<?php
declare(strict_types=1);
namespace MRBS\Utf8;

class Utf8String implements \Iterator
{
  private $byte_index = 0;
  private $next_byte_index;
  private $char_index = 0;
  private $data = [];
  private $string;


  public function __construct(string $string)
  {
    $this->string = $string;
    $this->next_byte_index = $this->nextByteIndex();
    if (isset($this->next_byte_index))
    {
      $this->data[$this->char_index] = $this->nextChar();
    }
  }

  public function current() : ?string
  {
    return $this->data[$this->char_index] ?? null;
  }

  public function next() : void
  {
    $this->byte_index = $this->next_byte_index;
    $this->next_byte_index = $this->nextByteIndex();
    $this->char_index++;
    if (isset($this->next_byte_index))
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
    $this->next_byte_index = $this->nextByteIndex();
  }


  // Takes a UTF-8 string and a byte index into that string, and
  // returns the byte index of the next UTF-8 sequence. When the end
  // of the string is encountered, the function returns NULL
  private function nextByteIndex() : ?int
  {
    $result = null;

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
      if (isset($this->string[$i]) && (ord($this->string[$i]) != 0))
      {
        $result = $i;
      }
    }

    return $result;
  }


  private function nextChar() : string
  {
    $result = '';

    for ($i=0, $byte_index = $this->byte_index; $byte_index < $this->next_byte_index; $i++, $byte_index++)
    {
      $result[$i] = $this->string[$byte_index];
    }

    return $result;
  }
}
