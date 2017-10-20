<?php

namespace MRBS\Form;

class ElementLabel extends Element
{

  public function __construct($label, $id=null)
  {
    parent::__construct('label');
    $this->text = $label;
    if (isset($id))
    {
      $this->setAttribute('for', $id);
    }
  }
 
}