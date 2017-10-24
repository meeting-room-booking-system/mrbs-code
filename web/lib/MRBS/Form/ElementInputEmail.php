<?php

namespace MRBS\Form;

class ElementInputEmail extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'email');
  }
 
}