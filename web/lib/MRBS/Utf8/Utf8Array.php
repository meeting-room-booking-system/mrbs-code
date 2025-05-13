<?php
declare(strict_types=1);
namespace MRBS\Utf8;

use ArrayIterator;
use MRBS\Exception;

class Utf8Array extends ArrayIterator
{
  private $data;

  // A class that splits a UTF-8 string into an array of UTF-8 characters
  // so that array methods such as count() can be used.
  public function __construct(string $string)
  {
    if (strlen($string) > 1000)
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
      $this->data = (new Utf8String($string))->explode();
    }
    parent::__construct($this->data);
  }
}
