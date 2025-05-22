<?php
declare(strict_types=1);
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

use function MRBS\escape_html;
use function MRBS\is_assoc;

class Element
{
  private $tag;
  private $self_closing;
  private $attributes = array();
  private $text = null;
  private $raw = false;
  private $text_at_start = false;
  private $elements = [];
  private $next = null;
  private $prev = null;


  public function __construct(string $tag, bool $self_closing=false)
  {
    $this->tag = $tag;
    $this->self_closing = $self_closing;
  }


  // If $raw is true then the text will not be put through escape_html().  Only to
  // be used for trusted text.
  public function setText(string $text, bool $text_at_start=false, bool $raw=false) : Element
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


  public function getAttribute(string $name)
  {
    return (isset($this->attributes[$name])) ? $this->attributes[$name] : null;
  }


  // A value of true allows for the setting of boolean attributes such as
  // 'required' and 'disabled'
  public function setAttribute(string $name, $value=true) : Element
  {
    $this->attributes[$name] = $value;
    return $this;
  }


  public function setAttributes(array $attributes) : Element
  {
    foreach ($attributes as $name => $value)
    {
      $this->setAttribute($name, $value);
    }

    return $this;
  }


  public function removeAttribute(string $name) : Element
  {
    unset($this->attributes[$name]);
    return $this;
  }


  public function getElement(string $key) : Element
  {
    return $this->elements[$key];
  }


  public function setElement(string $key, Element $element) : Element
  {
    $this->elements[$key] = $element;
    return $this;
  }


  public function getElements() : array
  {
    return $this->elements;
  }


  public function setElements(array $elements) : Element
  {
    $this->elements = $elements;
    return $this;
  }


  public function addElement(?Element $element=null, ?string $key=null) : Element
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


  public function addElements(array $elements) : Element
  {
    foreach ($elements as $element)
    {
      $this->addElement($element);
    }
    return $this;
  }


  public function removeElement(string $key) : Element
  {
    unset($this->elements[$key]);
    return $this;
  }


  public function next(?Element $element=null) : ?Element
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


  public function prev(?Element $element=null): ?Element
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


  public function addClass(string $class) : Element
  {
    $classes = $this->getAttribute('class');

    $classes = (isset($classes)) ? explode(' ', $classes) : array();
    if (!in_array($class, $classes))
    {
      $classes[] = $class;
      $this->setAttribute('class', implode(' ', $classes));
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
  //                  simple array).   Can take the following values:
  //                      true    treat as an associative array
  //                      false   treat as a simple array
  //                      null    auto-detect
  //    $for_datalist Whether the options are intended for use in a <datalist>.
  public function addSelectOptions(array $options, $selected=null, ?bool $associative=null, bool $for_datalist=false) : Element
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }

    if (!isset($associative))
    {
      $associative = is_assoc($options);
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
      if ($for_datalist)
      {
        throw new \InvalidArgumentException("Datalists cannot have <optgroup> elements.");
      }
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
      foreach ($options as $value => $text)
      {
        $option = new ElementOption();

        // We need to be careful about strings with multiple consecutive spaces in them.  If we have a <select> element
        // with simple options of the form <option>Joe  Smith</option>, then the text "Joe  Smith" will be contracted
        // to "Joe Smith".  This means that (a) it will look wrong on the form and (b) the wrong value will be passed
        // through to the form handler.  One way of getting round this would be to substitute the extra spaces with
        // non-breaking space characters.  However, although this will now look correct on the form, the value that is
        // passed through to the form handler will contain non-breaking spaces instead of ordinary spaces.  A query
        // using this value to find a row in the database will rely on the database engine treating ordinary and non-
        // breaking spaces as equivalent. While this will be true for many collations, it won't always be so.
        //
        // The solution is to place the text as is in the value attribute, but to replace the spaces in the text node
        // with non-breaking spaces, eg <option value="Joe  Smith">Joe  Smith</option>.  Because extra spaces in
        // attributes are preserved, the correct string will be passed through to the form handler.  And because
        // there are now non-breaking spaces in the text node, the string is displayed properly in the browser.  If the
        // options are in the form of an associative array with values and text, then the value will be used for the
        // value attribute and the text can have its spaces replaced.
        //
        // This solution doesn't work though for <datalist> elements, at least not when using Chrome or Safari.
        // According to https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/datalist "Each <option>
        // element should have a value attribute, which represents a suggestion to be entered into the input. It can
        // also have a label attribute, or, missing that, some text content, which may be displayed by the browser
        // instead of value (Firefox), or in addition to value (Chrome and Safari, as supplemental text). The exact
        // content of the drop-down menu depends on the browser, but when clicked, content entered into control field
        // will always come from the value attribute."  In other words, if the value and text are different - even in
        // the type of space character used - then, when using Chrome and Safari, they are both displayed by the
        // browser, which looks a little odd, when they are essentially the same string.
        //
        // So for <datalist> elements, which are never associative anyway, we just use the value attribute and have an
        // empty text node, eg <option value="Joe  Smith"></option>.
        //
        // See also https://github.com/meeting-room-booking-system/mrbs-code/issues/3871 and
        // https://stackoverflow.com/questions/79629259/does-mysql-distinguish-betwen-ordinary-and-non-breaking-spaces-in-a-query

        $option->setAttribute('value', ($associative) ? $value : $text);

        if (!$for_datalist || $associative)
        {
          // If it's a string replace the second and subsequent spaces with non-breaking spaces
          $text = (is_string($text)) ? preg_replace('/(?<= ) /', "\xc2\xa0", $text) : strval($text);
          $option->setText($text);
        }

        if (!$for_datalist && in_array($value, $selected))
        {
          $option->setAttribute('selected');
        }

        $this->addElement($option);
      }
    }

    return $this;
  }


  // $checked is either a scalar or an array of keys that are checked
  public function addCheckboxOptions(array $options, string $name, $checked=null, $associative=null, bool $disabled=false): Element
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
      $associative = is_assoc($options);
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
      $label->setText(strval($value))
            ->addElement($checkbox);

      $this->addElement($label);
    }

    return $this;
  }


  public function addRadioOptions(array $options, string $name, $checked=null, $associative=null, bool $disabled=false): Element
  {
    // Trivial case
    if (empty($options))
    {
      return $this;
    }

    if (!isset($associative))
    {
      $associative = is_assoc($options);
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
      $label->setText(strval($value))
            ->addElement($radio);

      $this->addElement($label);
    }

    return $this;
  }


  public function render() : void
  {
    echo $this->toHTML();
  }


  // Turns the form into HTML.   HTML escaping is done here.
  // If $no_whitespace is true, then don't put any whitespace after opening or
  // closing tags.   This is useful for structures such as
  // <label><input>text</label> where whitespace after the <input> tag would
  // affect what the browser displays on the screen.
  public function toHTML(bool $no_whitespace=false): string
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
      if ($value !== true)
      {
        // boolean attributes, eg 'required', don't need a value
        $html .= '="' . escape_html($value) . '"';
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


  private static function escapeText($text, bool $raw=false)
  {
    return ($raw) ? $text : escape_html($text);
  }

}
