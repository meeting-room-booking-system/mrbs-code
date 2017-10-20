<?php

namespace MRBS\Form;

abstract class ElementInput extends Element
{
  
  public function __construct($type='text', $self_closing=true)
  {
    parent::__construct('input');
    $this->self_closing = $self_closing;
    $this->setAttribute('type', $type);
  }
 
}