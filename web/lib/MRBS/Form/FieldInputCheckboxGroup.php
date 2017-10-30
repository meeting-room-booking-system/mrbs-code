<?php

namespace MRBS\Form;


class FieldInputCheckboxGroup extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementDiv())
         ->setControlAttributes(array('class' => 'group'));
  }
  
  
  public function addCheckboxOptions(array $options, $name, $checked=null, $associative=true)
  {
    $element = $this->getControl();
    $element->addCheckboxOptions($options, $name, $checked, $associative);
    $this->setControl($element);
    return $this;
  }
  
}