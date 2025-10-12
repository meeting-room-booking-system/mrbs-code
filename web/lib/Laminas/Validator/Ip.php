<?php
declare(strict_types=1);
namespace Laminas\Validator;

// A very basic emulator of the Laminas IP validator.  We don't use the
// original because (a) it requires PHP 8.0 and (b) it has too many other
// dependencies.
class Ip
{
  public function isValid(string $value): bool
  {
    return (false !== inet_pton($value));
  }

}
