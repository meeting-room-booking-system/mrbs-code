<?php

namespace MRBS\Form;

class ElementInputText extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'text');
  }
 
}