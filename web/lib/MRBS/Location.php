<?php
namespace MRBS;


abstract class Location extends Table
{

  protected $is_able;


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($name)
  {
    // This method should really be declared as an abstract public static function,
    // but in PHP 5 that throws a strict standards warning.  It's OK in PHP 7 onwards,
    // but while we are still supporting PHP 5 we need to do something else.
    // (An alternative solution that might make sense is to rename the room_name and
    // area_name columns to just 'name', which would have the added benefit of
    // simplifying the tables).
    throw new \Exception("getByName() needs to be implemented in the child class.");
  }

  abstract public function isDisabled();

  abstract public function getPermissions(array $role_ids);


  public function isVisible()
  {
    return $this->isAble(LocationPermission::READ);
  }


  protected function isAble($operation)
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
        $user = session()->getCurrentUser();
        if (isset($user))
        {
          $rules = $user->getRules($this);
        }
        else
        {
          // If there's no logged in user, return the default rules
          $rules = array(AreaPermission::getDefaultPermission());
        }
        $this->is_able[$operation] = LocationPermission::can($rules, $operation);
      }
    }

    return $this->is_able[$operation];
  }

}
