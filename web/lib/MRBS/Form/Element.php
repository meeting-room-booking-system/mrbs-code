<?php

namespace MRBS\Form;

class Element
{
  private $tag = null;
  private $self_closing;
  private $attributes = array();
  private $text = null;
  private $elements = array();
  
  
  public function __construct($tag, $self_closing=false)
  {
    $this->tag = $tag;
    $this->self_closing = $self_closing;
  }
  
  public function setText($text)
  {
    $this->text = $text;
    return $this;
  }
  
  public function setAttribute($name, $value=null)
  {
    $this->attributes[$name] = $value;
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
  

  public function getElements()
  {
    return $this->elements;
  }
  
  
  public function setElements(array $elements)
  {
    $this->elements = $elements;
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
    
    foreach ($this->attributes as $key => $value)
    {
      $html .= " $key";
      if (isset($value))
      {
        $html .= '="' . htmlspecialchars($value) . '"';
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
      elseif (!empty($this->elements))
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