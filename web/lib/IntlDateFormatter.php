<?php

// A basic emulation of PHP's IntlDateFormatter class.  This will only be loaded if the
// standard PHP class does not exist.

class IntlDateFormatter
{
  const FULL    = 0;
  const LONG    = 1;
  const MEDIUM  = 2;
  const SHORT   = 3;
  const NONE    = -1;
  const RELATIVE_FULL   = 128; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_LONG   = 129; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_MEDIUM = 130; // Available as of PHP 8.0.0, for dateType only
  const RELATIVE_SHORT  = 131; // Available as of PHP 8.0.0, for dateType only
  const GREGORIAN   = 1;
  const TRADITIONAL = 0;

  public function __construct(
    ?string $locale,
    int $dateType = self::FULL,
    int $timeType = self::FULL,
    $timezone = null,
    $calendar = null,
    ?string $pattern = null)
  {

  }


}
