<?php
declare(strict_types=1);
namespace MRBS;


// A class for expressing two years as a range, eg "2025/26"
// TODO: Is there a standard class that will do this?  Couldn't find one.
// TODO: Handle non-consecutive years, eg "2025-27".
class YearRanger
{
  private $locale;
  private $separator = '/';

  public function __construct(string $locale)
  {
    $this->locale = $locale;
  }


  public function format(\DateTime $start, \DateTime $end) : string
  {
    $formatter = new \IntlDateFormatter($this->locale);
    $formatter->setPattern('y');
    $start_string = $formatter->format($start);
    $end_string = $formatter->format($end);

    // There's no range
    if ($start_string == $end_string)
    {
      return $start_string;
    }

    // Get the range
    // If we're dealing with 4-digit Arabic numerals, and the centuries are the
    // same, then shorten the end year.
    $pattern = '/^\d{4}$/';  // exactly four digits, eg '2025'
    if (preg_match($pattern, $start_string) && preg_match($pattern, $end_string))
    {
      $start_century = mb_substr($start_string, 0, 2);
      $end_century = mb_substr($end_string, 0, 2);
      if ($start_century == $end_century)
      {
        $end_string = mb_substr($end_string, 2);
      }
    }
    return $start_string . $this->separator . $end_string;
  }


  public function setSeparator(string $separator) : void
  {
    $this->separator = $separator;
  }
}
