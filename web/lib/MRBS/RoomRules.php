<?php
namespace MRBS;


class RoomRules extends TableIterator
{

  private $role;
  private $area_id;

  public function __construct(Role $role, $area_id=null)
  {
    $this->role = $role;
    $this->area_id = $area_id;
    parent::__construct(__NAMESPACE__ . '\\RoomRule');
  }

  protected function getRes($sort_column = null)
  {
    $sql_params = array(':role_id' => $this->role->id);

    $sql = "SELECT L.*, R.room_name
              FROM " . _tbl(RoomRule::TABLE_NAME) . " L
         LEFT JOIN " . _tbl(Room::TABLE_NAME) . " R
                ON L.room_id=R.id
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id
             WHERE L.role_id=:role_id";
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
