<?php

namespace MRBS\Form;


class FieldInputSearch extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputSearch());
  }
  
}