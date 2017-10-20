<?php

namespace MRBS\Form;


class FieldText extends Field
{
  
  public function __construct($params)
  {
    parent::__construct();
    $this->addElement(new ElementLabel($params['label'], $params['id']));
    $this->addElement(new ElementText($params['name']));
    foreach ($params as $key => $value)
    {
      if (!in_array($key, array($params['label'], $params['name'])))
      {
        $this->setAttribute($key, $value);
      }
    }
  }
  
}