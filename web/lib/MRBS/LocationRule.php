<?php
namespace MRBS;


abstract class LocationRule extends Table
{
  // Possible permissions
  const READ  = 'r';  // Can view the area
  const WRITE = 'w';  // Can make a booking for oneself in the area
  const ALL   = 'a';  // Can make a booking for others in the area

  // Possible permission states
  const GRANTED = 'g';
  const DENIED  = 'd';

  public static $permission_default = self::WRITE;
  public static $state_default = self::GRANTED;

  private static $permissions = array(self::READ, self::WRITE, self::ALL);  // Must be in order


  // Returns an array of permissions in ascending order, with key the permission
  // and value the descriptive text.
  public static function getPermissionOptions()
  {
    return array(
        self::READ  => get_vocab('permission_read'),
        self::WRITE => get_vocab('permission_write'),
        self::ALL   => get_vocab('permission_all')
      );
  }


  public static function getStateOptions()
  {
    return array(
        self::GRANTED => get_vocab('state_granted'),
        self::DENIED  => get_vocab('state_denied')
      );
  }


  // Checks whether $rules allow $operation (READ | WRITE | ALL)
  public static function can(array $rules, $operation)
  {
    switch ($operation)
    {
      case self::READ:
        return self::canRead($rules);
        break;
      case self::WRITE:
        return self::canWrite($rules);
        break;
      case self::ALL:
        return self::canAll($rules);
        break;
      default:
        throw new \Exception("Unknown operation '$operation'");
        break;
    }
  }


  // Check whether the given rules allow reading
  private static function canRead(array $rules)
  {
    foreach ($rules as $rule)
    {
      switch ($rule->state)
      {
        case self::GRANTED:
          $highest_granted = (isset($highest_granted)) ?
            self::max($highest_granted, $rule->permission) :
            $rule->permission;
          break;
        case self::DENIED:
          $lowest_denied = (isset($lowest_denied)) ?
            self::max($lowest_denied, $rule->permission) :
            $rule->permission;
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


  // Check whether the given rules allow writing
  private static function canWrite(array $rules)
  {
    foreach ($rules as $rule)
    {
      switch ($rule->state)
      {
        case self::GRANTED:
          $highest_granted = (isset($highest_granted)) ?
            self::max($highest_granted, $rule->permission) :
            $rule->permission;
          break;
        case self::DENIED:
          $lowest_denied = (isset($lowest_denied)) ?
            self::max($lowest_denied, $rule->permission) :
            $rule->permission;
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


  // Checks whether the given rules allow all access
  private static function canAll(array $rules)
  {
    foreach ($rules as $rule)
    {
      switch ($rule->state)
      {
        case self::GRANTED:
          $highest_granted = (isset($highest_granted)) ?
                              self::max($highest_granted, $rule->permission) :
                              $rule->permission;
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


  protected static function getRules(array $role_ids, $location_id, $location_column)
  {
    $result = array();

    if (!empty($role_ids))
    {
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

      while (false !== ($row = $res->next_row_keyed()))
      {
        $rule = new static();
        $rule->load($row);
        $result[] = $rule;
      }
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
