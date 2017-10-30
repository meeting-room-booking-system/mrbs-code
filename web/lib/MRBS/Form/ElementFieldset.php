<?php

namespace MRBS\Form;


class ElementFieldset extends Element
{
  
  public function __construct()
  {
    parent::__construct('fieldset');
  }
  
  
  // $legend can either be a string or an element
  public function addLegend($legend)
  {
    $element = new ElementLegend();
    
    if (is_string($legend))
    {
      $element->setText($legend);
    }
    else
    {
      // Assumed to be an object of class 'Element' if it is not a string
      $element->addElement($legend);
    }
    
    $this->addElement($element);
    return $this;
  }
  
}