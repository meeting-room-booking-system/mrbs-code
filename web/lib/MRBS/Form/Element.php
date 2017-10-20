<?php

namespace MRBS\Form;

abstract class Element
{
  protected $tag;
  protected $self_closing;
  protected $attributes = array();
  protected $text = null;
  protected $elements = array();
  
  
  public function __construct($tag, $self_closing=false)
  {
    $this->tag = $tag;
    $this->self_closing = false;
  }
  
  
  public function setAttribute($name, $value=null)
  {
    $this->attributes[] = array('name' => $name, 'value' => $value);
    return $this;
  }
  
  
  public function setAttributes(array $attributes)
  {
    foreach ($attributes as $name => $value)
    {
      $this->setAttribute($name, $value);
    }
    
    return $this;
  }
  

  public function addElement(Element $element)
  {
    $this->elements[] = $element;
    return $this;
  }
  
  
  public function render()
  {
    echo $this->toHTML();
  }
  
  
  // Turns the form into HTML.   HTML escaping is done here.
  public function toHTML()
  {
    $html = "";
    
    $html .= "<" . $this->tag;
    
    foreach ($this->attributes as $attribute)
    {
      $html .= " " . $attribute['name'];
      if (isset($attribute['value']))
      {
        $html .= '="' . htmlspecialchars($attribute['value']) . '"';
      }
    }
    $html .= ">";
    
    if ($this->self_closing)
    {
      $html .= "\n";
    }
    else
    {
      // This code assumes that an element contains either text or other elements.
      // In fact the real HTML definition allows both, but we will impose this
      // restriction to keep things simple.
      if (isset($this->text))
      {
        $html .= htmlspecialchars($this->text);
      }
      else
      {
        $html .= "\n";
        foreach ($this->elements as $element)
        {
          $html .= $element->toHTML();
        }
      }
      $html .= "</" . $this->tag . ">\n";
    }
    
    return $html;
  }
  
}