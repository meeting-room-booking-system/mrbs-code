<?php

namespace MRBS\Form;

class ElementInputDatalist extends ElementInput
{
  private static $list_prefix = "mrbs_";
  private static $list_number = 1;
  
  public function __construct()
  {
    parent::__construct();
    
    // Provide a unique id to link the list with the input.
    // Doesn't matter what it is as it won't be used elsewhere.
    $list_id = self::$list_prefix . self::$list_number;
    self::$list_number++;
    
    $this->setAttribute('list', $list_id);
                         
    $datalist = new ElementDatalist();
    $datalist->setAttribute('id', $list_id);
    
    $this->addElement($datalist);
  }
 
}