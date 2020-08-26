<?php
namespace MRBS;


class Areas extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Area');
  }


  // Returns an array of area names indexed by area id.
  public function getNames($include_disabled=false)
  {
    $result = array();
    foreach ($this as $area)
    {
      if ($include_disabled || !$area->isDisabled())
      {
        $result[$area->id] = $area->area_name;
      }
    }
    return $result;
  }


  protected function getRes()
  {
    $class_name = $this->base_class;
    $sql = "SELECT *
              FROM " . _tbl($class_name::TABLE_NAME) . "
          ORDER BY sort_key";
    $this->res = db()->query($sql);
    $this->cursor = -1;
    $this->item = null;
  }
}
