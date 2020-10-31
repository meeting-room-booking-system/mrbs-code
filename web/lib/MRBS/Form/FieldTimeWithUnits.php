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
  public function __construct(array $param_names, $enabled, $seconds)
  {
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
    $input->setAttributes(array('name'  => $param_names['quantity'],
                                'value' => $duration));
    $this->addControlElement($input);

    // The select element for the units
    $options = Form::get_time_unit_options();
    $select = new ElementSelect();
    $select->setAttribute('name', $param_names['units'])
           ->addSelectOptions($options, array_search($units, $options), true);
    $this->addControlElement($select);
  }

}
