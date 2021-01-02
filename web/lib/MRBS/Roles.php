<?php
namespace MRBS;


use MRBS\Form\ElementFieldset;
use MRBS\Form\FieldInputCheckbox;

class Roles extends TableIterator
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
  public function getFieldset(array $selected, $disabled=false)
  {
    if ($this->count() == 0)
    {
      return null;
    }

    $fieldset = new ElementFieldset();
    $fieldset->addLegend(get_vocab('roles'));

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
            ->setChecked(in_array($role->id, $selected));
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
