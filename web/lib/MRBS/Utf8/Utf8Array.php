<?php
declare(strict_types=1);
namespace MRBS\Utf8;

use ArrayIterator;

class Utf8Array extends ArrayIterator
{
  private $data;

  // A class that splits a UTF-8 string into an array of UTF-8 characters
  // so that array methods such as count() can be used.
  public function __construct(string $string)
  {
    $this->data = (strlen($string) > 1000) ? self::explodeMethodA($string) : self::explodeMethodA($string);
    parent::__construct($this->data);
  }


  // UTF-8 compatible substr function obtained from a contribution by
  // "frank at jkelloggs dot dk" in the PHP online manual for substr()
  private static function explodeMethodA(string $string) : array
  {
    if (false === preg_match_all("/./su", $string, $matches))
    {
      throw new Exception("preg_match_all() failed");
    }
    return $matches[0];
  }
}
