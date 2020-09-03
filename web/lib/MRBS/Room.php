<?php
namespace MRBS;


class Room extends Table
{
  const TABLE_NAME = 'room';

  public $id;
  public $room_name;
  public $sort_key;
  public $area_id;
  public $description;
  public $capacity;
  public $room_admin_email;
  public $disabled;

  protected static $unique_columns = array('room_name');


  public function __construct($room_name=null)
  {
    parent::__construct();
    $this->room_name = $room_name;
    $this->sort_key = $room_name;
    $this->disabled = false;
  }


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($room_name)
  {
    return self::getByColumn('room_name', $room_name);
  }


  // Checks if the room is disabled.  A room is disabled if either it or
  // its area has ben disabled.
  public function isDisabled()
  {
    return ($this->disabled || $this->area_disabled);
  }


  public function isVisible()
  {
    return is_visible($this->id);
  }


  // For efficiency we get some information about the area at the same time.
  protected static function getByColumn($column, $value)
  {
    $sql = "SELECT R.*, A.area_name, A.disabled as area_disabled
              FROM " . _tbl(static::TABLE_NAME) . " R
         LEFT JOIN " . _tbl(Area::TABLE_NAME) . " A
                ON R.area_id=A.id
             WHERE R.$column=:value
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
