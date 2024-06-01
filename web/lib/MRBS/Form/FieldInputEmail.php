<?php
declare(strict_types=1);
namespace MRBS\Form;


class FieldInputEmail extends Field
{

  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputEmail());
  }

}
