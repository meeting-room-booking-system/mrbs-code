<?php

namespace MRBS\Form;

class ElementInputCheckbox extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'checkbox');
  }
 
}