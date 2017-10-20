<?php

namespace MRBS\Form;


class FieldText extends Field
{
  
  public function __construct($params)
  {
    parent::__construct();
    
    // The label
    $this->addElement(new ElementLabel($params['label'], $params['id']));
    
    // The <input> element
    $input = new ElementText($params['name']);
    foreach ($params as $key => $value)
    {
      if (!in_array($key, array('label', 'name')))  // We've already used these above
      {
        $input->setAttribute($key, $value);
      }
    }
    $this->addElement($input);

  }
  
}