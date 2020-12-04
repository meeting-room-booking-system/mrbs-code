<?php
namespace MRBS;


class Area extends Table
{
  const TABLE_NAME = 'area';

  protected static $unique_columns = array('area_name');

  private $is_able;


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
    return $this->isAble(AreaRoomPermission::READ);
  }


  private function isAble($operation)
  {
    if (!isset($this->is_able ) || !isset($this->is_able[$operation]))
    {
      // Admins can do anything
      if (is_admin())
      {
        $this->is_able[$operation] = true;
      }
      else
      {
        $user = session()->getCurrentUser();
        if (isset($user))
        {
          $rules = $user->getRules($this);
        }
        else
        {
          // If there's no logged in user, return the default rules
          $rules = array(AreaPermission::getDefaultPermission());
        }
        $this->is_able[$operation] = AreaRoomPermission::can($rules, $operation);
      }
    }

    return $this->is_able[$operation];
  }


  public function getPermissions(array $role_ids)
  {
    return AreaPermission::getPermissionsByRoles($role_ids, $this->id);
  }

}
