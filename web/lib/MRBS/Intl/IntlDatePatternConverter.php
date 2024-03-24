<?php
declare(strict_types=1);
namespace MRBS\Intl;

class IntlDatePatternConverter
{
  private const QUOTE_CHAR = "'";

  private $formatter;

  public function __construct(Formatter $formatter)
  {
    $this->formatter = $formatter;
  }


  public function convert(string $pattern) : string
  {
    // Parse the pattern
    // See https://unicode-org.github.io/icu/userguide/format_parse/datetime/
    // "Note: Any characters in the pattern that are not in the ranges of [‘a’..’z’] and
    // [‘A’..’Z’] will be treated as quoted text. For instance, characters like ':', '.',
    // ' ', '#' and '@' will appear in the resulting time text even they are not enclosed
    // within single quotes. The single quote is used to ‘escape’ letters. Two single
    // quotes in a row, whether inside or outside a quoted sequence, represent a ‘real’
    // single quote."
    $format = '';
    $token = '';
    $token_char = null;
    $in_quotes = false;
    // Split the string into an array of multibyte characters
    $chars = preg_split("//u", $pattern, 0, PREG_SPLIT_NO_EMPTY);

    while (null !== ($char = array_shift($chars)))
    {
      $is_token_char = !$in_quotes && preg_match("/^[a-z]$/i", $char);
      if ($is_token_char)
      {
        // The start of a token
        if (!isset($token_char))
        {
          $token_char = $char;
          $token = $char;
        }
        // The continuation of a token
        elseif ($char === $token_char)
        {
          $token .= $char;
        }
        // The end of a token and the beginning of a new one
        else
        {
          $format .= $this->formatter->convert($token);
          $token_char = $char;
          $token = $char;
        }
      }
      // Check to see if a token has just ended, ie we've either got
      // a non-token character or there are no more characters left.
      if (($token !== '') && (!$is_token_char || empty($chars)))
      {
        $format .= $this->formatter->convert($token);
        $token = '';
        $token_char = null;
      }

      // Quoted text
      if (!$is_token_char)
      {
        // If it's not a quote just add the character to the format
        if ($char !== self::QUOTE_CHAR)
        {
          $format .= $this->formatter->escape($char);
        }
        // Otherwise we have to work out whether the quote is the start or end of a
        // quoted sequence, or part of an escaped quote
        else
        {
          // Get the next character
          $char = array_shift($chars);
          if (isset($char))
          {
            // If it is a quote then it's an escaped quote and add it to the format
            if ($char === self::QUOTE_CHAR)
            {
              $format .= $this->formatter->escape($char);
            }
            // Otherwise it's either the start or end of a quoted section.
            // Toggle $in_quotes and add the character to the format if we're in quotes,
            // or else replace it so that it gets handled properly next time round.
            else
            {
              $in_quotes = !$in_quotes;
              if ($in_quotes)
              {
                $format .= $this->formatter->escape($char);
              }
              else
              {
                array_unshift($chars, $char);
              }
            }
          }
        }
      }
    }

    return $format;
  }

}
