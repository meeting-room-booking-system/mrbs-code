<?php

namespace MRBS\Form;


class FieldInputSubmit extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputSubmit());
  }
  
}