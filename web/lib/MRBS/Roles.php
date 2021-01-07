<?php
namespace MRBS;


use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputCheckbox;
use MRBS\Form\ElementSpan;
use MRBS\Form\FieldInputCheckbox;
use MRBS\Form\FieldSpan;

class Roles extends Attributes
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Role');
  }

  // Returns an array of role names indexed by id.
  public function getNames()
  {
    $result = array();
    foreach ($this as $role)
    {
      $result[$role->id] = $role->name;
    }
    return $result;
  }


  // Gets a form field for a standard form for selecting roles
  public function getFieldset(array $selected_own, $disabled=false, $selected_groups=null)
  {
    if ($this->count() == 0)
    {
      return null;
    }

    $fieldset = new ElementFieldset();
    $fieldset->setAttribute('id', 'fieldset_roles')
             ->addLegend(get_vocab('roles'));

    // Add a "header" row
    $field = new FieldSpan();
    $field->setControlText(get_vocab('user'));
    $span = new ElementSpan();
    $span->setText(get_vocab('groups'));
    $field->addElement($span);
    $fieldset->addElement($field);

    // Now add the "body"
    $this->rewind();

    while ($this->valid())
    {
      $role = $this->current();
      $field = new FieldInputCheckbox();
      $field->setLabel($role->name)
            ->setControlAttributes(array('id' => 'roles' . $this->cursor,
                                         'name' => 'roles[]',
                                         'value' => $role->id,
                                         'disabled' => $disabled))
            ->setChecked(in_array($role->id, $selected_own));
      if (isset($selected_groups))
      {
        $checkbox = new ElementInputCheckbox();
        $checkbox->setChecked(in_array($role->id, $selected_groups))
                 ->setAttribute('disabled');
        $field->addElement($checkbox);
      }
      $fieldset->addElement($field);
      $this->next();
    }

    return $fieldset;
  }


  protected function getRes($sort_column = null)
  {
    parent::getRes('name');
  }
}
