<?php

namespace MRBS\Form;


class FieldInputDatalist extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputDatalist());
  }
  
  
  public function addDatalistOptions(array $options, $associative=null)
  {
    $datalist = $this->getControl();
    $datalist->addDatalistOptions($options, $associative);
    $this->setControl($datalist);
    return $this;
  }
  
}