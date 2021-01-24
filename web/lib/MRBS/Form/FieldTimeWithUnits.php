<?php

namespace MRBS\Form;


class FieldTimeWithUnits extends FieldDiv
{

  // Constructs a field that has an enabling checkbox and then inputs for
  // the quantity and units of time.
  //
  // $param_names   An array of the parameter names, indexed by
  //                'enabler', 'quantity' and 'seconds'
  // $enabled       The current value of the enabling checkbox
  // $seconds       The current value of the field, in seconds
  // $suffix        Optional text that can appear after the units
  // $input_attributes    Optional array of additional attributes for the input
  public function __construct(array $param_names, $enabled, $seconds, $suffix=null, $input_attributes=null)
  {
    // We can get rid of the assert when the minimum PHP version is 7.1 or greater and
    // we can use a nullable type
    assert(is_null($input_attributes) || is_array($input_attributes),
           '$input_attributes must be null or an array');

    parent::__construct();

    // Convert the raw seconds into as large a unit as possible
    $duration = $seconds;
    \MRBS\toTimeString($duration, $units);

    // The checkbox, which enables or disables the field
    $checkbox = new ElementInputCheckbox();
    $checkbox->setAttributes(array('name'  => $param_names['enabler'],
                                   'class' => 'enabler'))
             ->setChecked($enabled);
    $this->addControlElement($checkbox);

    // The quantity element
    $input = new ElementInputNumber();
    $attributes = array('name'  => $param_names['quantity'],
                        'value' => $duration);
    if (isset($input_attributes))
    {
      $attributes = array_merge($attributes, $input_attributes);
    }
    $input->setAttributes($attributes);
    $this->addControlElement($input);

    // The select element for the units
    $options = Form::getTimeUnitOptions();
    $select = new ElementSelect();
    $select->setAttribute('name', $param_names['units'])
           ->addSelectOptions($options, array_search($units, $options), true);
    $this->addControlElement($select);

    // The suffix
    if (isset($suffix))
    {
      $span = new ElementSpan();
      $span->setText($suffix);
      $this->addControlElement($span);
    }
  }

}
