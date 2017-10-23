<?php

namespace MRBS\Form;


class FieldText extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addElement(new ElementText());
  }
  
}