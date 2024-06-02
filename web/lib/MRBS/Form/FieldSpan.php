<?php
declare(strict_types=1);
namespace MRBS\Form;


class FieldSpan extends Field
{

  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementSpan());
  }

}
