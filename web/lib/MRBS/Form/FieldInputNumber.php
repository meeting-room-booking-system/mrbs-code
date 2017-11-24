<?php

namespace MRBS\Form;

// Defaults to step="1"
class FieldInputNumber extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputNumber());
  }
  
}