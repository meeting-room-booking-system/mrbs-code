<?php
declare(strict_types=1);
namespace MRBS\Form;


class FieldSelect extends Field
{

  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementSelect());
  }


  public function addSelectOptions(array $options, $selected=null, ?bool $associative=null, bool $for_datalist=false): Element
  {
    $select = $this->getControl();
    $select->addSelectOptions($options, $selected, $associative);
    $this->setControl($select);
    return $this;
  }

}
