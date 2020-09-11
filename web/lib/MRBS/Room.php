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

  protected static $unique_columns = array('room_name', 'area_id');

  private $is_visible;


  public function __construct($room_name=null, $area_id=null)
  {
    parent::__construct();
    $this->room_name = $room_name;
    $this->sort_key = $room_name;
    $this->area_id = $area_id;
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
  // its area has been disabled.
  public function isDisabled()
  {
    return ($this->disabled || $this->area_disabled);
  }


  public function isVisible()
  {
    if (!isset($this->is_visible))
    {
      // Admins can see all rooms
      if (is_admin())
      {
        $this->is_visible = true;
      }
      else
      {
        $user = session()->getCurrentUser();
        if (!isset($user))
        {
          // TODO: need to have a guest role or something like that
          $this->is_visible = true;
        }
        else
        {
          $highest_granted = null;
          $lowest_denied = null;
          $room_permissions = $this->getPermissions($user->roles);
          foreach ($room_permissions as $room_permission)
          {
            switch ($room_permission->state)
            {
              case RoomPermission::GRANTED:
                $highest_granted = (isset($highest_granted)) ?
                                    RoomPermission::max($highest_granted, $room_permission->permission) :
                                    $room_permission->permission;
                break;
              case RoomPermission::DENIED:
                $lowest_denied = (isset($lowest_denied)) ?
                                  RoomPermission::max($lowest_denied, $room_permission->permission) :
                                  $room_permission->permission;
                break;
              default:
                break;
            }
          }
          if (isset($lowest_denied) && ($lowest_denied == RoomPermission::READ))
          {
            $this->is_visible = false;
          }
          else
          {
            $this->is_visible = true;
          }
        }
      }
    }

    return $this->is_visible;
  }


  private function getPermissions(array $role_ids)
  {
    if (empty($role_ids))
    {
      return array();
    }

    $sql_params = array(':room_id' => $this->id);
    $ins = array();

    foreach ($role_ids as $i => $role_id)
    {
      $named_parameter = ":role_id$i";
      $ins[] = $named_parameter;
      $sql_params[$named_parameter] = $role_id;
    }

    $sql = "SELECT *
              FROM " . _tbl(RoomPermission::TABLE_NAME) . "
             WHERE room_id=:room_id
               AND role_id IN (" . implode(', ', $ins) . ")";

    $res = db()->query($sql, $sql_params);

    $result = array();

    while (false !== ($row = $res->next_row_keyed()))
    {
      $permission = new RoomPermission();
      $permission->load($row);
      $result[] = $permission;
    }

    return $result;
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
