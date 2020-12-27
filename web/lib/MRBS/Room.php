<?php
namespace MRBS;


class Room extends Location
{
  const TABLE_NAME = 'room';

  protected static $unique_columns = array('room_name', 'area_id');


  public function __construct($room_name=null, $area_id=null)
  {
    parent::__construct();
    $this->room_name = $room_name;
    $this->sort_key = $room_name;
    $this->area_id = $area_id;
    $this->disabled = false;
    $this->area_disabled = false;
  }


  public static function getByName($name)
  {
    return self::getByColumn('room_name', $name);
  }


  // Checks if the room is disabled.  A room is disabled if either it or
  // its area has been disabled.
  public function isDisabled()
  {
    return ($this->disabled || $this->area_disabled);
  }


  public function isWritable()
  {
    return $this->isAble(RoomPermission::WRITE);
  }


  public function isBookAdmin()
  {
    return $this->isAble(RoomPermission::ALL);
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead($row)
  {
    if (isset($row['invalid_types']))
    {
      $row['invalid_types'] = json_decode($row['invalid_types']);
    }

    return $row;
  }


  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite($row)
  {
    if (isset($row['invalid_types']))
    {
      $row['invalid_types'] = json_encode($row['invalid_types']);
    }

    return $row;
  }


  public function getPermissions(array $role_ids)
  {
    return RoomPermission::getPermissionsByRoles($role_ids, $this->id);
  }


  // For efficiency we get some information about the area at the same time.
  protected static function getByColumn($column, $value)
  {
    $sql = "SELECT R.*, A.area_name";

    // The disabled column didn't always exist and it's possible that this
    // method is being called during an upgrade before the column exists
    $area_columns = new Columns(_tbl(Area::TABLE_NAME));
    if (null !== $area_columns->getColumnByName('disabled'))
    {
      $sql .= ", A.disabled as area_disabled";
    }

    $sql .= " FROM " . _tbl(static::TABLE_NAME) . " R
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id
             WHERE R." . db()->quote($column) . "=:value
             LIMIT 1";

    $sql_params = array(':value' => $value);
    $res = db()->query($sql, $sql_params);

    if ($res->count() == 0)
    {
      $result = null;
    }
    else
    {
      $class = get_called_class();
      $result = new $class();
      $result->load($res->next_row_keyed());
    }

    return $result;
  }

}
