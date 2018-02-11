<?php

namespace MRBS\Form;


class FieldInputDate extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputDate());
  }
  
}