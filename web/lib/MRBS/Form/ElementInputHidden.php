<?php

namespace MRBS\Form;

class ElementInputHidden extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'hidden');
  }
 
}