<?php
declare(strict_types=1);
namespace MRBS\Form;


// Fields consider of a 'label' element, ie a <label>, and a control
// element, eg an <input> or <select>, all wrapped in a <div>.  For example
//    <div>
//      <label></label>
//      <input>
//    </div>

abstract class Field extends Element
{
  // $is_group records whether the field consists of a group of controls
  // (eg radio buttons) or just a single control, in which case a label
  // can be associated with it.
  protected $is_group = false;

  public function __construct()
  {
    // Wrap all fields in a <div> ... </div>
    parent::__construct('div');
    $this->addElement(new ElementLabel(), 'label');
  }


  public function addControl(Element $element): Field
  {
    $this->addElement($element, 'control');
    return $this;
  }


  public function getControl(): Element
  {
    return $this->getElement('control');
  }


  public function setControl(Element $element): Field
  {
    $this->setElement('control', $element);
    return $this;
  }


  public function removeControl(): Field
  {
    $this->removeElement('control');
    return $this;
  }


  // If $raw is true then the text will not be put through escape_html().  Only to
  // be used for trusted text.
  public function setLabel($text, bool $text_at_start=false, bool $raw=false): Field
  {
    $label = $this->getElement('label');
    $label->setText($text, $text_at_start, $raw);
    $this->setElement('label', $label);
    return $this;
  }


  // Sets an attribute for the field control.  Also takes care of the label
  // by associating the label with the control using a 'for' attribute, by
  // using the 'id' if one is given, or if not, by assuming that the 'id'
  // is the same as the 'name' (unless $add_id is FALSE, in which case an id
  // won't be added).
  public function setControlAttribute(string $name, $value=true, bool $add_id=true): Field
  {
    $elements = $this->getElements();

    // If this is the name attribute and we haven't yet got an id, then
    // make the id the same as the name - unless $add_id is FALSE
    if ($add_id && ($name == 'name') && (null === $elements['control']->getAttribute('id')))
    {
      $this->setControlAttribute('id', $value);
    }

    // If this is an id and it's not a group field, then associate the
    // label with the id
    if (!$this->is_group && ($name == 'id'))
    {
      $elements['label']->setAttribute('for', $value);
    }

    $elements['control']->setAttribute($name, $value);

    $this->setElements($elements);
    return $this;
  }


  // Sets the attributes for the field control.
  public function setControlAttributes(array $attributes, bool $add_id=true): Field
  {
    foreach ($attributes as $key => $value)
    {
      $this->setControlAttribute($key, $value, $add_id);
    }
    return $this;
  }


  public function addControlClass(string $class): Field
  {
    $elements = $this->getElements();
    $elements['control']->addClass($class);
    $this->setElements($elements);
    return $this;
  }


  public function addLabelClass(string $class): Field
  {
    $elements = $this->getElements();
    $elements['label']->addClass($class);
    $this->setElements($elements);
    return $this;
  }


  public function setControlChecked($checked=true): Field
  {
    $elements = $this->getElements();
    $elements['control']->setChecked($checked);
    $this->setElements($elements);
    return $this;
  }


  public function setControlText(string $text): Field
  {
    $elements = $this->getElements();
    $elements['control']->setText($text);
    $this->setElements($elements);
    return $this;
  }


  public function addControlElement(Element $element): Field
  {
    $elements = $this->getElements();
    $elements['control']->addElement($element);
    $this->setElements($elements);
    return $this;
  }


  public function addLabelElement(Element $element): Field
  {
    $elements = $this->getElements();
    $elements['label']->addElement($element);
    $this->setElements($elements);
    return $this;
  }


  public function setLabelAttribute(string $name, $value=true): Field
  {
    $elements = $this->getElements();
    $elements['label']->setAttribute($name, $value);
    $this->setElements($elements);
    return $this;
  }


  // Sets the attributes for the field label.  No need to do
  // the 'for' attribute, as that is done automatically when you
  // set the 'id' in the control attributes.
  public function setLabelAttributes(array $attributes): Field
  {
    $elements = $this->getElements();

    foreach ($attributes as $key => $value)
    {
      $elements['label']->setAttribute($key, $value);
    }

    $this->setElements($elements);
    return $this;
  }


  public function removeLabelAttribute(string $name): Field
  {
    $elements = $this->getElements();
    $elements['label']->removeAttribute($name);
    $this->setElements($elements);
    return $this;
  }


  // Adds a hidden input to the form
  public function addHiddenInput(string $name, $value) : Field
  {
    $element = new ElementInputHidden($name, $value);
    $this->addElement($element);
    return $this;
  }

}
