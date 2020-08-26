<?php
namespace MRBS;


class AreaPermissions implements \Countable, \Iterator
{
  const TABLE_NAME = 'roles_areas';

  private $data;
  private $cursor;

  public function __construct()
  {
    $this->data = array();
    $this->cursor = 0;
  }

  public function current()
  {
    $current =  $this->data[$this->cursor];
    $permission = new AreaPermission($current['area_id'], $current['role_id']);
    $permission->permission = $current['permission'];
    $permission->state = $current['state'];

    return $permission;
  }

  public function next()
  {
    $this->cursor++;
  }

  public function key()
  {
    return $this->cursor;
  }

  public function valid()
  {
    return ($this->cursor < $this->count());
  }

  public function rewind()
  {
    $this->cursor = 0;
  }

  public function count()
  {
    return count($this->data);
  }

  public function getByRole(Role $role)
  {
    $sql = "SELECT *
              FROM " . \MRBS\_tbl(self::TABLE_NAME) . "
             WHERE role_id=:role_id";

    $sql_params = array(':role_id' => $role->id);
    $res = \MRBS\db()->query($sql, $sql_params);
    if ($res->count() > 0)
    {
      $this->data = $res->all_rows_keyed();
    }
  }
}
