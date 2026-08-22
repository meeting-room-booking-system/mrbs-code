<?php
declare(strict_types=1);
namespace MRBS;


use Psr\Log\LoggerInterface;

/**
 * A wrapper class for https://github.com/mmucklo/email-parse
 *
 * The main version only works on PHP >= 8.1. There is a version which works on PHP >= 7.1 and PHP < 8.1.
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
      $this->instance = new \Email\PHP71\Parse($logger);
    }
  }


  /**
   * @see \Email\PHP71\Parse::parse()
   */
  public function parse(string $emails, bool $multiple = true): array
  {
    return $this->instance->parse($emails, $multiple);
  }

}
