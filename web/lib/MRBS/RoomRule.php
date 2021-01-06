<?php
namespace MRBS;


class RoomRule extends LocationRule
{
  const TABLE_NAME = 'role_room';

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


  public static function getPermissionsByRoles(array $role_ids, $room_id)
  {
    return parent::getPermissions($role_ids, $room_id, 'room_id');
  }

}
