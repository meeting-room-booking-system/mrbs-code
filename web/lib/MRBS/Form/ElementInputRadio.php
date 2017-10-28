<?php

namespace MRBS\Form;

class ElementInputRadio extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'radio');
  }
 
}