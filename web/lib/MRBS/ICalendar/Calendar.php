<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use MRBS\Exception;
use MRBS\Language;
use MRBS\Utf8\Utf8String;
use function MRBS\get_mrbs_version;

require_once MRBS_ROOT . '/version.inc';

class Calendar
{
  private const NAME = 'VCALENDAR';
  private const CR = "\r";
  private const LF = "\n";
  private const MAX_OCTETS_IN_LINE =75;  // The maximum line length allowed
  private const LINE_FOLD = self::EOL . ' '; // The RFC also allows a horizontal tab instead of a space
  private const LINE_FOLD_OCTETS = 1;

  public const EOL = self::CR . self::LF;

  private $components = [];
  private $properties = [];


  public function __construct(?string $method=null)
  {
    $this->properties[] = new Property('PRODID', '-//MRBS//NONSGML ' . get_mrbs_version() . '//EN');
    $this->properties[] = new Property('VERSION', '2.0');
    $this->properties[] = new Property('CALSCALE', 'GREGORIAN');
    if (isset($method))
    {
      $this->properties[] = new Property('METHOD', $method);
    }
  }


  public function addComponent(Component $component) : self
  {
    $this->components[] = $component;
    return $this;
  }


  public function addComponents(array $components) : self
  {
    foreach ($components as $component)
    {
      $this->addComponent($component);
    }
    return $this;
  }

  public function toString(): string
  {
    $result = 'BEGIN:' . self::NAME . self::EOL;

    foreach ($this->properties as $property)
    {
      $result .= $property->toString();
    }

    foreach ($this->components as $component)
    {
      $result .= $component->toString();
    }

    $result .= 'END:' . self::NAME . self::EOL;
    return self::fold($result);
  }


  /**
   * "Fold" lines longer than 75 octets. Multibyte safe.
   */
  public static function fold(string $str) : string
  {
    // "Lines of text SHOULD NOT be longer than 75 octets, excluding the line
    // break.  Long content lines SHOULD be split into a multiple line
    // representations using a line "folding" technique.  That is, a long
    // line can be split between any two characters by inserting a CRLF
    // immediately followed by a single linear white-space character (i.e.,
    // SPACE or HTAB).  Any sequence of CRLF followed immediately by a
    // single linear white-space character is ignored (i.e., removed) when
    // processing the content type."  (RFC 5545)

    // Deal with the trivial case
    if ($str === '')
    {
      return $str;
    }

    // We assume that we are using UTF-8 and therefore that a space character
    // is one octet long.  If we ever switched for some reason to using, for
    // example, UTF-16, this assumption would be invalid.
    if ((Language::MRBS_CHARSET != 'utf-8') || (Language::MAIL_CHARSET != 'utf-8'))
    {
      throw new Exception("MRBS: internal error - using unsupported character set");
    }

    $utf8_string = new Utf8String($str);

    // Simple case: no folding necessary
    if ($utf8_string->byteCount() <= self::MAX_OCTETS_IN_LINE)
    {
      return $str;
    }

    // Iterate through the characters working out when to insert a fold
    $result = '';
    $n_chars = count($utf8_string->toArray());
    $octets = 0;
    $previous = [];

    foreach ($utf8_string as $i => $char)
    {
      // Store the character
      $previous[] = $char;

      // If it's a CR and there's at least one more character to come, then get that one.
      if (($char == self::CR) && ($i < $n_chars - 1))
      {
        continue;
      }

      // If it's a LF and the previous character was a CR, then we've reached the end of a line
      if (($char == self::LF) && (count($previous) == 2) && ($previous[0] == self::CR))
      {
        // Output the EOL, clear the previous characters and reset the octet count
        $result .= self::EOL;
        $previous = [];
        $octets = 0;
        continue;
      }

      // Otherwise output the previous characters, inserting a fold if necessary
      while (null !== ($previous_char = array_shift($previous)))
      {
        $previous_char_octets = (new Utf8String($previous_char))->byteCount();
        // If this character would take us over the line length limit, then output a line fold
        if ($octets + $previous_char_octets > self::MAX_OCTETS_IN_LINE)
        {
          $result .= self::LINE_FOLD;
          // Reset the octet count to account for the whitespace introduced during folding.
          // [Note:  It's not entirely clear from the RFC whether the octet that is introduced
          // when folding counts towards the 75 octets.   Some implementations (eg Google
          // Calendar as of Jan 2011) do not count it.  However, it can do no harm to err on
          // the safe side and include the initial whitespace in the count.]
          $octets = self::LINE_FOLD_OCTETS;
        }
        // Now output the character and add on the octets just output.
        $result .= $previous_char;
        $octets += $previous_char_octets;
      }
    }

    return $result;
  }

}
