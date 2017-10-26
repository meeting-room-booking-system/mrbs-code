<?php

namespace MRBS\Form;


class FieldInputPassword extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputPassword());
  }
  
}