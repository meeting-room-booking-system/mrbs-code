<?php

namespace MRBS\Form;

abstract class ElementInput extends Element
{
  
  public function __construct()
  {
    parent::__construct('input', $self_closing=true);
    $this->setAttribute('type', 'text');
  }
 
}