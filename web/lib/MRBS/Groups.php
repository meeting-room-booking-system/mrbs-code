<?php
namespace MRBS;


class Groups extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Group');
  }

  // Returns an array of group names indexed by id.
  public function getNames()
  {
    $result = array();
    foreach ($this as $group)
    {
      $result[$group->id] = $group->name;
    }
    return $result;
  }


  protected function getRes($sort_column = null)
  {
    parent::getRes('name');
  }
}
