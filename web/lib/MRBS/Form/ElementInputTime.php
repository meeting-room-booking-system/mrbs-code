<?php

namespace MRBS\Form;

class ElementInputTime extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttributes(array('type'     => 'time',
                               'required' => true));
  }
 
}