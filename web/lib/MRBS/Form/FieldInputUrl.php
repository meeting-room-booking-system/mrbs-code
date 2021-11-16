<?php

namespace MRBS\Form;


class FieldInputUrl extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputUrl());
  }
  
}