<?php

namespace MRBS\Form;


class FieldDiv extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementDiv());
  }
  
}