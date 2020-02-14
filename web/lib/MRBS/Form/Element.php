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
  private $raw = false;
  private $text_at_start = false;
  private $elements = array();
  private $next = null;
  private $prev = null;


  public function __construct($tag, $self_closing=false)
  {
    $this->tag = $tag;
    $this->self_closing = $self_closing;
  }


  // If $raw is true then the text will not be put through htmlspecialchars().  Only to
  // be used for trusted text.
  public function setText($text, $text_at_start=false, $raw=false)
  {
    if ($this->self_closing)
    {
      throw new \Exception("A self closing element cannot contain text.");
    }

    $this->text = $text;
    $this->text_at_start = $text_at_start;
    $this->raw = $raw;

    return $this;
  }


  public function getAttribute($name)
  {
    return (isset($this->attributes[$name])) ? $this->attributes[$name] : null;
  }


  // A value of true allows for the setting of boolean attributes such as
  // 'required' and 'disabled'
  public function setAttribute($name, $value=true)
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


  public function removeAttribute($name)
  {
    unset($this->attributes[$name]);
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


  public function addElement(Element $element=null, $key=null)
  {
    if (isset($element))
    {
      if (isset($key))
      {
        $this->elements[$key] = $element;
      }
      else
      {
        $this->elements[] = $element;
      }
    }

    return $this;
  }


  public function addElements(array $elements)
  {
    foreach ($elements as $element)
    {
      $this->addElement($element);
    }
    return $this;
  }


  public function removeElement($key)
  {
    unset($this->elements[$key]);
    return $this;
  }


  public function next(Element $element=null)
  {
    if (isset($element))
    {
      $this->next = $element;
      return $this;
    }
    elseif (isset($this->next))
    {
      return $this->next;
    }
    else
    {
      return null;
    }
  }


  public function prev(Element $element=null)
  {
    if (isset($element))
    {
      $this->prev = $element;
      return $this;
    }
    elseif (isset($this->prev))
    {
      return $this->prev;
    }
    else
    {
      return null;
    }
  }


  public function addClass($class)
  {
    $classes = $this->getAttribute('class');

    $classes = (isset($classes)) ? explode(' ', $classes) : array();
    $classes[] = $class;
    $this->setAttribute('class', implode(' ', $classes));

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
  //                  simple array).   Can take the following values:
  //                      true    treat as an associative array
  //                      false   treat as a simple array
  //                      null    auto-detect
  public function addSelectOptions(array $options, $selected=null, $associative=null)
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }

    if (!isset($associative))
    {
      $associative = \MRBS\is_assoc($options);
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
        $option = new ElementOption();

        if ($associative)
        {
          $option->setAttribute('value', $key);
        }

        $option->setText($value);

        if (!$associative)
        {
          $key = $value;
        }

        if (in_array($key, $selected))
        {
          $option->setAttribute('selected');
        }

        $this->addElement($option);
      }
    }

    return $this;
  }


  // $checked is either a scalar or an array of keys that are checked
  public function addCheckboxOptions(array $options, $name, $checked=null, $associative=null, $disabled=false)
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }

    if (is_scalar($checked))
    {
      $checked = array($checked);
    }

    if (!isset($associative))
    {
      $associative = \MRBS\is_assoc($options);
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
      if (isset($checked) && (in_array($key, $checked)))
      {
        $checkbox->setChecked(true);
      }

      if ($disabled)
      {
        $checkbox->setAttribute('disabled', true);
      }

      $label = new ElementLabel();
      $label->setText($value)
            ->addElement($checkbox);

      $this->addElement($label);
    }

    return $this;
  }


  public function addRadioOptions(array $options, $name, $checked=null, $associative=null, $disabled=false)
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }

    if (!isset($associative))
    {
      $associative = \MRBS\is_assoc($options);
    }

    foreach ($options as $key => $value)
    {
      if (!$associative)
      {
        $key = $value;
      }
      $radio = new ElementInputRadio();
      $radio->setAttributes(array('name'     => $name,
                                  'value'    => $key,
                                  'disabled' => $disabled));
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
    $html = "";

    $prev = $this->prev();
    if (isset($prev))
    {
      $html .= $prev->toHTML();
    }

    $terminator = ($no_whitespace) ? '' : "\n";
    $html .= "<" . $this->tag;

    foreach ($this->attributes as $key => $value)
    {
      if (!isset($value) || ($value === false))
      {
        // a boolean attribute, or else an empty attribute, that should be omitted.
        // We allow the empty string, '',  because that can be used, for example, in
        // 'value=""' as an attribute for the <option> element in a <select> element
        // that has the 'required' attribute set.
        continue;
      }

      $html .= " $key";
      if (isset($value) && ($value !== true))
      {
        // boolean attributes, eg 'required', don't need a value
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
        $html .= self::escapeText($this->text, $this->raw);
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
        $html .= self::escapeText($this->text, $this->raw);
      }

      $html .= "</" . $this->tag . ">$terminator";
    }

    $next = $this->next();
    if (isset($next))
    {
      $html .= $next->toHTML();
    }

    return $html;
  }


  private static function escapeText($text, $raw=false)
  {
    return ($raw) ? $text : htmlspecialchars($text);
  }

}
