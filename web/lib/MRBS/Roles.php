<?php
namespace MRBS;


use MRBS\Form\FieldSelect;

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
  public function getFormField(array $selected, $disabled=false)
  {
    if ($this->count() == 0)
    {
      return null;
    }

    $field = new FieldSelect();
    $field->setLabel(get_vocab('roles'))
          ->setControlAttributes(array('name' => 'roles[]',
                                       'disabled' => $disabled,
                                       'multiple' => true))
          ->addSelectOptions($this->getNames(), $selected, true);

    return $field;
  }


  protected function getRes($sort_column = null)
  {
    parent::getRes('name');
  }
}
