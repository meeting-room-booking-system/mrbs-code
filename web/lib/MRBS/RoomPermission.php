<?php
namespace MRBS;


class RoomPermission extends AreaRoomPermission
{
  const TABLE_NAME = 'roles_rooms';

  protected static $unique_columns = array('role_id', 'room_id');


  public function __construct($role_id=null, $room_id=null)
  {
    parent::__construct();
    $this->role_id = $role_id;
    $this->room_id = $room_id;

    // Default values
    $this->permission = self::$permission_default;
    $this->state = self::$state_default;
  }
  
}
