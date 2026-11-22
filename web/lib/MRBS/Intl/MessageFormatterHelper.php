<?php
declare(strict_types=1);

/*
 * Copyright © 2008 by Yii Software LLC (http://www.yiisoft.com)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *  * Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 *  * Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 *  * Neither the name of Yii Software LLC nor the names of its
 *    contributors may be used to endorse or promote products derived
 *    from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * Originally forked from
 * https://github.com/yiisoft/yii2/blob/2.0.15/framework/i18n/MessageFormatter.php
 */

namespace MRBS\Intl;


/**
 * MessageFormatterHelper provides helper methods for message formatting using ICU.
 *
 * Some of the methods (eg `tokenizePattern()` and `validateTokens()`) have been extracted from the Symfony polyfill
 * MessageFormatter class, so that they can be used to support additional message formatting methods (eg
 * `getPluralStrings()` which are used by MRBS).  The separate class was created, instead of modifying the original
 * MessageFormatter class, to avoid confusion with the standard MessageFormatter class.
 */
class MessageFormatterHelper
{
  /**
   * Convert a simple pattern containing just one plural placeholder to an array of plural strings.  Useful for
   * converting MRBS vocab messages for use by JavaScript's `Intl.PluralRules`.
   *
   * Example 1: "{0, plural, one {# sekunda} few {# sekundy} many {# sekund} other {# sekund}}" will produce
   * `['one' => '# sekunda', 'few' => '# sekundy', 'many' => '# sekund', 'other' => '# sekund']`.
   *
   * Example 2: "He has {0, plural, one {# mouse} other {# mice}}." will produce `['one' => 'He has # mouse.',
   * 'other' => 'He has # mice.']`.
   *
   * @param string $string Either a MessageFormatter pattern, or, for backwards compatibility, a string with conversion
   * specifications intended for use with `sprintf()`.
   * @return array{'zero'|'one'|'two'|'few'|'many'|'other': string}|string If a MessageFormatter pattern was provided
   * an array indexed by category; otherwise a string for use with `sprintf()`.
   */
  public static function getPluralStrings(string $string)
  {
    // If it doesn't look like a MessageFormatter pattern, just return ther string
    if (!preg_match('/{.*}/', $string))
    {
      return $string;
    }

    // Otherwise break the pattern down into categories
    try
    {
      $result = [];
      $prefix = '';
      $suffix = '';
      $tokens = self::tokenizePattern($string);
      self::validateTokens($tokens);

      foreach ($tokens as $token)
      {
        // If it's just a plain string, then add it to the prefix string ot the suffix string, depending on whether
        // we've already had the plural placeholder.  We will need to add these to the final results.
        if (is_string($token) && ($token !== ''))
        {
          if (count($result) === 0)
          {
            $prefix .= $token;
          }
          else
          {
            $suffix .= $token;
          }
          continue;
        }
        // If it's not a placeholder with a 'plural' argument, ignore it.
        if (!is_array($token) || !isset($token[1]) || trim($token[1]) !== 'plural')
        {
          continue;
        }
        if (!isset($token[2]))
        {
          throw new \DomainException("Invalid plural token");
        }
        // We've got a plural placeholder.  Get the strings for the plural categories.
        $plural_tokens = self::tokenizePattern($token[2]);
        self::validateTokens($plural_tokens);

        while (null != ($keyword = array_shift($plural_tokens)))
        {
          $keyword = trim($keyword);
          if (!in_array($keyword, ['zero', 'one', 'two', 'few', 'many', 'other']))
          {
            throw new \DomainException("Invalid keyword '$keyword'");
          }
          if (null === ($string_tokens = array_shift($plural_tokens)))
          {
            throw new \DomainException("No string for keyword '$keyword'");
          }
          $result[$keyword] = $string_tokens[0];
        }
      }
    }
    catch (\DomainException $e)
    {
      trigger_error($e->getMessage(), E_USER_WARNING);
      return [];
    }

    // Finally, add the prefix and suffix to the strings.
    foreach ($result as &$value)
    {
      $value = $prefix . $value . $suffix;
    }

    return $result;
  }


  public static function tokenizePattern($pattern)
  {
    if (false === $start = $pos = strpos($pattern, '{')) {
      return [$pattern];
    }

    $depth = 1;
    $tokens = [substr($pattern, 0, $pos)];

    while (true) {
      $open = strpos($pattern, '{', 1 + $pos);
      $close = strpos($pattern, '}', 1 + $pos);

      if (false === $open) {
        if (false === $close) {
          break;
        }
        $open = \strlen($pattern);
      }

      if ($close > $open) {
        ++$depth;
        $pos = $open;
      } else {
        --$depth;
        $pos = $close;
      }

      if (0 === $depth) {
        $tokens[] = explode(',', substr($pattern, 1 + $start, $pos - $start - 1), 3);
        $start = 1 + $pos;
        $tokens[] = substr($pattern, $start, $open - $start);
        $start = $open;
      }

      if (0 !== $depth && (false === $open || false === $close)) {
        break;
      }
    }

    if ($depth) {
      throw new \DomainException('Message pattern is invalid.');
    }

    return $tokens;
  }


  public static function validateTokens(array $tokens): void
  {
    foreach ($tokens as $token) {
      if (!\is_array($token)) {
        continue;
      }
      $type = isset($token[1]) ? trim($token[1]) : 'none';
      switch ($type) {
        case 'none':
        case 'number':
        case 'date':
        case 'time':
        case 'spellout':
        case 'ordinal':
        case 'duration':
        case 'choice':
          break;
        case 'selectordinal':
        case 'plural':
        case 'select':
          if (!isset($token[2])) {
            throw new \DomainException('Message pattern is invalid.');
          }
          $sub = MessageFormatterHelper::tokenizePattern($token[2]);
          $c = \count($sub);
          if ($c < 2) {
            throw new \DomainException('Message pattern is invalid.');
          }
          $hasOther = false;
          for ($i = 0; 1 + $i < $c; $i += 2) {
            if (\is_array($sub[$i]) || !\is_array($sub[1 + $i])) {
              throw new \DomainException('Message pattern is invalid.');
            }
            $selector = trim($sub[$i]);
            if ('select' !== $type && 0 === $i && 0 === strncmp($selector, 'offset:', 7)) {
              $offsetEnd = strpos(str_replace(["\n", "\r", "\t"], ' ', $selector), ' ', 7);
              $selector = false !== $offsetEnd ? trim(substr($selector, 1 + $offsetEnd)) : '';
            }
            if ('other' === $selector) {
              $hasOther = true;
            }
            self::validateTokens(MessageFormatterHelper::tokenizePattern(implode(',', $sub[1 + $i])));
          }
          if (!$hasOther) {
            throw new \DomainException('Message pattern is invalid.');
          }
          break;
        default:
          throw new \DomainException('Message pattern is invalid.');
      }
    }
  }

}
