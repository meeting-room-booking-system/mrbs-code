<?php
namespace MRBS;


abstract class AreaRoomPermission extends Table
{
  // Possible permissions
  const READ  = 'r';  // Can view the area
  const WRITE = 'w';  // Can make a booking for oneself in the area
  const ALL   = 'a';  // Can make a booking for others in the area

  // Possible permission states
  const NEITHER = 'n';
  const GRANTED = 'g';
  const DENIED  = 'd';

  public static $permission_default = self::WRITE;
  public static $state_default = self::GRANTED;

  private static $permissions = array(self::READ, self::WRITE, self::ALL);  // Must be in order

  public static function getPermissionOptions()
  {
    return array(
        AreaPermission::READ  => get_vocab('permission_read'),
        AreaPermission::WRITE => get_vocab('permission_write'),
        AreaPermission::ALL   => get_vocab('permission_all')
      );
  }


  public static function getStateOptions()
  {
    return array(
        AreaPermission::NEITHER => get_vocab('state_neither'),
        AreaPermission::GRANTED => get_vocab('state_granted'),
        AreaPermission::DENIED  => get_vocab('state_denied')
      );
  }


  public static function can(array $permissions, $operation)
  {
    switch ($operation)
    {
      case self::READ:
        return self::canRead($permissions);
        break;
      case self::WRITE:
        return self::canWrite($permissions);
        break;
      case self::ALL:
        return self::canAll($permissions);
        break;
      default:
        throw new \Exception("Unknown operation '$operation'");
        break;
    }
  }


  // Check whether the given permissions allow reading
  private static function canRead(array $permissions)
  {
    $highest_granted = self::getDefaultPermission();
    $lowest_denied = null;

    foreach ($permissions as $permission)
    {
      switch ($permission->state)
      {
        case self::GRANTED:
          $highest_granted = (isset($highest_granted)) ?
            self::max($highest_granted, $permission->permission) :
            $permission->permission;
          break;
        case self::DENIED:
          $lowest_denied = (isset($lowest_denied)) ?
            self::max($lowest_denied, $permission->permission) :
            $permission->permission;
          break;
        default:
          break;
      }
    }

    if (isset($lowest_denied) && ($lowest_denied === self::READ))
    {
      return false;
    }

    return (isset($highest_granted));
  }


  // Check whether the given permissions allow writing
  private static function canWrite(array $permissions)
  {
    $highest_granted = self::getDefaultPermission();
    $lowest_denied = null;

    foreach ($permissions as $permission)
    {
      switch ($permission->state)
      {
        case self::GRANTED:
          $highest_granted = (isset($highest_granted)) ?
            self::max($highest_granted, $permission->permission) :
            $permission->permission;
          break;
        case self::DENIED:
          $lowest_denied = (isset($lowest_denied)) ?
            self::max($lowest_denied, $permission->permission) :
            $permission->permission;
          break;
        default:
          break;
      }
    }

    if (isset($lowest_denied) && ($lowest_denied !== self::ALL))
    {
      return false;
    }

    return (isset($highest_granted) && ($highest_granted !== self::READ));
  }


  private static function canAll(array $permissions)
  {
    $highest_granted = self::getDefaultPermission();

    foreach ($permissions as $permission)
    {
      switch ($permission->state)
      {
        case self::GRANTED:
          $highest_granted = (isset($highest_granted)) ?
                              self::max($highest_granted, $permission->permission) :
                              $permission->permission;
          break;
        case self::DENIED:
          // If any permissions are denied then immediately return false
          return false;
          break;
        default:
          break;
      }
    }

    return (isset($highest_granted) && ($highest_granted === self::ALL));
  }


  // TODO: replace with default roles
  private static function getDefaultPermission()
  {
    $mrbs_user = session()->getCurrentUser();

    if (!isset($mrbs_user))
    {
      return self::READ;
    }

    switch ($mrbs_user->level)
    {
      case 0:
        return self::READ;
        break;
      case 1:
        return self::WRITE;
        break;
      case 2:
        return self::ALL;
        break;
      default:
        return null;
        break;
    }

  }


  protected static function getPermissions(array $role_ids, $location_id, $location_column)
  {
    if (empty($role_ids))
    {
      return array();
    }

    $sql_params = array(":location" => $location_id);
    $ins = array();

    foreach ($role_ids as $i => $role_id)
    {
      $named_parameter = ":role_id$i";
      $ins[] = $named_parameter;
      $sql_params[$named_parameter] = $role_id;
    }

    $sql = "SELECT *
              FROM " . _tbl(static::TABLE_NAME) . "
             WHERE $location_column=:location
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


  private static function max($a, $b)
  {
    // Check we've got valid parameters
    if (!in_array($a, self::$permissions) || !in_array($b, self::$permissions))
    {
      throw new \Exception("Invalid parameters");
    }
    // Simple case
    if ($a == $b)
    {
      return $a;
    }
    // Otherwise work out which is higher
    $max_key = max(array_search($a, self::$permissions),
                   array_search($b, self::$permissions));
    return self::$permissions[$max_key];
  }


  private static function min($a, $b)
  {
    // Check we've got valid parameters
    if (!in_array($a, self::$permissions) || !in_array($b, self::$permissions))
    {
      throw new \Exception("Invalid parameters");
    }
    // Simple case
    if ($a == $b)
    {
      return $a;
    }
    // Otherwise work out which is lower
    $min_key = min(array_search($a, self::$permissions),
                   array_search($b, self::$permissions));
    return self::$permissions[$min_key];
  }

}
