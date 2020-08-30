<?php
namespace MRBS;


class Rooms extends TableIterator
{

  private $area_id;


  public function __construct($area_id=null)
  {
    $this->area_id = $area_id;
    parent::__construct(__NAMESPACE__ . '\\Room');
  }


  // Returns an array of room names indexed by room id.
  // Only visible rooms are included.
  public function getNames($include_disabled=false)
  {
    $result = array();
    foreach ($this as $room)
    {
      if (($include_disabled || !$room->isDisabled()) && $room->isVisible())
      {
        $result[$room->id] = $room->room_name;
      }
    }
    return $result;
  }


  protected function getRes()
  {
    $class_name = $this->base_class;
    $sql_params = array();
    $sql = "SELECT *
              FROM " . _tbl($class_name::TABLE_NAME);
    if (isset($this->area_id))
    {
      $sql .= " WHERE area_id=:area_id";
      $sql_params[':area_id'] = $this->area_id;
    }
    $sql .= " ORDER BY sort_key";
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }
}
