<?php
namespace MRBS;

class DateTime extends \DateTime
{
  // Workaround for a bug that was fixed in PHP 5.3.6 [modify('12:00') doesn't do anything]
  // Only supports a limited range of $modify strings for PHP < 5.3.6.   Throws
  // an exception if passed a string that it can't handle (maybe it should just
  // generate a warning? - that's what the global DateTime does)
  public function modify($modify)
  {
    if (version_compare(PHP_VERSION, '5.3.6') >= 0)
    {
      return parent::modify($modify);
    }

    $date = getdate($this->getTimestamp());
    $modification = self::parse($modify);

    foreach ($modification as $unit => $amount)
    {
      switch($amount['mode'])
      {
        case 'absolute':
          $date[$unit] = $amount['quantity'];
          break;
        case 'relative':
          $date[$unit] = $date[$unit] + $amount['quantity'];
          break;
        default:
          throw new Exception ("Unknown mode '" . $amount['mode'] . "'");
          break;
      }
    }

    $modified_timestamp = mktime($date['hours'], $date['minutes'], $date['seconds'],
                                 $date['mon'], $date['mday'], $date['year']);

    return $this->setTimestamp($modified_timestamp);
  }


  public function getDay()
  {
    return intval($this->format('j'));
  }


  public function getMonth()
  {
    return intval($this->format('n'));
  }


  public function getYear()
  {
    return intval($this->format('Y'));
  }


  // Parse the $modify string and return an array of any modifications that are necessary.
  // The array is indexed at the top level by 'hours', 'minutes', 'seconds', 'mon', 'mday' and
  // 'year' - ie the same keys that the output of getdate() uses.   Each value is itself an array,
  // indexed by 'mode' (can be 'relative' or 'absolute') and then 'quantity'.   If the mode is
  // relative then the quantity is added to the original, if absolute then it replaces the original.
  private static function parse($modify)
  {
   $modify = self::map($modify);

   // Test for a simple hh:mm pattern (or hhmm or hh.mm)
   $pattern = '/([01][0-9]|[2][0-3])[.:]?([0-5][0-9])/';
   if (preg_match($pattern, $modify, $matches))
   {
       // The seconds are assumed to be 0 in an hh:mm pattern
       return array('hours'   => array('mode'     => 'absolute',
                                       'quantity' => $matches[1]),
                    'minutes' => array('mode'     => 'absolute',
                                       'quantity' => $matches[2]),
                    'seconds' => array('mode'     => 'absolute',
                                       'quantity' => 0));
   }

   // Could add more tests later if need be.
   throw new Exception("Modify string '$modify' not supported by MRBS");
  }


  // Replace some simple modify strings with their numeric alternatives.
  private static function map($modify)
  {
   $mappings = array('midnight' => '00:00',
                     'noon'     => '12:00');

   return (isset($mappings[$modify])) ? $mappings[$modify] : $modify;
  }
}
