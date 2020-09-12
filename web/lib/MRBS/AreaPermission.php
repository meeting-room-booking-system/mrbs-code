<?php
namespace MRBS;


class AreaPermission extends AreaRoomPermission
{
  const TABLE_NAME = 'role_area';

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


  public static function getByRoleArea($role_id, $area_id)
  {
    $sql = "SELECT P.*, A.area_name
              FROM " . _tbl(self::TABLE_NAME) . " P
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON P.area_id=A.id
             WHERE P.role_id=:role_id
               AND P.area_id=:area_id
             LIMIT 1";

    $sql_params = array(
        ':role_id' => $role_id,
        ':area_id' => $area_id
      );

    $res = db()->query($sql, $sql_params);

    if ($res->count() == 0)
    {
      $result = null;
    }
    else
    {
      $result = new self();
      $result->load($res->next_row_keyed());
    }

    return $result;
  }


  public static function getPermissionsByRoles(array $role_ids, $area_id)
  {
    return parent::getPermissions($role_ids, $area_id, 'area_id');
  }

}
