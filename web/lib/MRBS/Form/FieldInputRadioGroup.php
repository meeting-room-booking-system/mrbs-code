<?php

namespace MRBS\Form;


class FieldInputRadioGroup extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementDiv())
         ->setControlAttributes(array('class' => 'group'));
  }
  
  
  public function addRadioOptions(array $options, $name, $checked=null, $associative=true)
  {
    $element = $this->getControl();
    $element->addRadioOptions($options, $name, $checked, $associative);
    $this->setControl($element);
    return $this;
  }
  
}