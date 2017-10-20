<?php

namespace MRBS\Form;

abstract class ElementInput extends Element
{
  
  public function __construct($type='text')
  {
    parent::__construct('input');
    $this->setAttribute('type', $type);
  }
 
}