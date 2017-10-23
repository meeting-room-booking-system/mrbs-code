<?php

namespace MRBS\Form;


abstract class Field extends Element
{
  
  public function __construct()
  {
    // Wrap all fields in a <div> ... </div>
    parent::__construct('div');
    $this->addElement(new ElementLabel());
  }
  
  
  public function setLabel($text)
  {
    $elements = $this->getElements();
    $elements[0]->setText($text);
    $this->setElements($elements);
    return $this;
  }
  
  
  public function setFieldAttributes($attributes)
  {
    $elements = $this->getElements();
    
    foreach ($attributes as $key => $value)
    {
      if ($key == 'id')
      {
        $elements[0]->setAttribute('for', $value);
      }
      $elements[1]->setAttribute($key, $value);
    }
    
    $this->setElements($elements);
    return $this;
  }
}