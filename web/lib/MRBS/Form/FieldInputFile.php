<?php

namespace MRBS\Form;


class FieldInputFile extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputFile());
  }
  
}