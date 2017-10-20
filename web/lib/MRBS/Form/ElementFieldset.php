<?php

namespace MRBS\Form;


class ElementFieldset extends Element
{
  
  public function __construct($legend=null)
  {
    parent::__construct('fieldset');
    if (isset($legend))
    {
      $this->addElement(new ElementLegend($legend));
    }
  }
  
}