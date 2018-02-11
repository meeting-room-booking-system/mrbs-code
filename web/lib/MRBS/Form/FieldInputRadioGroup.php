<?php

namespace MRBS\Form;


class FieldInputRadioGroup extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementDiv())
         ->setControlAttributes(array('class' => 'group'));
    $this->is_group = true;
  }
  
  
  public function addRadioOptions(array $options, $name, $checked=null, $associative=null, $disabled=false)
  {
    $element = $this->getControl();
    $element->addRadioOptions($options, $name, $checked, $associative, $disabled);
    $this->setControl($element);
    return $this;
  }
  
}