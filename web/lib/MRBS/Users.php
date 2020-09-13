<?php
namespace MRBS;


class Users extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\User');
  }


  protected function getRes($sort_column = null)
  {
    global $auth;

    $class_name = $this->base_class;
    $table_name = _tbl($class_name::TABLE_NAME);
    $sql_params = array(':auth_type' => $auth['type']);
    $sql = "SELECT *
              FROM $table_name
             WHERE auth_type=:auth_type
             ORDER BY name";
    $this->res = db()->query($sql, $sql_params);
    $this->cursor = -1;
    $this->item = null;
  }
}
