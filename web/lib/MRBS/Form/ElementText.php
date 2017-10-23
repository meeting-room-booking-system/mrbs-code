<?php

namespace MRBS\Form;

class ElementText extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'text');
  }
 
}