<?php
declare(strict_types=1);
namespace MRBS\Form;


class FieldInputDatalist extends Field
{

  public function __construct()
  {
    parent::__construct();
    $this->addControl(new ElementInputDatalist());
  }


  public function addDatalistOptions(array $options, ?bool $associative=null) : FieldInputDatalist
  {
    $datalist = $this->getControl();
    $datalist->addDatalistOptions($options, $associative);
    $this->setControl($datalist);
    return $this;
  }

}
