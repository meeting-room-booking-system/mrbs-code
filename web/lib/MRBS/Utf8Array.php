<?php
declare(strict_types=1);
namespace MRBS;

use ArrayIterator;

class Utf8Array extends ArrayIterator
{
  private $data;

  // A class that splits a UTF-8 string into an array of UTF-8 characters
  // so that array methods such as count() can be used.
  public function __construct(string $string)
  {
    if (false === preg_match_all("/./su", $string, $matches))
    {
      throw new Exception("preg_match_all() failed");
    }
    $this->data = $matches[0];
    parent::__construct($this->data);
  }

}
