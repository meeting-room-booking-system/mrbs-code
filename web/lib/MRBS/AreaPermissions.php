<?php
namespace MRBS;


class AreaPermissions extends TableIterator
{

  private $role;

  public function __construct(Role $role)
  {
    $this->role = $role;
    parent::__construct(__NAMESPACE__ . '\\AreaPermission');
  }

  protected function getRes()
  {
    $sql = "SELECT R.*, A.id, A.area_name
              FROM " . _tbl(AreaPermission::TABLE_NAME) . " R
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id
             WHERE role_id=:role_id
          ORDER BY A.sort_key";

    $sql_params = array(':role_id' => $this->role->id);
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }

}
