<?php

namespace MRBS\Form;

class ElementLegend extends Element
{

  public function __construct($legend)
  {
    parent::__construct('legend');
    $this->text = $legend;
  }
 
}