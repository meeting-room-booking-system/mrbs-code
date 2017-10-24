<?php

namespace MRBS\Form;


class FieldSelect extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementSelect());
  }
  
  
  public function addOptions($options, $selected=null, $associative=true)
  {
    $select = $this->getControl();
    $select->addOptions($options, $selected, $associative);
    $this->setControl($select);
    return $this;
  }
  
}