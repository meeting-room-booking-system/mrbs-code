<?php

namespace MRBS\Form;


class FieldInputDatalist extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputDatalist());
    var_dump($this);
  }
  
}