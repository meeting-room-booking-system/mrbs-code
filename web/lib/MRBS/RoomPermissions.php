<?php
namespace MRBS;


class RoomPermissions extends TableIterator
{

  private $role;
  private $area_id;

  public function __construct(Role $role, $area_id=null)
  {
    $this->role = $role;
    $this->area_id = $area_id;
    parent::__construct(__NAMESPACE__ . '\\RoomPermission');
  }

  protected function getRes()
  {
    $sql_params = array(':role_id' => $this->role->id);

    $sql = "SELECT P.*, R.room_name
              FROM " . _tbl(RoomPermission::TABLE_NAME) . " P
         LEFT JOIN " . _tbl(Room::TABLE_NAME) . " R
                ON P.room_id=R.id
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id
             WHERE P.role_id=:role_id";
    if (isset($this->area_id))
    {
      $sql .= " AND R.area_id=:area_id
           ORDER BY A.sort_key, R.sort_key";
      $sql_params['area_id'] = $this->area_id;
    }
    else
    {
      $sql .= " ORDER BY R.sort_key";
    }

    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }

}
