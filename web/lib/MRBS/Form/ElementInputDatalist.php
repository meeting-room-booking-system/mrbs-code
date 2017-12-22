<?php

namespace MRBS\Form;

class ElementInputDatalist extends ElementInput
{
  private static $list_prefix = "mrbs_";
  private static $list_number = 1;
  
  public function __construct()
  {
    // One problem with using a datalist with an input element is the way different browsers
    // handle autocomplete.  If you have autocomplete on, and also an id or name attribute, then some
    // browsers, eg Edge, will bring the history up on top of the datalist options so that you can't
    // see the first few options.  But if you have autocomplete off, then other browsers, eg Chrome,
    // will not present the datalist options at all.  This can be fixed in JavaScript by having a second,
    // hidden, input which holds the actual form value and mirrors the visible input.  Because we can't
    // rely on JavaScript being enabled we will create the basic HTML using autocomplete on, ie the default,
    // which is the least bad alternative.   One disadvantage of this method is that the label is no longer
    // tied to the visible input, but this isn't as important for a text input as it is, say, for a checkbox
    // or radio button.
    parent::__construct();
    
    // Provide a unique id to link the list with the input.
    // Doesn't matter what it is as it won't be used elsewhere.
    $list_id = self::$list_prefix . self::$list_number;
    self::$list_number++;
    
    $this->setAttributes(array('type' => 'text',
                               'list' => $list_id));
                         
    $datalist = new ElementDatalist();
    $datalist->setAttribute('id', $list_id);
    
    $this->next($datalist);
  }
  
  
  public function addDatalistOptions(array $options, $associative=null)
  { 
    // Put a <select> wrapper around the options so that browsers that don't
    // support <datalist> will still have the options in their DOM and then
    // the JavaScript polyfill can find them and do something with them
    $select = new ElementSelect();
    $select->setAttribute('style', 'display: none');
    $select->addSelectOptions($options, null, $associative);
    
    $this->next($this->next()->addElement($select));
    
    return $this;
  }
 
}