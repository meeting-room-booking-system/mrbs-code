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
  
  
  // A value of null allows for the setting of attributes such as
  // 'required' and 'disabled'
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
  

  public function getElement($key)
  {
    return $this->elements[$key];
  }
  
  
  public function setElement($key, Element $element)
  {
    $this->elements[$key] = $element;
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
  
  
  public function addElement(Element $element, $key=null)
  {
    if (isset($key))
    {
      $this->elements[$key] = $element;
    }
    else
    {
      $this->elements[] = $element;
    }
    return $this;
  }
  
  
  // Add a set of options to an element, eg to a <select> element.
  //    $options      An array of options for the select element.   Can be a one- or two-dimensional
  //                  array.  If it's two-dimensional then the keys of the outer level represent
  //                  <optgroup> labels.  The inner level can be a simple array or an associative
  //                  array with value => text members for each <option> in the <select> element.
  //    $selected     The value(s) of the option(s) that are selected.  Can be a single value
  //                  or an array of values.
  //    $associative  Whether to treat the options as a simple or an associative array.  (This 
  //                  parameter is necessary because if you index an array with strings that look
  //                  like integers then PHP casts the keys to integers and the array becomes a 
  //                  simple array).
  public function addOptions($options, $selected=null, $associative=true)
  {
    // Trivial case
    if (empty($options))
    {
      return;
    }
    
    // It's possible to have multiple options selected
    if (!is_array($selected))
    {
      $selected = array($selected);
    }
    
    // Test whether $options is a one-dimensional or two-dimensional array.
    // If two-dimensional then we need to use <optgroup>s.
    if (is_array(reset($options)))   // cannot use $options[0] because $options may be associative
    {
      foreach ($options as $group => $group_options)
      {
        $optgroup = new ElementOptgroup();
        $optgroup->setAttribute('label', $group)
                 ->addOptions($group_options, $selected, $associative);
        $this->addElement($optgroup);
      }
    }
    else
    {
      foreach ($options as $key => $value)
      {
        if (!$associative)
        {
          $key = $value;
        }
        $option = new ElementOption();
        $option->setAttribute('value', $key)
               ->setText($value);
        if (in_array($key, $selected))
        {
          $option->setAttribute('selected');
        }
        $this->addElement($option);
      }
    }
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