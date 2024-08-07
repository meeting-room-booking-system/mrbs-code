<?php
declare(strict_types=1);
namespace MRBS\Form;

abstract class ElementInput extends Element
{

  public function __construct()
  {
    parent::__construct('input', true);
    $this->setAttribute('type', 'text');
  }

}
