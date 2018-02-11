<?php

namespace MRBS\Form;


class FieldInputCheckbox extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputCheckbox());
  }
  
  public function setChecked($checked=true)
  {
    $control = $this->getControl();
    $control->setChecked($checked);
    $this->setControl($control);
  }
}