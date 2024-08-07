<?php
declare(strict_types=1);
namespace MRBS\Form;

class ElementInputCheckbox extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'checkbox');
  }


  public function setChecked($checked=true): ElementInputCheckbox
  {
    if ($checked)
    {
      $this->setAttribute('checked');
    }
    else
    {
      $this->removeAttribute('checked');
    }

    return $this;
  }

}
