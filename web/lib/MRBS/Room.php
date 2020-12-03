<?php
namespace MRBS;


class Room extends Table
{
  const TABLE_NAME = 'room';

  protected static $unique_columns = array('room_name', 'area_id');

  private $is_visible;
  private $is_writable;
  private $is_book_admin;
  private $is_able;


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
    return $this->isAble(RoomPermission::READ);
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


  private function isAble($operation)
  {
    if (!isset($this->is_able ) || !isset($this->is_able[$operation]))
    {
      // Admins can do anything
      if (is_admin())
      {
        $this->is_able[$operation] = true;
      }
      else
      {
        $rules = $this->getRules();
        $this->is_able[$operation] = AreaRoomPermission::can($rules, $operation);
      }
    }

    return $this->is_able[$operation];
  }


  private function getRules($for_groups = false)
  {
    $user = session()->getCurrentUser();

    // If there's no logged in user, return the default rules
    if (!isset($user))
    {
      return array(RoomPermission::getDefaultPermission());
    }

    // Otherwise, get the roles for this user
    if ($for_groups)
    {
      $roles = Group::getRoles($user->groups);
      if (empty($roles))
      {
        return array(RoomPermission::getDefaultPermission());
      }
    }
    else
    {
      // Get the individual roles for this user
      $roles = $user->roles;
      if (empty($roles))
      {
        return $this->getRules(true);
      }
    }

    // Now we've got the roles, get the rules that apply
    // First see if there are any rules for this room
    $rules = $this->getPermissions($roles);
    // If there are none, check to see if there any rules for the area
    if (empty($rules))
    {
      $rules = Area::getById($this->area_id)->getPermissions($roles);
      if (empty($rules))
      {
        // If there are no rules for the area and we're already checking
        // the rules for the user's groups, then there's nothing more that
        // we can do, so return the default rules.
        if ($for_groups)
        {
          return array(RoomPermission::getDefaultPermission());
        }
        // Otherwise, see if there are some rules for the user's groups.
        return $this->getRules(true);
      }
    }

    return $rules;
  }


  private function getPermissions(array $role_ids)
  {
    return RoomPermission::getPermissionsByRoles($role_ids, $this->id);
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
