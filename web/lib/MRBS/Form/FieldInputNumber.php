<?php

namespace MRBS\Form;


class FieldInputNumber extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputNumber());
  }
  
}