<?php
namespace MRBS;


class Rooms extends TableIterator
{

  private $area_id;


  // Gets the rooms for an area.  If no area_id is specified then it gets all
  // the rooms in the system.
  public function __construct($area_id=null)
  {
    $this->area_id = $area_id;
    parent::__construct(__NAMESPACE__ . '\\Room');
  }


  // Returns the number of visible rooms
  public function countVisible($include_disabled=false)
  {
    $result = 0;

    foreach ($this as $room)
    {
      if (($include_disabled || !$room->isDisabled()) && $room->isVisible())
      {
        $result++;
      }
    }

    return $result;
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
    $sql = "SELECT R.*, A.area_name";
    // In early versions of MRBS the disabled field didn't exist and this method
    // may be called before the database can be upgraded
    if (db()->field_exists(_tbl(Area::TABLE_NAME), 'disabled'))
    {
      $sql .= ", A.disabled as area_disabled";
    }
    $sql .= " FROM $table_name R
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id ";
    if (isset($this->area_id))
    {
      $sql .= " WHERE R.area_id=:area_id";
      $sql_params[':area_id'] = $this->area_id;
    }
    // In early versions of MRBS the sort_key field didn't exist and this method
    // may be called before the database can be upgraded.  Note that the sort_key
    // was introduced into the area table after it was introduced into the room
    // table, so we need to check for the existence of the later one (area.sort_key).
    if (db()->field_exists(_tbl(Area::TABLE_NAME), 'sort_key'))
    {
      $sql .= " ORDER BY A.sort_key, R.sort_key";
    }
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }
}
