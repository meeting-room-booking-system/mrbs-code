<?php

namespace MRBS\Form;


// Fields consider of a 'label' element, ie a <label>, and a control
// element, eg an <input> or <select>, all wrapped in a <div>.  For example
//    <div>
//      <label></label>
//      <input>
//    </div>

abstract class Field extends Element
{
  
  public function __construct()
  {
    // Wrap all fields in a <div> ... </div>
    parent::__construct('div');
    $this->addElement(new ElementLabel(), 'label');
  }
  
  
  public function addControl(Element $element)
  {
    $this->addElement($element, 'control');
    return $this;
  }
  
  
  public function getControl()
  {
    return $this->getElement('control');
  }
  
  
  public function setControl(Element $element)
  {
    $this->setElement('control', $element);
    return $this;
  }
  
  
  public function setLabel($text)
  {
    $label = $this->getElement('label');
    $label->setText($text);
    $this->setElement('label', $label);
    return $this;
  }
  
  
  // Sets the attributes for the field control.  Also takes care of the label
  // by associating the label with the control using a 'for' attribute.
  public function setControlAttributes($attributes)
  {
    $elements = $this->getElements();
    
    foreach ($attributes as $key => $value)
    {
      if ($key == 'id')
      {
        $elements['label']->setAttribute('for', $value);
      }
      $elements['control']->setAttribute($key, $value);
    }
    
    $this->setElements($elements);
    return $this;
  }
  
  
  // Sets the attributes for the field label.  No need to do
  // the 'for' attribute, as that is done automatically when you
  // set the 'id' in the control attrributes.
  public function setLabelAttributes($attributes)
  {
    $elements = $this->getElements();
    
    foreach ($attributes as $key => $value)
    {
      $elements['label']->setAttribute($key, $value);
    }
    
    $this->setElements($elements);
    return $this;
  }
}