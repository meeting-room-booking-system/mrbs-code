<?php

namespace MRBS\Form;


class FieldSubmit extends Field
{
  
  public function __construct($params)
  {
    parent::__construct();
    $submit = new ElementSubmit();
    foreach ($params as $key => $value)
    {
      $submit->setAttribute($key, $value);
    }
    $this->addElement($submit);
  }
  
}