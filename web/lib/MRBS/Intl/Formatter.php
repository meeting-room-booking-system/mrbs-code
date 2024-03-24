<?php
declare(strict_types=1);
namespace MRBS\Intl;

// An interface for converting ICU datetime patterns to another format, eg strftime
interface Formatter
{
  // Convert an ICU pattern token into the nearest equivalent token.
  // Throws an exception if the token can't be converted.
  public function convert(string $token) : string;

  public function escape(string $char) : string;
}
