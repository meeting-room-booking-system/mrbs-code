<?php

namespace MRBS\Form;

class ElementInputFile extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'file');
  }
 
}