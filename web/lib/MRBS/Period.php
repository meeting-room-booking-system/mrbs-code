<?php
declare(strict_types=1);
namespace MRBS;

class Period
{
  public $name;
  public $start;
  public $end;

  public function __construct(string $name, ?string $start=null, ?string $end=null)
  {
    $this->name = $name;
    $this->start = $start;
    $this->end = $end;
  }

}
