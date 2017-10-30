<?php

namespace MRBS\Form;


class FieldTextarea extends Field
{
  
  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('class', 'field_text_area')
         ->addControl(new ElementTextarea());
  }
  
}