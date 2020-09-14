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


  // Returns an array of room names indexed by room id. Only visible rooms are included.
  // Useful for passing to the addSelectOptions() method.
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


  // Returns an array of room ids
  public function getIds($include_disabled=false)
  {
    return array_keys($this->getNames($include_disabled));
  }


  // Returns a two-dimensional array of room names indexed first by area name,
  // then by room id.  Only visible rooms are included.
  // Useful for passing to the addSelectOptions() method to get <select> options
  // with option groups.
  public function getGroupedNames($include_disabled=false)
  {
    $result = array();
    foreach ($this as $room)
    {
      if (($include_disabled || !$room->isDisabled()) && $room->isVisible())
      {
        $result[$room->area_name][$room->id] = $room->room_name;
      }
    }
    return $result;
  }


  // For efficiency we get some information about the area at the same time.
  protected function getRes($sort_column = null)
  {
    $class_name = $this->base_class;
    $table_name = _tbl($class_name::TABLE_NAME);
    $sql_params = array();
    $sql = "SELECT R.*, A.area_name, A.disabled as area_disabled
              FROM $table_name R
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id ";
    if (isset($this->area_id))
    {
      $sql .= " WHERE R.area_id=:area_id";
      $sql_params[':area_id'] = $this->area_id;
    }
    // In early versions of MRBS the sort_key field didn't exist and this method
    // may be called before the database can be upgraded.
    if (db()->field_exists($table_name, 'sort_key'))
    {
      $sql .= " ORDER BY A.sort_key, R.sort_key";
    }
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }
}
