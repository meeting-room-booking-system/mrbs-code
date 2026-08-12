<?php
declare(strict_types=1);
namespace MRBS;


use Psr\Log\LoggerInterface;

/**
 * A wrapper class for https://github.com/mmucklo/email-parse
 *
 * The main branch only works on PHP >= 8.1. There is a branch (2.2) which works on PHP >= 7.1, but
 * is (a) not as thorough at parsing (though probably good enough for most purposes) and (b) generates
 * deprecation errors on PHP 8.6 and above.  So we use the main branch if possible, and fall back to
 * the 2.2 branch if necessary.
 */
class Parse
{
  private $instance;


  public function __construct(?LoggerInterface $logger = null)
  {
    assert(version_compare(MRBS_MIN_PHP_VERSION, '8.1', '<'), "This class is redundant");
    if (version_compare(PHP_VERSION, '8.1', '>='))
    {
      $this->instance = new \Email\Parse($logger);
    }
    else
    {
      $this->instance = new \Email\Branch2dot2\Parse($logger);
    }
  }


  /**
   * @see \Email\Branch2dot2\Parse::parse()
   */
  public function parse(string $emails, bool $multiple = true): array
  {
    return $this->instance->parse($emails, $multiple);
  }

}
