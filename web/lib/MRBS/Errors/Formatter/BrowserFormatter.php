<?php
declare(strict_types=1);
namespace MRBS\Errors\Formatter;


class BrowserFormatter extends GeneralFormatter
{
  public function __construct()
  {
    parent::__construct(null, true);
  }

}
