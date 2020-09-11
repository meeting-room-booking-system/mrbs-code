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


  public static function max($a, $b)
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


  public static function min($a, $b)
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
