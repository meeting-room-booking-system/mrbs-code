<?php

namespace MRBS\Form;

class ElementText extends ElementInput
{

  public function __construct($name)
  {
    parent::__construct('text');
    $this->setAttribute('name', $name);
  }
 
}