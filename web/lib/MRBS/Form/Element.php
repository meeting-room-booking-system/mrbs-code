<?php

namespace MRBS\Form;

// This is a limited implementation of the HTML5 DOM and is optimised for use by
// MRBS forms.   It makes a number of simplifications and restrictions.  In particular
// it assumes that:

//    (a) an element can only contain one text node, and
//    (b) that text node either comes before or after all the element nodes that it contains
//
// In the full DOM an element can contain multiple text nodes, for example

//    <p>Some text<b>a bold bit</b>some more text</p>
//
// If structures like these are required ten they can usually be achieved by wrapping the raw
// text nodes in a <span>.

class Element
{
  private $tag = null;
  private $self_closing = false;
  private $attributes = array();
  private $text = null;
  private $text_at_start = false;
  private $elements = array();
  
  
  public function __construct($tag, $self_closing=false)
  {
    $this->tag = $tag;
    $this->self_closing = $self_closing;
  }
  
  
  public function setText($text, $text_at_start=false)
  {
    if ($this->self_closing)
    {
      throw new \Exception("A self closing element cannot contain text.");
    }
    
    $this->text = $text;
    $this->text_at_start = $text_at_start;
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
  
  
  // Add a set of select options to an element, eg to a <select> or <datalist> element.
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
  public function addSelectOptions(array $options, $selected=null, $associative=true)
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
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
                 ->addSelectOptions($group_options, $selected, $associative);
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
    
    return $this;
  }
  
  
  public function addCheckboxOptions(array $options, $name, $checked=null, $associative=true)
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }
    
    foreach ($options as $key => $value)
    {
      if (!$associative)
      {
        $key = $value;
      }
      $checkbox = new ElementInputCheckbox();
      $checkbox->setAttributes(array('name'  => $name,
                                     'value' => $key));
      if (isset($checked) && ($key == $checked))
      {
        $checkbox->setAttribute('checked');
      }
      $label = new ElementLabel();
      $label->setText($value)
            ->addElement($checkbox);
            
      $this->addElement($label);
    }
    
    return $this;
  }
  
  
  public function addRadioOptions(array $options, $name, $checked=null, $associative=true)
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }
    
    foreach ($options as $key => $value)
    {
      if (!$associative)
      {
        $key = $value;
      }
      $radio = new ElementInputRadio();
      $radio->setAttributes(array('name'  => $name,
                                  'value' => $key));
      if (isset($checked) && ($key == $checked))
      {
        $radio->setAttribute('checked');
      }
      $label = new ElementLabel();
      $label->setText($value)
            ->addElement($radio);
            
      $this->addElement($label);
    }
    
    return $this;
  }
  
  
  public function render()
  {
    echo $this->toHTML();
  }
  
  
  // Turns the form into HTML.   HTML escaping is done here.
  // If $no_whitespace is true, then don't put any whitespace after opening or
  // closing tags.   This is useful for structures such as
  // <label><input>text</label> where whitespace after the <input> tag would
  // affect what the browser displays on the screen.
  public function toHTML($no_whitespace=false)
  {
    $terminator = ($no_whitespace) ? '' : "\n";
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
      $html .= $terminator;
    }
    else
    {
      if (isset($this->text) && $this->text_at_start)
      {
        $html .= htmlspecialchars($this->text);
      }
      
      if (!empty($this->elements))
      {
        // If this element contains text, then don't use a terminator, otherwise
        // unwanted whitespace will be introduced.
        if (!isset($this->text))
        {
          $html .= $terminator;
        }
        foreach ($this->elements as $element)
        {
          $html .= $element->toHTML(isset($this->text));
        }
      }
      
      if (isset($this->text) && !$this->text_at_start)
      {
        $html .= htmlspecialchars($this->text);
      }

      $html .= "</" . $this->tag . ">$terminator";
    }
    
    return $html;
  }
  
}