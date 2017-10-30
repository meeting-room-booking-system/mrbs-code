<?php

namespace MRBS\Form;


class FieldInputTime extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputTime());
  }
  
}