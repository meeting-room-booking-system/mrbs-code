<?php

namespace MRBS\Form;

class ElementHidden extends ElementInput
{

  public function __construct($name, $value)
  {
    parent::__construct('hidden');
    $this->setAttributes(array('name'  => $name,
                               'value' => $value));
  }
 
}