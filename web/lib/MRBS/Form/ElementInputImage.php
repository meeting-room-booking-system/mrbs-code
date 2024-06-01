<?php
declare(strict_types=1);
namespace MRBS\Form;

class ElementInputImage extends ElementInput
{

  public function __construct()
  {
    parent::__construct();
    $this->setAttribute('type', 'image');
  }

}
