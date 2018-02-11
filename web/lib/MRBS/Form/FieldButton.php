<?php

namespace MRBS\Form;


class FieldButton extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementButton());
  }
  
}