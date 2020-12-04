<?php
namespace MRBS;


abstract class Location extends Table
{

  protected $is_able;


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  abstract public static function getByName($name);

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
