<?php

namespace MRBS\Form;

class ElementInputUrl extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'url');
  }
 
}