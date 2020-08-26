<?php
namespace MRBS;


class Area
{
  private $data;

  const TABLE_NAME = 'area';


  public function __construct()
  {
    $this->data = array();
  }


  public function __get($name)
  {
    return (isset($this->data) && isset($this->data[$name])) ? $this->data[$name] : null;
  }


  public function __set($name, $value)
  {
    $this->data[$name] = $value;
  }


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($name)
  {
    return self::getByColumn('area_name', $name);
  }


  public static function exists($name)
  {
    $sql = "SELECT * FROM " . _tbl(self::TABLE_NAME) . "
                    WHERE name=:name
                    LIMIT 1";
    $sql_params = array(':name' => $name);
    $res = db()->query($sql, $sql_params);
    return ($res->count() > 0);
  }


  public static function deleteById($id)
  {
    $sql = "DELETE FROM " . _tbl(self::TABLE_NAME) . "
                  WHERE id=:id
                  LIMIT 1";
    $sql_params = array(':id' => $id);
    db()->command($sql, $sql_params);
  }


  public function save()
  {
    throw new \Exception("TODO");
  }


  public function load(array $row)
  {
    foreach ($row as $key => $value)
    {
      $this->data[$key] = $value;
    }
  }


  public function isDisabled()
  {
    return (bool) $this->data['disabled'];
  }


  private static function getByColumn($column, $value)
  {
    $sql = "SELECT * FROM " . _tbl(self::TABLE_NAME) . "
                      WHERE $column=:value
                      LIMIT 1";
    $sql_params = array(':value' => $value);
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
}
