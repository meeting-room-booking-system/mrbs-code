<?php

namespace MRBS\Form;

class ElementSubmit extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'submit');
  }
 
}