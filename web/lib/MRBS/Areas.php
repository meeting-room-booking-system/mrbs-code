<?php
namespace MRBS;


class Areas extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Area');
  }


  // Returns an array of area names indexed by area id.  Only visible rooms are included.
  // Useful for passing to the addSelectOptions() method.
  public function getNames($include_disabled=false)
  {
    $result = array();
    foreach ($this as $area)
    {
      if (($include_disabled || !$area->isDisabled()) && $area->isVisible())
      {
        $result[$area->id] = $area->area_name;
      }
    }
    return $result;
  }


  protected function getRes($sort_column = null) : void
  {
    parent::getRes('sort_key');
  }
}
