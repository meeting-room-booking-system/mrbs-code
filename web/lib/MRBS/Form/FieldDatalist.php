<?php

namespace MRBS\Form;


class FieldDatalist extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementDatalist());
  }
  
  
  public function addDatalistOptions(array $options, $associative=true)
  {
    $datalist = $this->getControl();
    $datalist->addDatalistOptions($options, $associative);
    $this->setControl($datalist);
    return $this;
  }
  
}