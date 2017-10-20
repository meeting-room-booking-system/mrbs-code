<?php

namespace MRBS\Form;


abstract class Field extends Element
{
  
  public function __construct($legend=null)
  {
    // Wrap all fields in a <div> ... </div>
    parent::__construct('div');
  }
  
}