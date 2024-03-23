<?php
declare(strict_types=1);
namespace MRBS\Intl;

interface Formatter
{
  // Convert an ICU pattern token into the nearest equivalent token.
  // Returns FALSE if there is no equivalent.
  public function convert(string $token);

  public function escape(string $char) : string;
}
