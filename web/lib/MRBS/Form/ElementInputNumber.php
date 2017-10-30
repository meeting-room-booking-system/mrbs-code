<?php

namespace MRBS\Form;

class ElementInputNumber extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'number');
  }
 
}