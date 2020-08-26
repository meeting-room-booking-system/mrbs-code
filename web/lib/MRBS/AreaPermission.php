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

  public function __construct($area_id, $role_id)
  {
    $this->area_id = $area_id;
    $this->role_id = $role_id;
    $this->permission = null;
    $this->state = null;
  }

}
