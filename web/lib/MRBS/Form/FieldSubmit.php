<?php

namespace MRBS\Form;


class FieldSubmit extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addElement(new ElementSubmit());
  }
  
}