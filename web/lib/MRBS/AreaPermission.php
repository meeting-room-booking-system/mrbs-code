<?php
namespace MRBS;


class AreaPermission extends Table
{
  // Possible permissions
  const READ  = 'r';  // Can view the area
  const WRITE = 'w';  // Can make a booking for oneself in the area
  const ALL   = 'a';  // Can make a booking for others in the area

  // Possible permission states
  const NEITHER = 'n';
  const GRANTED = 'g';
  const DENIED  = 'd';

  const TABLE_NAME = 'roles_areas';

  public static $permission_default = self::WRITE;
  public static $state_default = self::GRANTED;

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


  public static function getPermissionOptions()
  {
    return array(
        AreaPermission::READ  => get_vocab('permission_read'),
        AreaPermission::WRITE => get_vocab('permission_write'),
        AreaPermission::ALL   => get_vocab('permission_all')
      );
  }


  public static function getStateOptions()
  {
    return array(
        AreaPermission::NEITHER => get_vocab('state_neither'),
        AreaPermission::GRANTED => get_vocab('state_granted'),
        AreaPermission::DENIED  => get_vocab('state_denied')
      );
  }
}
