<?php
declare(strict_types=1);
namespace MRBS\Intl;

interface Formatter
{
  public function convert(string $token) : string;

  public function escape(string $char) : string;
}
