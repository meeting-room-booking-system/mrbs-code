<?php
namespace MRBS;


class AreaRules extends TableIterator
{

  private $role;

  public function __construct(Role $role)
  {
    $this->role = $role;
    parent::__construct(__NAMESPACE__ . '\\AreaRule');
  }

  protected function getRes($sort_column = null)
  {
    $sql = "SELECT L.*, A.area_name
              FROM " . _tbl(AreaRule::TABLE_NAME) . " L
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON L.area_id=A.id
             WHERE L.role_id=:role_id
          ORDER BY A.sort_key";

    $sql_params = array(':role_id' => $this->role->id);
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }

}
