<?php
declare(strict_types=1);
namespace MRBS\Utf8;

use MRBS\Exception;
use MRBS\System;

class Utf8Char
{
  private $char;

  public function __construct(string $char)
  {
    $this->char = $char;
  }

  public function toUtf16(int $endianness) : string
  {
    switch ($endianness)
    {
      case System::BIG_ENDIAN:
        $format = 'n';
        break;
      case System::LITTLE_ENDIAN:
        $format = 'v';
        break;
      default:
        throw new \InvalidArgumentException("Unknown endianness '$endianness'");
        break;
    }

    $ucs_string = pack($format, self::convertCharToUtf16Int($this->char));
    //error_log(sprintf("UCS %04x -> %02x,%02x",$char,ord($ucs_string[0]),ord($ucs_string[1])));
    return $ucs_string;
  }


  private static function convertCharToUtf16Int(string $char) : int
  {
    $c0 = ord($char[0]);

    // Easy case, code is 0xxxxxxx - just use it as is
    if ($c0 < 0x80)
    {
      return $c0;
    }

    $cn = ord($char[1]) ^ 0x80;
    $ucs = ($c0 << 6) | $cn;

    // Two byte codes: 110xxxxx 10xxxxxx
    if ($c0 < 0xE0)
    {
      $ucs &= ~0x3000;
      return $ucs;
    }

    $cn = ord($char[2]) ^ 0x80;
    $ucs = ($ucs << 6) | $cn;

    // Three byte codes: 1110xxxx 10xxxxxx 10xxxxxx
    if ($c0 < 0xF0)
    {
      $ucs &= ~0xE0000;
      return $ucs;
    }

    $cn = ord($char[3]) ^ 0x80;
    $ucs = ($ucs << 6) | $cn;

    // Four byte codes: 11110xxx 10xxxxxxx 10xxxxxx 10xxxxxx
    if ($c0 < 0xF8)
    {
      $ucs &= ~0x3C00000;
      return $ucs;
    }

    throw new Exception("Shouldn't get here.");
  }

}
