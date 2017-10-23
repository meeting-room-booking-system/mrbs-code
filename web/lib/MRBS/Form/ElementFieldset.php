<?php

namespace MRBS\Form;


class ElementFieldset extends Element
{
  
  public function __construct()
  {
    parent::__construct('fieldset');
  }
  
  
  public function addLegend($text)
  {
    $legend = new ElementLegend();
    $legend->setText($text);
    $this->addElement($legend);
    return $this;
  }
  
}