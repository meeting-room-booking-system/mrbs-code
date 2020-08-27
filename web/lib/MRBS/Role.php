<?php
namespace MRBS;


class Role extends Table
{
  const TABLE_NAME = 'roles';

  protected static $unique_columns = array('name');


  public function __construct($name=null)
  {
    parent::__construct();
    if (isset($name))
    {
      $this->data = array('name' => $name);
    }
  }


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($name)
  {
    return self::getByColumn('name', $name);
  }


  public static function deleteById($id)
  {
    $sql = "DELETE FROM " . _tbl(self::TABLE_NAME) . "
                  WHERE id=:id
                  LIMIT 1";
    $sql_params = array(':id' => $id);
    db()->command($sql, $sql_params);
  }

}
