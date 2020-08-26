<?php
namespace MRBS;


class AreaPermission
{
  // Possible permissions
  const READ  = 'r';  // Can view the area
  const WRITE = 'w';  // Can make a booking for oneself in the area
  const ALL   = 'a';  // Can make a booking for others in the area

  // Possible permission states
  const NEITHER = 'n';
  const GRANTED = 'g';
  const DENIED  = 'd';

  public $area_id;
  public $role_id;
  public $permission;
  public $state;

  public static $permission_default = self::WRITE;
  public static $state_default = self::GRANTED;


  public function __construct($area_id, $role_id)
  {
    $this->area_id = $area_id;
    $this->role_id = $role_id;
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
