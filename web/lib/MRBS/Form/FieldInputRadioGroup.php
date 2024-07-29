<?php
declare(strict_types=1);
namespace MRBS\Form;


class FieldInputRadioGroup extends Field
{

  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementDiv())
         ->setControlAttributes(array('class' => 'group'));
    $this->is_group = true;
  }


  public function addRadioOptions(array $options, string $name, $checked=null, $associative=null, bool $disabled=false): Element
  {
    $element = $this->getControl();
    $element->addRadioOptions($options, $name, $checked, $associative, $disabled);
    $this->setControl($element);
    return $this;
  }

}
