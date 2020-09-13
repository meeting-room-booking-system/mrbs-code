<?php
namespace MRBS;


class Area extends Table
{
  const TABLE_NAME = 'area';

  protected static $unique_columns = array('area_name');

  private $is_visible;


  public function __construct($area_name=null)
  {
    parent::__construct();
    $this->area_name = $area_name;
  }


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($area_name)
  {
    return self::getByColumn('area_name', $area_name);
  }


  // Returns an array of room names for the area indexed by area id.
  public function getRoomNames($include_disabled=false)
  {
    $rooms = new Rooms($this->id);
    return $rooms->getNames($include_disabled);
  }


  public function isDisabled()
  {
    return (bool) $this->disabled;
  }


  public function isVisible()
  {
    if (!isset($this->is_visible))
    {
      // Admins can see everything
      if (is_admin())
      {
        $this->is_visible = true;
      }
      else
      {
        $user = session()->getCurrentUser();
        // TODO: need to have default roles
        $roles = (isset($user)) ? $user->roles : array();
        $permissions = $this->getPermissions($roles);
        $this->is_visible = AreaRoomPermission::can($permissions, AreaPermission::READ);
      }
    }

    return $this->is_visible;
  }


  public function getPermissions(array $role_ids)
  {
    return AreaPermission::getPermissionsByRoles($role_ids, $this->id);
  }

}
