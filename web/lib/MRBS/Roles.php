<?php
namespace MRBS;


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


  protected function getRes($sort_column = null)
  {
    parent::getRes('name');
  }
}
