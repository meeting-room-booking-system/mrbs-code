<?php

namespace MRBS\Form;


class FieldSelect extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addElement(new ElementSelect());
  }
  
}