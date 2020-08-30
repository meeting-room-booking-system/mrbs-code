<?php
namespace MRBS;


class AreaPermission extends AreaRoomPermission
{
  const TABLE_NAME = 'roles_areas';

  protected static $unique_columns = array('role_id', 'area_id');


  public function __construct($role_id=null, $area_id=null)
  {
    parent::__construct();
    $this->role_id = $role_id;
    $this->area_id = $area_id;

    // Default values
    $this->permission = self::$permission_default;
    $this->state = self::$state_default;
  }
  
}
