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


  public function isDisabled()
  {
    return (bool) $this->disabled;
  }


  public function isVisible()
  {
    return is_visible($this->id);
  }
}
