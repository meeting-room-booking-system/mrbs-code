<?php

namespace MRBS\Form;

class ElementInputHidden extends ElementInput
{

  public function __construct(?string $name=null, $value=null)
  {
    parent::__construct();
    $this->setAttribute('type', 'hidden');

    if (isset($name))
    {
      $this->setAttribute('name', $name);
    }

    if (isset($value))
    {
      if (is_bool($value))
      {
        $value = ($value) ? 1 : 0;
      }
      $this->setAttribute('value', $value);
    }
  }

}
