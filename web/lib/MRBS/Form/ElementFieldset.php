<?php

namespace MRBS\Form;


class ElementFieldset extends Element
{
  
  public function __construct()
  {
    parent::__construct('fieldset');
  }
  
  
  // $legend can be
  //    (a) an ElementLegend object, or
  //    (b) another element, or
  //    (c) a string
  // If it is (b) or (c) then it is wrapped inside a Legend element.
  public function addLegend($legend)
  {
    if (is_object($legend) &&
        (__NAMESPACE__ . "\\ElementLegend" == get_class($legend)))
    {
      $element = $legend;
    }
    else
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
    }
    
    $this->addElement($element);
    return $this;
  }
  
}