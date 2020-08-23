<?php
namespace MRBS;


class Role
{
  private $data;

  const TABLE_NAME = 'roles';


  public function __construct($name=null)
  {
    if (isset($name))
    {
      $this->data = array('name' => $name);
    }
  }


  public function __get($name)
  {
    return (isset($this->data) && isset($this->data[$name])) ? $this->data[$name] : null;
  }


  public function __set($name, $value)
  {
    $this->data[$name] = $value;
  }


  public function getByName($name)
  {
    $sql = "SELECT * FROM " . \MRBS\_tbl(self::TABLE_NAME) . "
                    WHERE name=:name
                    LIMIT 1";
    $sql_params = array(':name' => $name);
    $res = \MRBS\db()->query($sql, $sql_params);
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


  public static function exists($name)
  {
    $sql = "SELECT * FROM " . \MRBS\_tbl(self::TABLE_NAME) . "
                    WHERE name=:name
                    LIMIT 1";
    $sql_params = array(':name' => $name);
    $res = \MRBS\db()->query($sql, $sql_params);
    return ($res->count() > 0);
  }


  public static function deleteById($id)
  {
    $sql = "DELETE FROM " . \MRBS\_tbl(self::TABLE_NAME) . "
                  WHERE id=:id
                  LIMIT 1";
    $sql_params = array(':id' => $id);
    \MRBS\db()->command($sql, $sql_params);
  }


  public function save()
  {
    \MRBS\db()->mutex_lock(\MRBS\_tbl(self::TABLE_NAME));
    // As there's only one column at the moment there's no point in doing an update
    // if the name already exists.
    if (!self::exists($this->data['name']))
    {
      $sql = "INSERT INTO " . \MRBS\_tbl(self::TABLE_NAME) . " (name)
                   VALUES (:name)";
      $sql_params = array(':name' => $this->data['name']);
      \MRBS\db()->command($sql, $sql_params);
    }
    \MRBS\db()->mutex_unlock(\MRBS\_tbl(self::TABLE_NAME));
  }


  public function load(array $row)
  {
    foreach ($row as $key => $value)
    {
      $this->data[$key] = $value;
    }
  }
}
