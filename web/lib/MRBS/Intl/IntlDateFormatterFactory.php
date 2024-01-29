<?php
// Note that we cannot use strict types here as some early versions of PHP (eg 7.2.34) throw
// a TypeError if null is passed as the sixth parameter to \IntlDateFormatter::_construct(),
// despite the signature on the manual page.
// declare(strict_types=1);
namespace MRBS\Intl;

// A factory class for creating either an instance of \IntlDateFormatter or \MRBS\Intl\IntlDateFormatter,
// depending on the setting of the $force_strftime config variable.
class IntlDateFormatterFactory
{
  public static function create(
    ?string $locale,
    int     $dateType = \IntlDateFormatter::FULL,
    int     $timeType = \IntlDateFormatter::FULL,
            $timezone = null,
            $calendar = null,
    ?string $pattern = null)
  {
    global $force_strftime;

    if ($force_strftime)
    {
      // This will return an instance of the emulation
      return new IntlDateFormatter($locale, $dateType, $timeType, $timezone, $calendar, $pattern);
    }
    else
    {
      // This will return an instance of the standard PHP class if the 'intl' extension is loaded,
      // otherwise an instance of the emulation
      return new \IntlDateFormatter($locale, $dateType, $timeType, $timezone, $calendar, $pattern);
    }
  }

}
