<?php

namespace MRBS\Form;


class FieldTextarea extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementTextarea());
  }
  
}