<?php
namespace MRBS;


class Room extends Table
{
  const TABLE_NAME = 'room';

  protected static $unique_columns = array('room_name');


  public function __construct($room_name=null)
  {
    parent::__construct();
    $this->room_name = $room_name;
  }


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($room_name)
  {
    return self::getByColumn('room_name', $room_name);
  }


  // Checks if the room is disabled.  A room is disabled if either it or
  // its area has ben disabled.
  public function isDisabled()
  {
    if ($this->disabled)
    {
      return true;
    }
    $area = Area::getById($this->area_id);
    return $area->isDisabled();
  }


  public function isVisible()
  {
    return is_visible($this->id);
  }
}
